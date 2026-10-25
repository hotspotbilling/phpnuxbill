<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

class SystemInfo
{
    /**
     * Get comprehensive system metrics including Cron, Disk, RAM, CPU, Uptime, Database, and Services
     */
    public static function getInfo(): array
    {
        global $UPLOAD_PATH, $config;

        $cron = self::getCronStatus($UPLOAD_PATH);
        $disk = self::getDiskUsage();
        $ram  = self::getRamUsage();
        $cpu  = self::getCpuUsage();
        $uptime = self::getSystemUptime();
        $db   = self::getDatabaseStatus();
        $onlineUsers = self::getOnlineUsers();
        $services = self::getServicesStatus($cron, $db, $onlineUsers);

        return [
            'cron'         => $cron,
            'disk'         => $disk,
            'ram'          => $ram,
            'cpu'          => $cpu,
            'uptime'       => $uptime,
            'database'     => $db,
            'online_users' => $onlineUsers,
            'services'     => $services,
            'os'           => [
                'family'   => PHP_OS_FAMILY,
                'name'     => php_uname('s'),
                'release'  => php_uname('r'),
                'hostname' => php_uname('n'),
                'arch'     => php_uname('m'),
                'php'      => PHP_VERSION,
                'sapi'     => php_sapi_name(),
                'web'      => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI / WebServer'
            ]
        ];
    }

    /**
     * Replicates and enhances the old dashboard cron_monitor widget
     */
    public static function getCronStatus(?string $uploadPath = null): array
    {
        global $UPLOAD_PATH;
        $path = $uploadPath ?: $UPLOAD_PATH;
        $timestampFile = $path . '/cron_last_run.txt';

        $lastRunTime = 0;
        $fileExists = false;

        if (file_exists($timestampFile)) {
            $content = trim((string) @file_get_contents($timestampFile));
            if (is_numeric($content) && (int) $content > 0) {
                $lastRunTime = (int) $content;
                $fileExists = true;
            }
        }

        $now = time();
        $diff = $lastRunTime > 0 ? ($now - $lastRunTime) : null;

        if (!$fileExists || $lastRunTime <= 0) {
            return [
                'status'       => 'danger',
                'badge'        => 'danger',
                'is_healthy'   => false,
                'last_run_time'=> 0,
                'run_date'     => null,
                'diff_seconds' => null,
                'time_ago'     => Lang::T('Never'),
                'title'        => Lang::T('Cron Job Not Running'),
                'message'      => Lang::T('Cron appears not to have been set up. Please check your cron setup (e.g. system/cron.php).')
            ];
        }

        $runDate = date('Y-m-d h:i:s A', $lastRunTime);
        $timeAgo = self::timeAgo($lastRunTime);

        if ($diff > 86400) {
            return [
                'status'       => 'danger',
                'badge'        => 'danger',
                'is_healthy'   => false,
                'last_run_time'=> $lastRunTime,
                'run_date'     => $runDate,
                'diff_seconds' => $diff,
                'time_ago'     => $timeAgo,
                'title'        => Lang::T('Cron Stalled'),
                'message'      => sprintf(Lang::T('Cron has not run for over 24 hours (Last ran %s). Please check your crontab service.'), $timeAgo)
            ];
        }

        if ($diff > 3600) {
            return [
                'status'       => 'warning',
                'badge'        => 'warning',
                'is_healthy'   => false,
                'last_run_time'=> $lastRunTime,
                'run_date'     => $runDate,
                'diff_seconds' => $diff,
                'time_ago'     => $timeAgo,
                'title'        => Lang::T('Cron Delayed'),
                'message'      => sprintf(Lang::T('Cron has not run for over 1 hour (Last ran %s). Please check your setup.'), $timeAgo)
            ];
        }

        return [
            'status'       => 'success',
            'badge'        => 'success',
            'is_healthy'   => true,
            'last_run_time'=> $lastRunTime,
            'run_date'     => $runDate,
            'diff_seconds' => $diff,
            'time_ago'     => $timeAgo,
            'title'        => Lang::T('Cron Active'),
            'message'      => sprintf(Lang::T('Cron Job last ran on: %s (%s)'), $runDate, $timeAgo)
        ];
    }

