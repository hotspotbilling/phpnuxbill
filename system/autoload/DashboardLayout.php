<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * How one admin arranged the dashboard. Kept per admin in tbl_appconfig (no schema change), under
 * dash_layout_<kind>_<admin id>:
 *   blocks   the dynamic dashboard: {"order": ["stats", "c_routely", "system", ...]}
 *   widgets  the default dashboard:  {"slots": {"1": ["w3", "c_routely"], "2": ["w7"]}}
 * Anything not in the saved layout (a new widget, a newly installed plugin card) goes where it would go by default,
 * and anything saved that no longer exists is ignored, so a stale layout can never hide or break the dashboard.
 */
class DashboardLayout
{
    /** The fixed blocks of the dynamic dashboard, in their default order. */
    const BLOCKS = ['cron', 'stats', 'system', 'online', 'charts1', 'charts2', 'charts3', 'charts4', 'charts5'];

    const KINDS = ['blocks', 'widgets'];

    private static function key($kind, $adminId)
    {
        return 'dash_layout_' . $kind . '_' . (int) $adminId;
    }

    /** The saved layout, or an empty array when the admin never arranged this dashboard. */
    public static function load($kind, $adminId)
    {
        if (!in_array($kind, self::KINDS, true) || (int) $adminId <= 0) {
            return [];
        }
        $row = ORM::for_table('tbl_appconfig')->where('setting', self::key($kind, $adminId))->find_one();
        $data = $row ? json_decode((string) $row->value, true) : null;
        return is_array($data) ? $data : [];
    }

    public static function save($kind, $adminId, array $data)
    {
        if (!in_array($kind, self::KINDS, true) || (int) $adminId <= 0) {
            return;
        }
        $row = ORM::for_table('tbl_appconfig')->where('setting', self::key($kind, $adminId))->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = self::key($kind, $adminId);
        }
        $row->value = json_encode($data);
        $row->save();
    }

    /** Back to the default arrangement. */
    public static function reset($kind, $adminId)
    {
        if (!in_array($kind, self::KINDS, true) || (int) $adminId <= 0) {
            return;
        }
        $row = ORM::for_table('tbl_appconfig')->where('setting', self::key($kind, $adminId))->find_one();
        if ($row) {
            $row->delete();
        }
    }

    /** How many places widgets can go in a role's layout string such as "12.7,5.12": the template numbers them 1, 2, 3 ... */
    public static function slotCount($layout)
    {
        $n = 0;
        foreach (explode('.', (string) $layout) as $row) {
            $n += $row == 12 ? 1 : count(explode(',', $row));
        }
        return max(1, $n);
    }

    /** The default order of the dynamic dashboard's blocks: plugin cards sit right after the statistics. */
    public static function defaultBlocks(array $cards)
    {
        $out = [];
        foreach (self::BLOCKS as $id) {
            $out[] = $id;
            if ($id === 'stats') {
                foreach ($cards as $c) {
                    $out[] = $c['id'];
                }
            }
        }
        return $out;
    }

    /**
     * The dynamic dashboard: block id => its place (0, 1, 2 ...). The template puts it in a CSS `order`, so with nothing saved
     * the page looks exactly as it did.
     */
    public static function blockOrder(array $saved, array $cards)
    {
        $default = self::defaultBlocks($cards);
        $final = [];
        foreach ((array) ($saved['order'] ?? []) as $id) {
            if (is_string($id) && in_array($id, $default, true) && !in_array($id, $final, true)) {
                $final[] = $id;
            }
        }
        foreach ($default as $id) {
            if (!in_array($id, $final, true)) {
                $final[] = $id;
            }
        }
        return array_flip($final);
    }

    /**
     * The default dashboard: put the role's widgets and the plugin cards in their places. Returns the same kind of list the
     * template already loops over, each item with its final `position` (the slot) and a `block` id, in the order to print.
     */
    public static function arrangeWidgets(array $widgets, array $cards, array $saved, $slotCount)
    {
        $items = [];
        foreach ($widgets as $w) {
            $w['block'] = 'w' . (int) $w['id'];
            $w['position'] = min(max(1, (int) $w['position']), $slotCount);
            $items[$w['block']] = $w;
        }
        foreach ($cards as $c) {
            $items[$c['id']] = ['block' => $c['id'], 'position' => 1, 'orders' => -1, 'title' => $c['title'], 'content' => $c['html']];
        }
        $slots = array_fill(1, $slotCount, []);
        $placed = [];
        foreach ((array) ($saved['slots'] ?? []) as $n => $ids) {
            $n = (int) $n;
            if ($n < 1 || $n > $slotCount || !is_array($ids)) {
                continue;
            }
            foreach ($ids as $id) {
                if (is_string($id) && isset($items[$id]) && !isset($placed[$id])) {
                    $slots[$n][] = $id;
                    $placed[$id] = true;
                }
            }
        }
        $rest = array_diff_key($items, $placed);
        uasort($rest, function ($a, $b) {
            return [$a['position'], (int) $a['orders']] <=> [$b['position'], (int) $b['orders']];
        });
        foreach ($rest as $id => $it) {
            $slots[$it['position']][] = $id;
        }
        $out = [];
        foreach ($slots as $n => $ids) {
            foreach ($ids as $id) {
                $it = $items[$id];
                $it['position'] = $n;
                $out[] = $it;
            }
        }
        return $out;
    }

    /**
     * What the browser sent, reduced to what is allowed: known ids only, each once, slots that exist. Returns null when it is
     * not a layout at all.
     * @param array $validIds every block or widget id that exists right now
     */
    public static function sanitize($kind, $raw, array $validIds, $slotCount)
    {
        if (!is_array($raw)) {
            return null;
        }
        $seen = [];
        $clean = function ($ids) use (&$seen, $validIds) {
            $out = [];
            foreach ((array) $ids as $id) {
                if (is_string($id) && in_array($id, $validIds, true) && !isset($seen[$id])) {
                    $seen[$id] = true;
                    $out[] = $id;
                }
            }
            return $out;
        };
        if ($kind === 'blocks') {
            return ['order' => $clean($raw['order'] ?? [])];
        }
        if ($kind === 'widgets') {
            $slots = [];
            foreach ((array) ($raw['slots'] ?? []) as $n => $ids) {
                $n = (int) $n;
                if ($n >= 1 && $n <= $slotCount) {
                    $slots[$n] = $clean($ids);
                }
            }
            return ['slots' => $slots];
        }
        return null;
    }
}
