<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  Enhanced Global Command Search
 **/

if (!isset($_SESSION['aid'])) {
    http_response_code(403);
    echo '<div class="search-empty-state"><i class="fa fa-lock"></i> <span>' . Lang::T('Session expired. Please log in.') . '</span></div>';
    exit;
}

global $UPLOAD_PATH, $root_path;
$baseUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
$uploadPath = trim(str_replace($root_path, '', $UPLOAD_PATH), '/\\');
$defaultAvatarUrl = $baseUrl . '/' . $uploadPath . '/user.default.jpg';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($query === '') {
    // Show Quick Navigation & Shortcuts when query is empty or focused
    $quickLinks = [
        ['title' => Lang::T('Add Customer'), 'sub' => Lang::T('Create a new customer profile'), 'url' => getUrl('customers/add'), 'icon' => 'fa-user-plus', 'category' => Lang::T('Actions')],
        ['title' => Lang::T('Recharge Account'), 'sub' => Lang::T('Refill customer package or voucher'), 'url' => getUrl('plan/recharge'), 'icon' => 'fa-bolt', 'category' => Lang::T('Actions')],
        ['title' => Lang::T('Hotspot Plans'), 'sub' => Lang::T('Manage internet packages'), 'url' => getUrl('services/hotspot'), 'icon' => 'fa-wifi', 'category' => Lang::T('Navigation')],
        ['title' => Lang::T('Routers & Network'), 'sub' => Lang::T('Manage MikroTik routers and interfaces'), 'url' => getUrl('routers/list'), 'icon' => 'fa-server', 'category' => Lang::T('Navigation')],
        ['title' => Lang::T('Transactions & Reports'), 'sub' => Lang::T('View invoices and revenue reports'), 'url' => getUrl('reports/daily'), 'icon' => 'fa-line-chart', 'category' => Lang::T('Navigation')],
        ['title' => Lang::T('General Settings'), 'sub' => Lang::T('System configuration and company details'), 'url' => getUrl('settings/app'), 'icon' => 'fa-cog', 'category' => Lang::T('Navigation')],
    ];

    echo '<div class="search-results-container">';
    echo '<div class="search-section">';
    echo '<div class="search-section-header"><span class="search-section-title"><i class="fa fa-compass"></i> ' . Lang::T('Quick Jump & Actions') . '</span></div>';
    echo '<div class="search-items-list">';
    foreach ($quickLinks as $item) {
        echo '<a href="' . $item['url'] . '" class="search-item" tabindex="-1">';
        echo '  <div class="search-item-icon"><i class="fa ' . $item['icon'] . '"></i></div>';
        echo '  <div class="search-item-info">';
        echo '    <div class="search-item-title">' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '    <div class="search-item-sub">' . htmlspecialchars($item['sub'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '  </div>';
        echo '  <div class="search-item-badge badge-neutral">' . htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '</a>';
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="search-results-footer">';
    echo '  <span><kbd>↑</kbd><kbd>↓</kbd> ' . Lang::T('Navigate') . '</span>';
    echo '  <span><kbd>↵</kbd> ' . Lang::T('Select') . '</span>';
    echo '  <span><kbd>ESC</kbd> ' . Lang::T('Close') . '</span>';
    echo '</div>';
    echo '</div>';
    exit;
}

$hasResults = false;
$safeQuery = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
echo '<div class="search-results-container">';

// 1. Quick Action Match
$quickActions = [
    'add customer' => ['title' => Lang::T('Add Customer'), 'sub' => Lang::T('Register new subscriber'), 'url' => getUrl('customers/add'), 'icon' => 'fa-user-plus'],
    'new customer' => ['title' => Lang::T('Add Customer'), 'sub' => Lang::T('Register new subscriber'), 'url' => getUrl('customers/add'), 'icon' => 'fa-user-plus'],
    'recharge' => ['title' => Lang::T('Recharge Customer'), 'sub' => Lang::T('Activate plan for subscriber'), 'url' => getUrl('plan/recharge'), 'icon' => 'fa-bolt'],
    'refill' => ['title' => Lang::T('Recharge Customer'), 'sub' => Lang::T('Activate plan for subscriber'), 'url' => getUrl('plan/recharge'), 'icon' => 'fa-bolt'],
    'router' => ['title' => Lang::T('Add Router'), 'sub' => Lang::T('Connect new MikroTik router'), 'url' => getUrl('routers/add'), 'icon' => 'fa-server'],
    'voucher' => ['title' => Lang::T('Generate Vouchers'), 'sub' => Lang::T('Batch voucher generator'), 'url' => getUrl('plan/voucher'), 'icon' => 'fa-ticket'],
    'logs' => ['title' => Lang::T('Activity Logs'), 'sub' => Lang::T('System event logs'), 'url' => getUrl('logs'), 'icon' => 'fa-history'],
    'settings' => ['title' => Lang::T('General Settings'), 'sub' => Lang::T('Configure application preferences'), 'url' => getUrl('settings/general'), 'icon' => 'fa-cog'],
    'backup' => ['title' => Lang::T('Database Backup'), 'sub' => Lang::T('Database status and backup'), 'url' => getUrl('settings/dbstatus'), 'icon' => 'fa-database'],
];

$matchedActions = [];
foreach ($quickActions as $key => $action) {
    if (stripos($key, $query) !== false || stripos($action['title'], $query) !== false) {
        $matchedActions[] = $action;
    }
}

if (!empty($matchedActions)) {
    $hasResults = true;
    echo '<div class="search-section">';
    echo '<div class="search-section-header"><span class="search-section-title"><i class="fa fa-bolt"></i> ' . Lang::T('Quick Actions') . '</span></div>';
    echo '<div class="search-items-list">';
    foreach ($matchedActions as $act) {
        echo '<a href="' . $act['url'] . '" class="search-item" tabindex="-1">';
        echo '  <div class="search-item-icon"><i class="fa ' . $act['icon'] . '"></i></div>';
        echo '  <div class="search-item-info">';
        echo '    <div class="search-item-title">' . htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '    <div class="search-item-sub">' . htmlspecialchars($act['sub'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '  </div>';
        echo '  <div class="search-item-badge badge-neutral">' . Lang::T('Action') . '</div>';
        echo '</a>';
    }
    echo '</div>';
    echo '</div>';
}

// 2. Customers Search
$customers = ORM::for_table('tbl_customers')
    ->where_raw('(`username` LIKE ? OR `fullname` LIKE ? OR `phonenumber` LIKE ? OR `email` LIKE ? OR `pppoe_username` LIKE ?)', [
        "%$query%", "%$query%", "%$query%", "%$query%", "%$query%"
    ])
    ->limit(6)
    ->find_many();

if ($customers && count($customers) > 0) {
    $hasResults = true;
    echo '<div class="search-section">';
    echo '<div class="search-section-header">';
    echo '  <span class="search-section-title"><i class="fa fa-users"></i> ' . Lang::T('Customers') . '</span>';
    echo '  <span class="search-section-count">' . count($customers) . '</span>';
    echo '</div>';
    echo '<div class="search-items-list">';
    foreach ($customers as $c) {
        $url = getUrl('customers/view/' . $c->id);
        $statusClass = ($c->status == 'Active') ? 'badge-success' : 'badge-danger';
        $subMeta = [];
        if (!empty($c->username)) $subMeta[] = '@' . $c->username;
        if (!empty($c->phonenumber)) $subMeta[] = $c->phonenumber;
        if (!empty($c->email)) $subMeta[] = $c->email;
        $subText = implode(' • ', $subMeta);

        $photo = ltrim((string) $c->photo, '/');
        if (!empty($photo) && strpos($photo, 'default') === false) {
            $avatarUrl = $baseUrl . '/' . $uploadPath . '/' . $photo . '.thumb.jpg';
        } else {
            $avatarUrl = $defaultAvatarUrl;
        }

        $emailHash = !empty($c->email) ? md5(strtolower(trim($c->email))) : md5($c->username);
        $gravatarUrl = 'https://www.gravatar.com/avatar/' . $emailHash . '?d=mp';

        $jsDefault = json_encode($defaultAvatarUrl);
        $jsGravatar = json_encode($gravatarUrl);
        $avatarJs = "if(!this.dataset.step){this.dataset.step=(this.src.indexOf('user.default.jpg')!==-1)?'gravatar':'default';}if(this.dataset.step==='default'){this.dataset.step='gravatar';this.src=" . $jsDefault . ";}else if(this.dataset.step==='gravatar'){this.dataset.step='done';this.src=" . $jsGravatar . ";}else{this.onerror=null;}";
        $avatarOnerror = htmlspecialchars($avatarJs, ENT_QUOTES, 'UTF-8');

        echo '<a href="' . $url . '" class="search-item" tabindex="-1">';
        echo '  <div class="search-item-avatar">';
        echo '    <img src="' . htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') . '" onerror="' . $avatarOnerror . '" alt="avatar">';
        echo '  </div>';
        echo '  <div class="search-item-info">';
        echo '    <div class="search-item-title">' . htmlspecialchars($c->fullname ?: $c->username, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '    <div class="search-item-sub">' . htmlspecialchars($subText, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '  </div>';
        echo '  <div class="search-item-badge ' . $statusClass . '">' . htmlspecialchars(Lang::T($c->status), ENT_QUOTES, 'UTF-8') . '</div>';
        echo '</a>';
    }
    echo '</div>';
    echo '</div>';
}

// 3. Plans / Packages Search
$plans = ORM::for_table('tbl_plans')
    ->where_raw('(`name_plan` LIKE ? OR `type` LIKE ?)', ["%$query%", "%$query%"])
    ->limit(4)
    ->find_many();

if ($plans && count($plans) > 0) {
    $hasResults = true;
    echo '<div class="search-section">';
    echo '<div class="search-section-header">';
    echo '  <span class="search-section-title"><i class="fa fa-wifi"></i> ' . Lang::T('Plans & Packages') . '</span>';
    echo '  <span class="search-section-count">' . count($plans) . '</span>';
    echo '</div>';
    echo '<div class="search-items-list">';
    foreach ($plans as $p) {
        $url = getUrl('services/edit/' . $p->id);
        $typeClass = (strtoupper($p->type) == 'HOTSPOT') ? 'badge-primary' : 'badge-info';
        $subText = Lang::moneyFormat($p->price) . ' • ' . $p->validity . ' ' . Lang::T($p->validity_unit);

        echo '<a href="' . $url . '" class="search-item" tabindex="-1">';
        echo '  <div class="search-item-icon plan-icon"><i class="fa fa-cubes"></i></div>';
        echo '  <div class="search-item-info">';
        echo '    <div class="search-item-title">' . htmlspecialchars($p->name_plan, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '    <div class="search-item-sub">' . htmlspecialchars($subText, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '  </div>';
        echo '  <div class="search-item-badge ' . $typeClass . '">' . htmlspecialchars($p->type, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '</a>';
    }
    echo '</div>';
    echo '</div>';
}

// 4. Routers Search
$routers = ORM::for_table('tbl_routers')
    ->where_raw('(`name` LIKE ? OR `ip_address` LIKE ?)', ["%$query%", "%$query%"])
    ->limit(4)
    ->find_many();

if ($routers && count($routers) > 0) {
    $hasResults = true;
    echo '<div class="search-section">';
    echo '<div class="search-section-header">';
    echo '  <span class="search-section-title"><i class="fa fa-server"></i> ' . Lang::T('Routers') . '</span>';
    echo '  <span class="search-section-count">' . count($routers) . '</span>';
    echo '</div>';
    echo '<div class="search-items-list">';
    foreach ($routers as $r) {
        $url = getUrl('routers/edit/' . $r->id);
        $statusClass = ($r->enabled == 1) ? 'badge-success' : 'badge-danger';
        $statusText = ($r->enabled == 1) ? Lang::T('Enabled') : Lang::T('Disabled');

        echo '<a href="' . $url . '" class="search-item" tabindex="-1">';
        echo '  <div class="search-item-icon router-icon"><i class="fa fa-hdd-o"></i></div>';
        echo '  <div class="search-item-info">';
        echo '    <div class="search-item-title">' . htmlspecialchars($r->name, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '    <div class="search-item-sub">' . htmlspecialchars($r->ip_address, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '  </div>';
        echo '  <div class="search-item-badge ' . $statusClass . '">' . htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '</a>';
    }
    echo '</div>';
    echo '</div>';
}

// 5. Invoices / Transactions Search
$transactions = ORM::for_table('tbl_transactions')
    ->where_raw('(`invoice` LIKE ? OR `username` LIKE ? OR `plan_name` LIKE ?)', ["%$query%", "%$query%", "%$query%"])
    ->order_by_desc('id')
    ->limit(4)
    ->find_many();

if ($transactions && count($transactions) > 0) {
    $hasResults = true;
    echo '<div class="search-section">';
    echo '<div class="search-section-header">';
    echo '  <span class="search-section-title"><i class="fa fa-file-text-o"></i> ' . Lang::T('Invoices & Transactions') . '</span>';
    echo '  <span class="search-section-count">' . count($transactions) . '</span>';
    echo '</div>';
    echo '<div class="search-items-list">';
    foreach ($transactions as $t) {
        $url = getUrl('plan/view/' . $t->id);
        $subText = '@' . $t->username . ' • ' . $t->plan_name . ' • ' . $t->recharged_on;

        echo '<a href="' . $url . '" class="search-item" tabindex="-1">';
        echo '  <div class="search-item-icon trx-icon"><i class="fa fa-file-text"></i></div>';
        echo '  <div class="search-item-info">';
        echo '    <div class="search-item-title">#' . htmlspecialchars($t->invoice ?: $t->id, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '    <div class="search-item-sub">' . htmlspecialchars($subText, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '  </div>';
        echo '  <div class="search-item-badge badge-neutral">' . Lang::moneyFormat($t->price) . '</div>';
        echo '</a>';
    }
    echo '</div>';
    echo '</div>';
}

if (!$hasResults) {
    echo '<div class="search-empty-state">';
    echo '  <div class="search-empty-icon"><i class="fa fa-search-minus"></i></div>';
    echo '  <div class="search-empty-title">' . Lang::T('No results found for') . ' "<strong>' . $safeQuery . '</strong>"</div>';
    echo '  <div class="search-empty-desc">' . Lang::T('Try searching by customer name, username, phone, plan, router IP, or invoice #.') . '</div>';
    echo '</div>';
} else {
    echo '<div class="search-results-footer">';
    echo '  <span><kbd>↑</kbd><kbd>↓</kbd> ' . Lang::T('Navigate') . '</span>';
    echo '  <span><kbd>↵</kbd> ' . Lang::T('Select') . '</span>';
    echo '  <span><kbd>ESC</kbd> ' . Lang::T('Close') . '</span>';
    echo '</div>';
}

echo '</div>';