    /**
     * Disk space information
     */
    public static function getDiskUsage(): array
    {
        $root = defined('FILE_PATH') ? FILE_PATH : '.';
        $total = @disk_total_space($root) ?: 0.0;
        $free  = @disk_free_space($root) ?: 0.0;
        $used  = max(0.0, $total - $free);
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0.0;

        $status = 'success';
        if ($percent >= 90) {
            $status = 'danger';
        } elseif ($percent >= 80) {
            $status = 'warning';
        }

        return [
            'total'           => (float) $total,
            'free'            => (float) $free,
            'used'            => (float) $used,
            'percent'         => $percent,
            'free_percent'    => round(max(0, 100 - $percent), 1),
            'total_formatted' => self::formatBytes($total),
            'free_formatted'  => self::formatBytes($free),
            'used_formatted'  => self::formatBytes($used),
            'status'          => $status
        ];
    }

    /**
     * RAM / Memory usage across Linux, macOS, Windows, and PHP fallback
     */
    public static function getRamUsage(): array
    {
        $total = 0.0;
        $free  = 0.0;
        $used  = 0.0;

        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo') ?: '';
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $m)) {
                $total = (float) $m[1] * 1024;
            }
            if (preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $m)) {
                $free = (float) $m[1] * 1024;
            } elseif (preg_match('/MemFree:\s+(\d+)\s+kB/i', $meminfo, $m)) {
                $free = (float) $m[1] * 1024;
            }
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $totalStr = @shell_exec('sysctl -n hw.memsize 2>/dev/null');
            if ($totalStr) {
                $total = (float) trim($totalStr);
                $vmstat = @shell_exec('vm_stat 2>/dev/null');
                if ($vmstat && preg_match('/Pages free:\s+(\d+)/', $vmstat, $freeM) && preg_match('/page size of (\d+) bytes/', $vmstat, $psizeM)) {
                    $pageSize = (int) $psizeM[1];
                    $freePages = (int) $freeM[1];
                    $free = (float) ($freePages * $pageSize);
                }
            }
        }

        if ($total <= 0) {
            // Fallback to PHP Memory
            $phpUsed = (float) memory_get_usage(true);
            $limitStr = ini_get('memory_limit');
            $limitBytes = self::parseIniBytes($limitStr);
            if ($limitBytes <= 0) {
                $limitBytes = $phpUsed * 4;
            }
            $total = $limitBytes;
            $free  = max(0.0, $total - $phpUsed);
        }

        $used = max(0.0, $total - $free);
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0.0;

        $status = 'success';
        if ($percent >= 90) {
            $status = 'danger';
        } elseif ($percent >= 80) {
            $status = 'warning';
        }

        return [
            'total'            => $total,
            'free'             => $free,
            'used'             => $used,
            'percent'          => $percent,
            'free_percent'     => round(max(0, 100 - $percent), 1),
            'total_formatted'  => self::formatBytes($total),
            'free_formatted'   => self::formatBytes($free),
            'used_formatted'   => self::formatBytes($used),
            'php_memory_used'  => self::formatBytes(memory_get_usage(true)),
            'php_memory_limit' => ini_get('memory_limit') ?: 'Default',
            'status'           => $status
        ];
    }

    /**
     * CPU usage, load average, and core count
     */
    public static function getCpuUsage(): array
    {
        $cores = self::getCpuCores();
        $load1 = 0.0;
        $load5 = 0.0;
        $load15 = 0.0;

        if (function_exists('sys_getloadavg')) {
            $load = @sys_getloadavg();
            if (is_array($load) && count($load) >= 3) {
                $load1 = round((float) $load[0], 2);
                $load5 = round((float) $load[1], 2);
                $load15 = round((float) $load[2], 2);
            }
        }

        // Estimate CPU % based on 1m load per core
        $percent = $cores > 0 ? round(min(100.0, ($load1 / $cores) * 100), 1) : 0.0;

        $model = self::getCpuModel();

        $status = 'success';
        if ($percent >= 90) {
            $status = 'danger';
        } elseif ($percent >= 75) {
            $status = 'warning';
        }

        return [
            'cores'       => $cores,
            'load_1'      => $load1,
            'load_5'      => $load5,
            'load_15'     => $load15,
            'percent'     => $percent,
            'model'       => $model,
            'status'      => $status,
            'load_string' => "{$load1}, {$load5}, {$load15}"
        ];
    }

    /**
     * System Uptime
     */
    public static function getSystemUptime(): array
    {
        $seconds = 0;
        $bootTime = null;

        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/uptime')) {
            $uptimeContent = @file_get_contents('/proc/uptime');
            if ($uptimeContent) {
                $seconds = (int) floatval(explode(' ', trim($uptimeContent))[0]);
            }
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $boottime = @shell_exec('sysctl -n kern.boottime 2>/dev/null');
            if ($boottime && preg_match('/sec = (\d+)/', $boottime, $m)) {
                $bootTs = (int) $m[1];
                $seconds = max(0, time() - $bootTs);
                $bootTime = date('Y-m-d H:i:s', $bootTs);
            }
        }

        if ($seconds <= 0) {
            // Approximate from server start or uptime
            $seconds = max(60, time() - (int) ($_SERVER['REQUEST_TIME'] ?? time()));
        }

        if (!$bootTime && $seconds > 0) {
            $bootTime = date('Y-m-d H:i:s', time() - $seconds);
        }

        return [
            'seconds'   => $seconds,
            'formatted' => self::formatUptime($seconds),
            'boot_time' => $bootTime ?: 'N/A'
        ];
    }

    /**
     * Database Health, Size, and Engine Version
     */
    public static function getDatabaseStatus(): array
    {
        $start = microtime(true);
        $status = 'offline';
        $version = 'Unknown';
        $dbName = 'Unknown';
        $latencyMs = 0.0;
        $sizeBytes = 0;
        $tablesCount = 0;

        try {
            $res = ORM::raw_execute('SELECT VERSION() AS v, DATABASE() AS db');
            $latencyMs = round((microtime(true) - $start) * 1000, 2);
            $stmt = ORM::get_last_statement();
            if ($stmt) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $version = $row['v'] ?? 'MySQL';
                    $dbName  = $row['db'] ?? '';
                    $status  = 'online';
                }
            }

            // Database Size and Table Count
            $sizeRes = ORM::raw_execute(
                'SELECT SUM(data_length + index_length) AS size, COUNT(*) AS tables ' .
                'FROM information_schema.tables WHERE table_schema = DATABASE()'
            );
            $sizeStmt = ORM::get_last_statement();
            if ($sizeStmt) {
                $sRow = $sizeStmt->fetch(PDO::FETCH_ASSOC);
                if ($sRow) {
                    $sizeBytes = (float) ($sRow['size'] ?? 0);
                    $tablesCount = (int) ($sRow['tables'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $status = 'offline';
        }

        return [
            'status'         => $status,
            'latency_ms'     => $latencyMs,
            'version'        => $version,
            'name'           => $dbName,
            'size_bytes'     => $sizeBytes,
            'size_formatted' => self::formatBytes($sizeBytes),
            'tables_count'   => $tablesCount
        ];
    }

    /**
     * Aggregate statuses for all key services
     */
    /**
     * Aggregate statuses for all key services
     */
    public static function getServicesStatus(?array $cron = null, ?array $db = null, ?array $onlineUsers = null): array
    {
        global $config, $UPLOAD_PATH;

        if ($cron === null) {
            $cron = self::getCronStatus($UPLOAD_PATH);
        }
        if ($db === null) {
            $db = self::getDatabaseStatus();
        }
        if ($onlineUsers === null) {
            $onlineUsers = self::getOnlineUsers();
        }

        $services = [];

        // 1. Database
        $services[] = [
            'key'          => 'database',
            'name'         => Lang::T('Database Engine'),
            'type'         => 'MySQL / MariaDB',
            'status'       => $db['status'] === 'online' ? 'online' : 'offline',
            'status_label' => $db['status'] === 'online' ? Lang::T('Online') : Lang::T('Offline'),
            'badge_class'  => $db['status'] === 'online' ? 'success' : 'danger',
            'info'         => $db['status'] === 'online' ? "{$db['version']} • {$db['latency_ms']}ms • {$db['tables_count']} " . Lang::T('Tables') : Lang::T('Connection Failed'),
            'icon'         => 'fas fa-database'
        ];

        // 2. Cron Scheduler
        $cronBadge = $cron['status'] === 'success' ? 'success' : ($cron['status'] === 'warning' ? 'warning' : 'danger');
        $cronLabel = $cron['status'] === 'success' ? Lang::T('Active') : ($cron['status'] === 'warning' ? Lang::T('Delayed') : Lang::T('Inactive'));
        $services[] = [
            'key'          => 'cron',
            'name'         => Lang::T('Cron Scheduler'),
            'type'         => Lang::T('Automated Tasks'),
            'status'       => $cron['status'],
            'status_label' => $cronLabel,
            'badge_class'  => $cronBadge,
            'info'         => $cron['last_run_time'] > 0 ? Lang::T('Ran') . ' ' . $cron['time_ago'] . " ({$cron['run_date']})" : Lang::T('Never run'),
            'icon'         => 'fas fa-clock'
        ];

        // 3. Web Server & PHP
        $services[] = [
            'key'          => 'webserver',
            'name'         => Lang::T('Web Server & PHP'),
            'type'         => Lang::T('Runtime Engine'),
            'status'       => 'online',
            'status_label' => Lang::T('Online'),
            'badge_class'  => 'success',
            'info'         => 'PHP ' . PHP_VERSION . ' • ' . (isset($_SERVER['SERVER_SOFTWARE']) ? explode(' ', $_SERVER['SERVER_SOFTWARE'])[0] : php_sapi_name()),
            'icon'         => 'fas fa-server'
        ];

        // 4. Redis Cache
        $redisOnline = false;
        $redisInfo = Lang::T('Disabled (File Cache)');
        if (class_exists('RedisCache')) {
            if (RedisCache::isConnected()) {
                $redisOnline = true;
                $stats = RedisCache::getStats();
                $redisInfo = 'Redis v' . ($stats['version'] ?? 'N/A') . ' • ' . ($stats['used_memory'] ?? 'OK') . ' • Uptime: ' . ($stats['uptime'] ?? 'N/A');
            } elseif (!empty($config['redis_enabled'])) {
                $redisInfo = Lang::T('Configured but unreachable');
            }
        }
        $services[] = [
            'key'          => 'redis',
            'name'         => Lang::T('Redis Cache'),
            'type'         => Lang::T('In-Memory Accelerator'),
            'status'       => $redisOnline ? 'online' : (!empty($config['redis_enabled']) ? 'offline' : 'disabled'),
            'status_label' => $redisOnline ? Lang::T('Active') : (!empty($config['redis_enabled']) ? Lang::T('Offline') : Lang::T('Disabled')),
            'badge_class'  => $redisOnline ? 'success' : (!empty($config['redis_enabled']) ? 'danger' : 'secondary'),
            'info'         => $redisInfo,
            'icon'         => 'fas fa-bolt'
        ];

        // 5. FreeRADIUS Server AAA Engine
        $radiusEnabled = !empty($config['radius_enable']);
        $radiusOnline = false;
        $radiusInfo = Lang::T('RADIUS Not Enabled');
        if ($radiusEnabled) {
            try {
                $count = (int) ORM::for_table('nas', 'radius')->count();
                $radiusOnline = true;
                $radiusInfo = "Online • {$count} " . Lang::T('NAS Configured');
            } catch (Throwable $e) {
                $radiusInfo = Lang::T('Connection Error');
            }
        }
        $services[] = [
            'key'          => 'radius',
            'name'         => Lang::T('FreeRADIUS AAA Engine'),
            'type'         => Lang::T('RADIUS Authentication & Accounting'),
            'status'       => $radiusEnabled ? ($radiusOnline ? 'online' : 'offline') : 'disabled',
            'status_label' => $radiusEnabled ? ($radiusOnline ? Lang::T('Online') : Lang::T('Offline')) : Lang::T('Disabled'),
            'badge_class'  => $radiusEnabled ? ($radiusOnline ? 'success' : 'danger') : 'secondary',
            'info'         => $radiusInfo,
            'icon'         => 'fas fa-shield-alt'
        ];

        return $services;
    }

    /**
     * Get real-time online users across all platforms:
     * 1. Mikrotik API (Hotspot + PPPoE active via RouterOS API)
     * 2. Radius REST (rad_acct in database via radius.php)
     * 3. Radius MySQL (radacct in database)
     */
    public static function getOnlineUsers(): array
    {
        global $config;

        $radiusEnabled = !empty($config['radius_enable']);

        // 1. Radius REST Online Users (table rad_acct in database)
        // Sessions updated by radius.php with acctstatustype IN ('Start', 'Interim-Update') OR NULL, and != 'Stop'
        $restActive = "(acctstatustype = 'Start' OR acctstatustype = 'Interim-Update' OR acctstatustype IS NULL)";
        $restNotStopped = "(acctstatustype != 'Stop' OR acctstatustype IS NULL)";
        $restOnline = 0;
        $restUsers = 0;
        try {
            $restOnline = (int) ORM::for_table('rad_acct')
                ->where_raw($restActive)
                ->where_raw($restNotStopped)
                ->count();
        } catch (Throwable $e) {
            $restOnline = 0;
        }
        try {
            $row = ORM::for_table('rad_acct')
                ->select_expr('COUNT(DISTINCT username)', 'n')
                ->where_raw($restActive)
                ->where_raw($restNotStopped)
                ->find_one();
            $restUsers = $row ? (int) $row->n : 0;
        } catch (Throwable $e) {
            $restUsers = 0;
        }

        // 2. Radius MySQL Online Users (table radacct in database)
        // Sessions in FreeRADIUS with acctstoptime IS NULL. Each figure has its own try so one failure keeps the other.
        $mysqlOnline = 0;
        $mysqlUsers = 0;
        if ($radiusEnabled) {
            foreach ([['radacct', 'radius'], ['radacct', null]] as [$tbl, $conn]) {
                try {
                    $q = $conn ? ORM::for_table($tbl, $conn) : ORM::for_table($tbl);
                    $mysqlOnline = (int) $q->where_null('acctstoptime')->count();
                    break;
                } catch (Throwable $e) {
                    $mysqlOnline = 0;
                }
            }
            foreach ([['radacct', 'radius'], ['radacct', null]] as [$tbl, $conn]) {
                try {
                    $q = $conn ? ORM::for_table($tbl, $conn) : ORM::for_table($tbl);
                    $row = $q->select_expr('COUNT(DISTINCT username)', 'n')->where_null('acctstoptime')->find_one();
                    $mysqlUsers = $row ? (int) $row->n : 0;
                    break;
                } catch (Throwable $e) {
                    $mysqlUsers = 0;
                }
            }
        }

        // 3. Mikrotik API Online Users (Hotspot active + PPPoE active via RouterOS API), cached for a short time
        $mt = self::getMikrotikOnline();
        $mikrotikHotspot = $mt['hotspot'];
        $mikrotikPppoe = $mt['pppoe'];
        $routersCount = $mt['routers'];
        $mikrotikOnline = $mikrotikHotspot + $mikrotikPppoe;

        // Fetch recent active sessions for detailed tab view
        $restSessions = [];
        try {
            $restSessions = ORM::for_table('rad_acct')
                ->select_many('radacctid', 'username', 'nasipaddress', 'framedipaddress', 'callingstationid', 'acctstarttime', 'acctstatustype')
                ->where_raw("(acctstatustype = 'Start' OR acctstatustype = 'Interim-Update' OR acctstatustype IS NULL)")
                ->where_raw("(acctstatustype != 'Stop' OR acctstatustype IS NULL)")
                ->order_by_desc('radacctid')
                ->limit(10)
                ->find_array();
        } catch (Throwable $e) {}

        $mysqlSessions = [];
        if ($radiusEnabled) {
            try {
                $mysqlSessions = ORM::for_table('radacct', 'radius')
                    ->select_many('radacctid', 'username', 'nasipaddress', 'framedipaddress', 'callingstationid', 'acctstarttime')
                    ->where_null('acctstoptime')
                    ->order_by_desc('radacctid')
                    ->limit(10)
                    ->find_array();
            } catch (Throwable $e) {
                try {
                    $mysqlSessions = ORM::for_table('radacct')
                        ->select_many('radacctid', 'username', 'nasipaddress', 'framedipaddress', 'callingstationid', 'acctstarttime')
                        ->where_null('acctstoptime')
                        ->order_by_desc('radacctid')
                        ->limit(10)
                        ->find_array();
                } catch (Throwable $e2) {}
            }
        }

        $totalOnline = $mikrotikOnline + $restOnline + $mysqlOnline;

        return [
            'total'             => $totalOnline,
            'mikrotik'          => $mikrotikOnline,
            'mikrotik_hotspot'  => $mikrotikHotspot,
            'mikrotik_pppoe'    => $mikrotikPppoe,
            'rest'              => $restOnline,
            'rest_users'        => $restUsers,
            'rest_sessions'     => $restSessions,
            'mysql'             => $mysqlOnline,
            'mysql_users'       => $mysqlUsers,
            'mysql_sessions'    => $mysqlSessions,
            'radius_total'      => $restOnline + $mysqlOnline,
            'radius_enabled'    => $radiusEnabled,
            'routers_checked'   => $routersCount
        ];
    }

    /**
     * Hotspot + PPP active-session counts from every online router. Opening RouterOS connections on each
     * dashboard load/refresh is slow, so the totals are cached for 30 seconds (Redis when connected, otherwise a file).
     * Mikrotik::getClient($ip, $user, $pass, $port, $timeout) accepts the 1-second timeout used here.
     */
    private static function getMikrotikOnline(): array
    {
        $compute = function (): array {
            $out = ['hotspot' => 0, 'pppoe' => 0, 'routers' => 0];
            try {
                foreach (ORM::for_table('tbl_routers')->where('enabled', 1)->find_array() as $r) {
                    if (strcasecmp($r['status'] ?? '', 'online') !== 0) {
                        continue;
                    }
                    $out['routers']++;
                    try {
                        $client = Mikrotik::getClient($r['ip_address'], $r['username'], $r['password'], $r['port'] ?? null, 1);
                        if (!$client) {
                            continue;
                        }
                        try {
                            $reqH = new PEAR2\Net\RouterOS\Request('/ip/hotspot/active/print');
                            $reqH->setArgument('.proplist', '.id');
                            $out['hotspot'] += count($client->sendSync($reqH));
                        } catch (Throwable $eH) {
                        }
                        try {
                            $reqP = new PEAR2\Net\RouterOS\Request('/ppp/active/print');
                            $reqP->setArgument('.proplist', '.id');
                            $out['pppoe'] += count($client->sendSync($reqP));
                        } catch (Throwable $eP) {
                        }
                    } catch (Throwable $e) {
                    }
                }
            } catch (Throwable $e) {
            }
            return $out;
        };

        $ttl = 30;
        if (class_exists('RedisCache') && RedisCache::isConnected()) {
            $res = RedisCache::remember('dash:mikrotik_online', $ttl, $compute);
            return is_array($res) ? $res : $compute();
        }
        global $CACHE_PATH;
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . 'mikrotik_online.cache';
        if (is_file($file) && (time() - filemtime($file)) < $ttl) {
            $cached = json_decode((string) @file_get_contents($file), true);
            if (is_array($cached) && isset($cached['hotspot'], $cached['pppoe'], $cached['routers'])) {
                return $cached;
            }
        }
        $res = $compute();
        @file_put_contents($file, json_encode($res), LOCK_EX);
        return $res;
    }

    /**
     * Helpers
     */
    public static function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function formatUptime(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'N/A';
        }
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $mins = floor(($seconds % 3600) / 60);
        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days}d";
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = "{$hours}h";
        }
        $parts[] = "{$mins}m";
        return implode(' ', $parts);
    }

    public static function timeAgo(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return Lang::T('Never');
        }
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return $diff . 's ' . Lang::T('ago');
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ' . Lang::T('ago');
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ' . Lang::T('ago');
        }
        return floor($diff / 86400) . 'd ' . Lang::T('ago');
    }

    private static function getCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo') ?: '';
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            if (!empty($matches[0])) {
                return max(1, count($matches[0]));
            }
        }
        if (PHP_OS_FAMILY === 'Darwin') {
            $cores = @shell_exec('sysctl -n hw.ncpu 2>/dev/null');
            if ($cores) {
                return max(1, (int) trim($cores));
            }
        }
        if (PHP_OS_FAMILY === 'Windows') {
            $cores = getenv('NUMBER_OF_PROCESSORS');
            if ($cores) {
                return max(1, (int) $cores);
            }
        }
        return 2;
    }

    private static function getCpuModel(): string
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo') ?: '';
            if (preg_match('/model name\s*:\s*(.+)/i', $cpuinfo, $m)) {
                return trim($m[1]);
            }
        }
        if (PHP_OS_FAMILY === 'Darwin') {
            $model = @shell_exec('sysctl -n machdep.cpu.brand_string 2>/dev/null');
            if ($model) {
                return trim($model);
            }
        }
        return 'Standard Processor (' . PHP_OS . ')';
    }

    private static function parseIniBytes(string $val): float
    {
        $val = trim($val);
        if ($val === '-1') {
            return -1;
        }
        $last = strtolower(substr($val, -1));
        $num = (float) $val;
        switch ($last) {
            case 'g':
                $num *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $num *= 1024 * 1024;
                break;
            case 'k':
                $num *= 1024;
                break;
        }
        return $num;
    }
}
