<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Queries behind the Daily Report, its charts and its Print/PDF export, written to stay fast on millions of transactions:
 * the date range is compared on the columns themselves (so the recharged_on index is used), a filter is only added when the
 * admin narrowed it, counts and charts are GROUP BY queries in the database, and full names are looked up for the rows shown
 * instead of joining every transaction to tbl_customers.
 */
class TransactionReport
{
    /** Seconds the filter choices (distinct plans, methods, routers) are kept: reading them scans the whole table. */
    const OPTIONS_TTL = 600;

    /** Days the line chart covers, counted back from the end date. */
    const LINE_DAYS = 30;

    /** Chart dimension => SQL expression it groups by. */
    const DIMENSIONS = [
        'type' => '`type`',
        'plan' => '`plan_name`',
        'method' => "SUBSTRING_INDEX(`method`, ' - ', 1)",
        'router' => '`routers`',
    ];

    /** The choices for the filter lists: ['types' => [], 'plans' => [], 'methods' => [], 'routers' => []]. */
    public static function filterOptions()
    {
        return self::cached('report_trx_filters', self::OPTIONS_TTL, function () {
            return [
                'types' => ORM::for_table('tbl_transactions')->getEnum('type'),
                'plans' => self::column("SELECT DISTINCT `plan_name` FROM `tbl_transactions` ORDER BY `plan_name`"),
                'methods' => self::column("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) AS m FROM `tbl_transactions` ORDER BY m"),
                'routers' => self::column("SELECT DISTINCT `routers` FROM `tbl_transactions` ORDER BY `routers`"),
            ];
        });
    }

    /**
     * The filter the admin asked for (sd, ed, ts, te and tps/plns/mts/rts from the query string), checked.
     * A list is empty when it does not narrow anything: nothing chosen, or every choice ticked.
     */
    public static function filterFromRequest(array $options, $defaultStart)
    {
        $f = [
            'sd' => self::date(_req('sd'), $defaultStart),
            'ed' => self::date(_req('ed'), date('Y-m-d')),
            'ts' => self::time(_req('ts'), '00:00:00', '00'),
            'te' => self::time(_req('te'), '23:59:59', '59'),
            'tps' => self::pick('tps', $options['types']),
            'plns' => self::pick('plns', $options['plans']),
            'mts' => self::pick('mts', $options['methods']),
            'rts' => self::pick('rts', $options['routers']),
        ];
        return $f;
    }

    /** Start of the current billing month (Settings: reset day), the default start date; a day past a month's end is its last day. */
    public static function defaultStart()
    {
        global $config;
        $day = max(1, min(31, (int) ($config['reset_day'] ?? 1)));
        $month = strtotime(date('Y-m-01'));
        // the reset day of this month, a day past its end being its last day
        if ((int) date('j') < min($day, (int) date('t', $month))) {
            $month = strtotime('-1 month', $month);
        }
        return date('Y-m-', $month) . sprintf('%02d', min($day, (int) date('t', $month)));
    }

    /** WHERE clause and its parameters for a filter. */
    public static function where(array $f)
    {
        // BETWEEN keeps the index on recharged_on usable; the two OR terms trim the first and last day to the chosen times
        $w = ['`recharged_on` BETWEEN ? AND ?', '(`recharged_on` > ? OR `recharged_time` >= ?)', '(`recharged_on` < ? OR `recharged_time` <= ?)'];
        $p = [$f['sd'], $f['ed'], $f['sd'], $f['ts'], $f['ed'], $f['te']];
        foreach (['tps' => '`type`', 'plns' => '`plan_name`', 'rts' => '`routers`'] as $key => $col) {
            if (!empty($f[$key])) {
                $w[] = $col . ' IN (' . implode(',', array_fill(0, count($f[$key]), '?')) . ')';
                array_push($p, ...$f[$key]);
            }
        }
        if (!empty($f['mts'])) {
            // a method is saved as "Gateway - channel", or only "Gateway"
            $or = [];
            foreach ($f['mts'] as $mt) {
                $or[] = '`method` = ? OR `method` LIKE ?';
                $p[] = $mt;
                $p[] = addcslashes($mt, '\\%_') . ' - %';
            }
            $w[] = '(' . implode(' OR ', $or) . ')';
        }
        return [implode(' AND ', $w), $p];
    }

    /** The transactions of a filter, newest first, ready for Paginator or find_array(). */
    public static function query(array $f)
    {
        [$w, $p] = self::where($f);
        return ORM::for_table('tbl_transactions')
            ->where_raw($w, $p)
            ->order_by_desc('recharged_on')
            ->order_by_desc('recharged_time')
            ->order_by_desc('id');
    }

    /** ['count' => rows, 'sum' => total price] of a filter, in one pass. */
    public static function totals(array $f)
    {
        [$w, $p] = self::where($f);
        $r = self::rows("SELECT COUNT(*) AS c, COALESCE(SUM(`price`), 0) AS s FROM `tbl_transactions` WHERE $w", $p);
        return ['count' => (int) ($r[0]['c'] ?? 0), 'sum' => (float) ($r[0]['s'] ?? 0)];
    }

