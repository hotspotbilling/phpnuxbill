<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Dashboard'));
$ui->assign('_admin', $admin);

// Infrastructure data (disk, RAM, cron, services, sessions) is for SuperAdmin/Admin only
$canViewSystem = in_array($admin['user_type'], ['SuperAdmin', 'Admin'], true);
$ui->assign('can_view_system', $canViewSystem);

if (isset($_GET['ajax_system_status'])) {
    header('Content-Type: application/json');
    if (!$canViewSystem) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
    echo json_encode(SystemInfo::getInfo());
    exit;
}

if (isset($_GET['refresh'])) {
    if (class_exists('RedisCache')) {
        RedisCache::flushPattern('dash:*');
    }
    // *.temp in the cache folder (older versions wrote the dashboard cache there) and in cache/dashboard/
    foreach ([$CACHE_PATH, $CACHE_PATH . DIRECTORY_SEPARATOR . 'dashboard'] as $cacheDir) {
        if (!is_dir($cacheDir)) {
            continue;
        }
        foreach (scandir($cacheDir) as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (is_file($cacheDir . DIRECTORY_SEPARATOR . $file) && $ext == 'temp') {
                unlink($cacheDir . DIRECTORY_SEPARATOR . $file);
            }
        }
    }
    r2(getUrl('dashboard'), 's', 'Data Refreshed');
}


$tipeUser = _req("user");
if (empty($tipeUser)) {
    $tipeUser = 'Admin';
}
$ui->assign('tipeUser', $tipeUser);

$reset_day = $config['reset_day'];
if (empty($reset_day)) {
    $reset_day = 1;
}
//first day of month
if (date("d") >= $reset_day) {
    $start_date = date('Y-m-' . $reset_day);
} else {
    $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
}

$current_date = date('Y-m-d');
$ui->assign('start_date', $start_date);
$ui->assign('current_date', $current_date);


if ($config['dashboard_type'] === 'dynamic') {

    if (class_exists('RedisCache') && RedisCache::isDashboardCacheEnabled()) {
        $data = RedisCache::remember('dash:metrics_dynamic', RedisCache::getDashboardTTL(), [RedisCache::class, 'calculateDynamicDashboard']);
    } elseif (class_exists('RedisCache') && RedisCache::isDashboardFileCacheEnabled()) {
        $data = RedisCache::rememberFile('dash:metrics_dynamic', RedisCache::getDashboardTTL(), [RedisCache::class, 'calculateDynamicDashboard']);
    } else {
        $data = RedisCache::calculateDynamicDashboard();
    }

    foreach ($data as $key => $val) {
        $ui->assign($key, $val);
    }

    // Volatile metrics are never cached: computed once per request and merged into the cached chart payload
    if ($canViewSystem) {
        $systemStatus = SystemInfo::getInfo();
        $ui->assign('system_status', $systemStatus);
        $ui->assign('online_users', $systemStatus['online_users']);
        $dashJson = json_decode($data['dashboard_data_json'] ?? '{}', true);
        $dashJson = is_array($dashJson) ? $dashJson : [];
        $dashJson['systemStatus'] = $systemStatus; // replaces anything an older cache entry may still carry
        $ui->assign('dashboard_data_json', json_encode($dashJson));
    } else {
        $ui->assign('system_status', []);
        $onlineUsers = SystemInfo::getOnlineUsers();
        $onlineUsers['rest_sessions'] = [];  // usernames / IPs / MACs of live sessions are admin-only
        $onlineUsers['mysql_sessions'] = [];
        $ui->assign('online_users', $onlineUsers);
        // a cache entry written by an older version may still carry infrastructure data: never pass it on
        $dashJson = json_decode($data['dashboard_data_json'] ?? '{}', true);
        if (is_array($dashJson)) {
            unset($dashJson['systemStatus']);
            $ui->assign('dashboard_data_json', json_encode($dashJson));
        }
    }

    $ui->display('admin/dashboard-new.tpl');
} else {
    $tipeUser = $admin['user_type'];
    if (in_array($tipeUser, ['SuperAdmin', 'Admin'])) {
        $tipeUser = 'Admin';
    }

    $computeWidgets = function () use ($WIDGET_PATH, $tipeUser) {
        $widgets = ORM::for_table('tbl_widgets')->where("enabled", 1)->where('user', $tipeUser)->order_by_asc("orders")->findArray();
        $count = count($widgets);
        for ($i = 0; $i < $count; $i++) {
            try {
                if (file_exists($WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'] . ".php")) {
                    require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'] . ".php";
                    $widgets[$i]['content'] = (new $widgets[$i]['widget'])->getWidget($widgets[$i]);
                } else {
                    $widgets[$i]['content'] = "Widget not found";
                }
            } catch (Throwable $e) {
                $widgets[$i]['content'] = $e->getMessage();
            }
        }
        return $widgets;
    };

    if (class_exists('RedisCache') && RedisCache::isDashboardCacheEnabled()) {
        // Agent/Sales widget output may depend on the logged-in admin, so scope the cache per admin
        $widgetCacheKey = 'dash:widgets_' . $tipeUser . ($tipeUser === 'Admin' ? '' : '_' . (int) $admin['id']);
        $widgets = RedisCache::remember($widgetCacheKey, RedisCache::getDashboardTTL(), $computeWidgets);
    } elseif (class_exists('RedisCache') && RedisCache::isDashboardFileCacheEnabled()) {
        $tipeKey = 'dash:widgets_' . $tipeUser . ($tipeUser === 'Admin' ? '' : '_' . (int) $admin['id']);
        $widgets = RedisCache::rememberFile($tipeKey, RedisCache::getDashboardTTL(), $computeWidgets);
    } else {
        $widgets = $computeWidgets();
    }

    $ui->assign('widgets', $widgets);
    run_hook('view_dashboard'); #HOOK
    $ui->display('admin/dashboard.tpl');
}