    /** Pie chart data for one dimension: ['labels' => ["name (n)"], 'datas' => [n]], largest first. */
    public static function pie(array $f, $dim)
    {
        $expr = self::DIMENSIONS[$dim];
        [$w, $p] = self::where($f);
        $result = ['labels' => [], 'datas' => []];
        foreach (self::rows("SELECT $expr AS k, COUNT(*) AS c FROM `tbl_transactions` WHERE $w GROUP BY k ORDER BY c DESC", $p) as $r) {
            $result['labels'][] = $r['k'] . ' (' . $r['c'] . ')';
            $result['datas'][] = (int) $r['c'];
        }
        return $result;
    }

    /**
     * Line chart data: the last LINE_DAYS days up to the end date, newest first, one line per type, plan, method and router
     * that has transactions in them. ['labels' => [dates], 'datas' => [['label' => name, 'data' => [n per date]]]]
     */
    public static function line(array $f)
    {
        $first = date('Y-m-d', strtotime($f['ed'] . ' -' . (self::LINE_DAYS - 1) . ' days'));
        if ($first < $f['sd']) {
            $first = $f['sd'];
        }
        $dates = [];
        for ($t = strtotime($f['ed']); $t >= strtotime($first); $t = strtotime('-1 day', $t)) {
            $dates[] = date('Y-m-d', $t);
        }
        $result = ['labels' => $dates, 'datas' => []];
        if (!$dates) {
            return $result;
        }
        $g = $f;
        $g['sd'] = max($f['sd'], $first);
        if ($g['sd'] > $f['sd']) {
            $g['ts'] = '00:00:00'; // the range now starts on a later day, so the start time no longer applies
        }
        [$w, $p] = self::where($g);
        $pos = array_flip($dates);
        foreach (self::DIMENSIONS as $dim => $expr) {
            $lines = [];
            foreach (self::rows("SELECT `recharged_on` AS d, $expr AS k, COUNT(*) AS c FROM `tbl_transactions` WHERE $w GROUP BY d, k", $p) as $r) {
                if (!isset($pos[$r['d']])) {
                    continue;
                }
                if (!isset($lines[$r['k']])) {
                    $lines[$r['k']] = array_fill(0, count($dates), 0);
                }
                $lines[$r['k']][$pos[$r['d']]] = (int) $r['c'];
            }
            foreach ($lines as $k => $data) {
                // named with its dimension: a plan and a type (or router) may share a name
                $result['datas'][] = ['label' => $dim . ': ' . $k, 'data' => $data];
            }
        }
        return $result;
    }

    /** Adds 'fullname' to transaction rows (arrays) with one lookup for the usernames they hold. */
    public static function withNames(array $rows)
    {
        $users = array_values(array_unique(array_filter(array_column($rows, 'username'), 'strlen')));
        $names = [];
        foreach (array_chunk($users, 500) as $chunk) {
            $sql = 'SELECT `username`, `fullname` FROM `tbl_customers` WHERE `username` IN (' . implode(',', array_fill(0, count($chunk), '?')) . ')';
            foreach (self::rows($sql, $chunk) as $r) {
                $names[$r['username']] = $r['fullname'];
            }
        }
        foreach ($rows as &$row) {
            $row['fullname'] = $names[$row['username']] ?? '';
        }
        unset($row);
        return $rows;
    }

    /** Rows in tbl_transactions by type (['Hotspot' => n, ...]), counted once a minute: an exact count reads millions of index entries. */
    public static function typeCounts()
    {
        return self::cached('report_trx_types', 60, function () {
            $out = [];
            foreach (self::rows('SELECT `type` AS k, COUNT(*) AS c FROM `tbl_transactions` GROUP BY `type`') as $r) {
                $out[(string) $r['k']] = (int) $r['c'];
            }
            return $out;
        });
    }

    /** Rows in tbl_transactions (see typeCounts()). */
    public static function tableCount()
    {
        return (int) array_sum(self::typeCounts());
    }

    /** Days the trend chart of summary() covers at most, counted back from the end date. */
    const TREND_DAYS = 92;

    /**
     * Everything the Daily Report's insight panels show, in one request:
     * 'daily' => ['labels' => dates oldest first, 'count' => [], 'revenue' => []] (the last TREND_DAYS days of the range),
     * 'type' / 'plan' / 'method' / 'router' => ['items' => [['name', 'count', 'sum']], 'others' => ['count', 'sum'], 'total' => sum].
     */
    public static function summary(array $f, $top = 6)
    {
        $out = ['daily' => self::dailyTrend($f)];
        [$w, $p] = self::where($f);
        // one pass over the range for all four breakdowns: grouped by every dimension at once, then added up here
        $sel = [];
        foreach (self::DIMENSIONS as $dim => $expr) {
            $sel[] = "$expr AS `$dim`";
        }
        $acc = array_fill_keys(array_keys(self::DIMENSIONS), []);
        foreach (self::rows('SELECT ' . implode(', ', $sel) . ", COUNT(*) AS c, COALESCE(SUM(`price`), 0) AS s FROM `tbl_transactions` WHERE $w GROUP BY " . implode(', ', array_map(function ($d) { return "`$d`"; }, array_keys(self::DIMENSIONS))), $p) as $r) {
            foreach ($acc as $dim => $_) {
                $k = (string) $r[$dim];
                if (!isset($acc[$dim][$k])) {
                    $acc[$dim][$k] = ['k' => $k, 'c' => 0, 's' => 0.0];
                }
                $acc[$dim][$k]['c'] += (int) $r['c'];
                $acc[$dim][$k]['s'] += (float) $r['s'];
            }
        }
        foreach (self::DIMENSIONS as $dim => $expr) {
            $rows = array_values($acc[$dim]);
            usort($rows, function ($a, $b) {
                return [$b['s'], $b['c']] <=> [$a['s'], $a['c']];
            });
            $items = [];
            $others = ['count' => 0, 'sum' => 0.0];
            $total = 0.0;
            foreach ($rows as $i => $r) {
                $total += (float) $r['s'];
                if ($i < $top) {
                    $items[] = ['name' => (string) $r['k'], 'count' => (int) $r['c'], 'sum' => (float) $r['s']];
                } else {
                    $others['count'] += (int) $r['c'];
                    $others['sum'] += (float) $r['s'];
                }
            }
            $out[$dim] = ['items' => $items, 'others' => $others, 'total' => $total];
        }
        return $out;
    }

    private static function dailyTrend(array $f)
    {
        $first = date('Y-m-d', strtotime($f['ed'] . ' -' . (self::TREND_DAYS - 1) . ' days'));
        $g = $f;
        if ($first > $f['sd']) {
            $g['sd'] = $first;
            $g['ts'] = '00:00:00';
        }
        [$w, $p] = self::where($g);
        $by = [];
        foreach (self::rows("SELECT `recharged_on` AS d, COUNT(*) AS c, COALESCE(SUM(`price`), 0) AS s FROM `tbl_transactions` WHERE $w GROUP BY d", $p) as $r) {
            $by[$r['d']] = $r;
        }
        $out = ['labels' => [], 'count' => [], 'revenue' => []];
        for ($t = strtotime($g['sd']), $end = strtotime($g['ed']); $t <= $end; $t = strtotime('+1 day', $t)) {
            $d = date('Y-m-d', $t);
            $out['labels'][] = $d;
            $out['count'][] = isset($by[$d]) ? (int) $by[$d]['c'] : 0;
            $out['revenue'][] = isset($by[$d]) ? (float) $by[$d]['s'] : 0.0;
        }
        return $out;
    }

    /** The same filter moved back to the period of equal length that ends just before it starts (for "vs previous period"). */
    public static function previous(array $f)
    {
        $days = (int) round((strtotime($f['ed']) - strtotime($f['sd'])) / 86400) + 1;
        $g = $f;
        $g['sd'] = date('Y-m-d', strtotime($f['sd'] . " -$days days"));
        $g['ed'] = date('Y-m-d', strtotime($f['ed'] . " -$days days"));
        return $g;
    }

    private static function rows($sql, array $params = [])
    {
        $st = ORM::get_db()->prepare($sql);
        $st->execute(array_values($params));
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function column($sql)
    {
        $out = [];
        foreach (ORM::get_db()->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $v) {
            if ($v !== null && $v !== '') {
                $out[] = (string) $v;
            }
        }
        return $out;
    }

    private static function cached($key, $ttl, callable $fn)
    {
        if (RedisCache::isConnected()) {
            return RedisCache::remember($key, $ttl, $fn);
        }
        return RedisCache::rememberFile($key, $ttl, $fn);
    }

    /** Chosen values of a multi-select in the query string; [] when it does not narrow anything. */
    private static function pick($name, array $all)
    {
        $v = isset($_GET[$name]) ? $_GET[$name] : [];
        if (!is_array($v)) {
            $v = [$v];
        }
        $v = array_values(array_unique(array_filter($v, function ($x) {
            return is_scalar($x) && trim((string) $x) !== '';
        })));
        $v = array_map('strval', $v);
        if (!$v || ($all && !array_diff($all, $v))) {
            return [];
        }
        return $v;
    }

    private static function date($v, $default)
    {
        $v = trim((string) $v);
        $d = DateTime::createFromFormat('!Y-m-d', $v);
        return ($d && $d->format('Y-m-d') === $v) ? $v : $default;
    }

    private static function time($v, $default, $seconds)
    {
        $v = trim((string) $v);
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v)) {
            return $v . ':' . $seconds; // a time box sends HH:MM; the end of a minute is :59
        }
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $v) ? $v : $default;
    }
}
