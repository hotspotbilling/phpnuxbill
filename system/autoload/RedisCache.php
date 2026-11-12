<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  Redis Cache Manager & Helper
 **/

class RedisCache
{
    private static ?Redis $client = null;
    private static bool $initialized = false;
    private static string $prefix = 'nuxbill:';

    /**
     * Get or initialize Redis client instance
     */
    public static function instance(): ?Redis
    {
        if (self::$initialized) {
            return self::$client;
        }

        self::$initialized = true;

        global $config;

        // If Redis is disabled in settings or extension not loaded, do not connect
        if (empty($config['redis_enabled']) || !extension_loaded('redis')) {
            self::$client = null;
            return null;
        }

        $host = !empty($config['redis_host']) ? trim($config['redis_host']) : '127.0.0.1';
        $port = !empty($config['redis_port']) ? (int) $config['redis_port'] : 6379;
        $pass = !empty($config['redis_pass']) ? $config['redis_pass'] : null;

        try {
            $redis = new Redis();
            // 0.5s timeout prevents blocking page loads if Redis is unresponsive
            $connected = $redis->connect($host, $port, 0.5);
            if ($connected) {
                if (!empty($pass)) {
                    $redis->auth($pass);
                }
                self::$client = $redis;
                return self::$client;
            }
        } catch (Throwable $e) {
            // Silently fall back to MySQL if connection fails
            self::$client = null;
        }

        return null;
    }

    /**
     * Check if Redis is actively connected
     */
    public static function isConnected(): bool
    {
        $redis = self::instance();
        if (!$redis) {
            return false;
        }
        try {
            return $redis->ping() ? true : false;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Check if dashboard caching is active
     */
    public static function isDashboardCacheEnabled(): bool
    {
        global $config;
        return !empty($config['redis_enabled']) && !empty($config['redis_cache_dashboard']) && self::isConnected();
    }

    /**
     * Check if RADIUS caching is active
     */
    public static function isRadiusCacheEnabled(): bool
    {
        global $config;
        return !empty($config['redis_enabled']) && !empty($config['redis_cache_radius']) && self::isConnected();
    }

    /**
     * Dashboard data is cached in files (system/cache/dashboard/dash_*.temp, same TTL) when Redis cannot do it: Redis not
     * enabled, or enabled but unreachable. Not used when an admin turned Redis on and dashboard caching off.
     */
    public static function isDashboardFileCacheEnabled(): bool
    {
        global $config;
        if (self::isDashboardCacheEnabled()) {
            return false;
        }
        return empty($config['redis_enabled']) || !empty($config['redis_cache_dashboard']);
    }

    /** File-based cache-aside, same contract as remember(). The "?refresh=1" button deletes the *.temp files. */
    public static function rememberFile(string $key, int $ttl, callable $callback)
    {
        // system/cache/dashboard/ holds revenue and customer figures: it gets a deny-all .htaccess and an empty index.html
        $file = Extensions::privateDir('dashboard') . DIRECTORY_SEPARATOR . 'dash_' . md5($key) . '.temp';
        if (is_file($file) && (time() - (int) filemtime($file)) < $ttl) {
            $raw = @file_get_contents($file);
            if ($raw !== false && $raw !== '') {
                $cached = @unserialize($raw, ['allowed_classes' => false]);
                if ($cached !== false || $raw === serialize(false)) {
                    return $cached;
                }
            }
        }
        $result = $callback();
        if ($result !== null && is_dir(dirname($file)) && is_writable(dirname($file))) {
            $tmp = $file . '.' . getmypid() . '.tmp';
            if (@file_put_contents($tmp, serialize($result), LOCK_EX) !== false) {
                @rename($tmp, $file); // readers never see a half-written file
            } else {
                @unlink($tmp);
            }
        }
        return $result;
    }

    /**
     * Get dashboard TTL (in seconds)
     */
    public static function getDashboardTTL(): int
    {
        global $config;
        $ttl = (int) ($config['redis_dashboard_ttl'] ?? 60);
        return $ttl > 0 ? $ttl : 60;
    }

    /**
     * Get RADIUS TTL (in seconds)
     */
    public static function getRadiusTTL(): int
    {
        global $config;
        $ttl = (int) ($config['redis_radius_ttl'] ?? 60);
        return $ttl > 0 ? $ttl : 60;
    }

    /**
     * Cache-aside helper: Return cached value or execute callback and cache result
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        $redis = self::instance();
        $fullKey = self::$prefix . $key;

        if ($redis) {
            try {
                $cached = $redis->get($fullKey);
                if ($cached !== false && $cached !== null) {
                    return unserialize($cached, ['allowed_classes' => false]);
                }
            } catch (Throwable $e) {
                // Ignore cache read failures and fall through to callback
            }
        }

        $result = $callback();

        if ($redis && $result !== null) {
            try {
                $redis->setex($fullKey, $ttl, serialize($result));
            } catch (Throwable $e) {
                // Ignore cache write errors
            }
        }

        return $result;
    }

    /**
     * Get cached item
     */
    public static function get(string $key)
    {
        $redis = self::instance();
        if (!$redis) {
            return null;
        }
        try {
            $data = $redis->get(self::$prefix . $key);
            return $data !== false ? unserialize($data, ['allowed_classes' => false]) : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Set cache item
     */
    public static function set(string $key, $value, int $ttl = 60): bool
    {
        $redis = self::instance();
        if (!$redis) {
            return false;
        }
        try {
            return $redis->setex(self::$prefix . $key, $ttl, serialize($value));
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Delete one or more keys
     */
    public static function del(string ...$keys): void
    {
        $redis = self::instance();
        if (!$redis || empty($keys)) {
            return;
        }
        try {
            $prefixed = array_map(fn($k) => self::$prefix . $k, $keys);
            $redis->del($prefixed);
        } catch (Throwable $e) {}
    }

    /**
     * Delete keys matching a pattern (e.g. 'dash:*')
     */
    public static function flushPattern(string $pattern): int
    {
        $redis = self::instance();
        if (!$redis) {
            return 0;
        }
        try {
            $deleted = 0;
            $it = null;
            do {
                $keys = $redis->scan($it, self::$prefix . $pattern, 100);
                if (is_array($keys) && !empty($keys)) {
                    $deleted += (int) $redis->unlink($keys);
                }
            } while ($it > 0);
            return $deleted;
        } catch (Throwable $e) {}
        return 0;
    }

    /**
     * Invalidate all RADIUS customer cache entries for a username
     */
    public static function forgetRadiusCustomer(?string $username): void
    {
        if ($username === null || $username === '') {
            return;
        }
        $h = md5($username);
        self::del('rad:cust_chap:' . $h, 'rad:cust_pppoe:' . $h, 'rad:cust_auth:' . $h);
    }

    /**
     * Flush all keys under nuxbill prefix
     */
    public static function flushAll(): int
    {
        return self::flushPattern('*');
    }

    /**
     * Test connection parameters
     */
    public static function testConnection(string $host, int $port, ?string $pass = null): array
    {
        if (!extension_loaded('redis')) {
            return [
                'success' => false,
                'message' => 'PHP Redis extension is not installed. Run "sudo apt install php-redis" on Ubuntu.'
            ];
        }

        try {
            $redis = new Redis();
            $connected = @$redis->connect($host, $port, 1.5);
            if (!$connected) {
                return [
                    'success' => false,
                    'message' => "Unable to connect to Redis server at {$host}:{$port}."
                ];
            }
            if (!empty($pass)) {
                if (!@$redis->auth($pass)) {
                    return [
                        'success' => false,
                        'message' => 'Redis authentication failed: Invalid password.'
                    ];
                }
            }
            $ping = $redis->ping();
            return [
                'success' => true,
                'message' => "Connected successfully! Ping response: " . (is_string($ping) ? $ping : 'PONG')
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Redis Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Collect Redis info and metrics
     */
    public static function getStats(): array
    {
        $stats = [
            'extension_loaded' => extension_loaded('redis'),
            'connected' => false,
            'version' => 'N/A',
            'uptime' => 'N/A',
            'used_memory' => '0 B',
            'peak_memory' => '0 B',
            'total_keys' => 0,
            'clients' => 0,
            'hits' => 0,
            'misses' => 0,
            'hit_ratio' => 0,
            'used_bytes' => 0,
            'peak_bytes' => 0
        ];

        if (!$stats['extension_loaded']) {
            return $stats;
        }

        $redis = self::instance();
        if (!$redis) {
            return $stats;
        }

        try {
            $info = $redis->info();
            $stats['connected'] = true;
            $stats['version'] = $info['redis_version'] ?? 'Unknown';

            $uptimeSeconds = (int) ($info['uptime_in_seconds'] ?? 0);
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $mins = floor(($uptimeSeconds % 3600) / 60);
            $stats['uptime'] = ($days > 0 ? "{$days}d " : "") . "{$hours}h {$mins}m";

            $stats['used_memory'] = $info['used_memory_human'] ?? '0 B';
            $stats['peak_memory'] = $info['used_memory_peak_human'] ?? '0 B';
            $stats['clients'] = (int) ($info['connected_clients'] ?? 0);
            $stats['used_bytes'] = (int) ($info['used_memory'] ?? 0); // for the memory bar on the Redis page
            $stats['peak_bytes'] = (int) ($info['used_memory_peak'] ?? 0);

            $hits = (int) ($info['keyspace_hits'] ?? 0);
            $misses = (int) ($info['keyspace_misses'] ?? 0);
            $total = $hits + $misses;
            $stats['hits'] = $hits;
            $stats['misses'] = $misses;
            $stats['hit_ratio'] = $total > 0 ? round(($hits / $total) * 100, 1) : 0;

            $totalKeys = 0;
            $it = null;
            do {
                $batch = $redis->scan($it, self::$prefix . '*', 100);
                if (is_array($batch)) {
                    $totalKeys += count($batch);
                }
            } while ($it > 0);
            $stats['total_keys'] = $totalKeys;
        } catch (Throwable $e) {}

        return $stats;
    }

    /**
     * Compute dynamic dashboard metrics
     */
    public static function calculateDynamicDashboard(): array
    {
        global $config, $radius_host;
        $today = date('Y-m-d');
        $thisMonthStart = date('Y-m-01');
        $lastMonthStart = date('Y-m-01', strtotime('-1 month', strtotime($thisMonthStart)));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month', strtotime($thisMonthStart)));

        // 1. Total Customers
        $c_all = (int) ORM::for_table('tbl_customers')->count();
        $c_this_month = (int) ORM::for_table('tbl_customers')->where_gte('created_at', $thisMonthStart . ' 00:00:00')->count();
        $c_last_month = (int) ORM::for_table('tbl_customers')->where_gte('created_at', $lastMonthStart . ' 00:00:00')->where_lte('created_at', $lastMonthEnd . ' 23:59:59')->count();
        $c_percentage_change = $c_last_month > 0 ? round((($c_this_month - $c_last_month) / $c_last_month) * 100, 1) : ($c_this_month > 0 ? 100 : 0);

        // 2. Active Connections (User Recharges)
        $u_act = (int) ORM::for_table('tbl_user_recharges')->where('status', 'on')->count();
        $u_all = (int) ORM::for_table('tbl_user_recharges')->count();
        $u_act_pct = $u_all > 0 ? round(($u_act / $u_all) * 100, 1) : 0;
        $u_expired = max(0, $u_all - $u_act);

        // 3. Monthly Revenue
        $imonth = (float) (ORM::for_table('tbl_transactions')
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->where_gte('recharged_on', $thisMonthStart)
            ->where_lte('recharged_on', $today)
            ->sum('price') ?: 0);
        $imonth_last = (float) (ORM::for_table('tbl_transactions')
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->where_gte('recharged_on', $lastMonthStart)
            ->where_lte('recharged_on', $lastMonthEnd)
            ->sum('price') ?: 0);
        $rev_percentage_change = $imonth_last > 0 ? round((($imonth - $imonth_last) / $imonth_last) * 100, 1) : ($imonth > 0 ? 100 : 0);

        // 4. Today Revenue & Transactions Count
        $iday = (float) (ORM::for_table('tbl_transactions')
            ->where('recharged_on', $today)
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->sum('price') ?: 0);
        $iday_count = (int) ORM::for_table('tbl_transactions')
            ->where('recharged_on', $today)
            ->count();

        // 5. Routers Status
        $routers_list = ORM::for_table('tbl_routers')
            ->select_many('id', 'name', 'ip_address', 'status', 'last_seen', 'enabled')
            ->order_by_desc('status')
            ->order_by_asc('name')
            ->find_array();
        $routers_total = count($routers_list);
        $routers_online = 0;
        $routers_map = [];
        foreach ($routers_list as $r) {
            $routers_map[$r['name']] = $r;
            if (!empty($r['ip_address'])) {
                $routers_map[$r['ip_address']] = $r;
            }
            if (strcasecmp($r['status'] ?? '', 'online') === 0) {
                $routers_online++;
            }
        }
        $routers_offline = max(0, $routers_total - $routers_online);

        // 6. Radius Status & NAS Clients
        global $radius_host;
        $radius_enabled = !empty($config['radius_enable']);
        $radius_online = false;
        $radius_nas_list = [];
        $radius_nas_count = 0;
        $radius_nas_online = 0;
        $radius_active_sessions = 0;

        if ($radius_enabled) {
            try {
                $nas_records = ORM::for_table('nas', 'radius')
                    ->select_many('id', 'nasname', 'shortname', 'type', 'routers')
                    ->order_by_asc('shortname')
                    ->find_array();
                $radius_online = true;
                $radius_nas_count = count($nas_records);

                foreach ($nas_records as $nas) {
                    $nas_status = 'Online';
                    if (!empty($nas['routers']) && isset($routers_map[$nas['routers']])) {
                        $nas_status = $routers_map[$nas['routers']]['status'] ?? 'Online';
                    } elseif (!empty($nas['nasname']) && isset($routers_map[$nas['nasname']])) {
                        $nas_status = $routers_map[$nas['nasname']]['status'] ?? 'Online';
                    }
                    $nas['status'] = $nas_status;
                    if (strcasecmp($nas_status, 'online') === 0) {
                        $radius_nas_online++;
                    }
                    $radius_nas_list[] = $nas;
                }

                try {
                    $radius_active_sessions = (int) ORM::for_table('radacct', 'radius')
                        ->where_null('acctstoptime')
                        ->count();
                } catch (Throwable $e) {
                    $radius_active_sessions = 0;
                }
            } catch (Throwable $e) {
                // no radius database (freeradius-rest setups): judge by FreeRADIUS' requests to radius.php instead
                $radius_online = SystemInfo::radiusStatus()['online'];
            }
        }

        // Combined Network calculations (Routers + Radius AAA Server + NAS Clients)
        $total_network_nodes = $routers_total + ($radius_enabled ? 1 : 0) + $radius_nas_count;
        $online_network_nodes = $routers_online + ($radius_enabled && $radius_online ? 1 : 0) + $radius_nas_online;
        $offline_network_nodes = max(0, $total_network_nodes - $online_network_nodes);
        $network_uptime = $total_network_nodes > 0 ? round(($online_network_nodes / $total_network_nodes) * 100, 1) : 100;

        // Charts: Sales Report Data (Day, Week, Month, Year)
        $daysMap = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $daysMap[$d] = ['label' => date('M d', strtotime($d)), 'value' => 0.0, 'count' => 0];
        }
        $dayTrx = ORM::for_table('tbl_transactions')
            ->select('recharged_on')
            ->select_expr('SUM(price)', 'total')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('recharged_on', date('Y-m-d', strtotime('-29 days')))
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->group_by('recharged_on')
            ->find_array();
        foreach ($dayTrx as $r) {
            if (isset($daysMap[$r['recharged_on']])) {
                $daysMap[$r['recharged_on']]['value'] = (float)$r['total'];
                $daysMap[$r['recharged_on']]['count'] = (int)$r['count'];
            }
        }

        $weeksMap = [];
        for ($i = 11; $i >= 0; $i--) {
            $wTime = strtotime("-$i weeks");
            $wKey = date('o-W', $wTime);
            $weeksMap[$wKey] = ['label' => 'Wk ' . date('W', $wTime), 'value' => 0.0, 'count' => 0];
        }
        $weekTrx = ORM::for_table('tbl_transactions')
            ->select_expr("DATE_FORMAT(recharged_on, '%x-%v')", 'week_key')
            ->select_expr('SUM(price)', 'total')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('recharged_on', date('Y-m-d', strtotime('-12 weeks')))
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->group_by_expr("DATE_FORMAT(recharged_on, '%x-%v')")
            ->find_array();
        foreach ($weekTrx as $r) {
            if (isset($weeksMap[$r['week_key']])) {
                $weeksMap[$r['week_key']]['value'] = (float)$r['total'];
                $weeksMap[$r['week_key']]['count'] = (int)$r['count'];
            }
        }

        $monthsMap = [];
        for ($i = 11; $i >= 0; $i--) {
            $mKey = date('Y-m', strtotime("-$i months", strtotime($thisMonthStart)));
            $monthsMap[$mKey] = ['label' => date('M Y', strtotime($mKey . '-01')), 'value' => 0.0, 'count' => 0, 'new_customers' => 0, 'churned' => 0];
        }
        $monthTrx = ORM::for_table('tbl_transactions')
            ->select_expr("DATE_FORMAT(recharged_on, '%Y-%m')", 'month_key')
            ->select_expr('SUM(price)', 'total')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('recharged_on', date('Y-m-d', strtotime('-11 months', strtotime($thisMonthStart))))
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->group_by_expr("DATE_FORMAT(recharged_on, '%Y-%m')")
            ->find_array();
        foreach ($monthTrx as $r) {
            if (isset($monthsMap[$r['month_key']])) {
                $monthsMap[$r['month_key']]['value'] = (float)$r['total'];
                $monthsMap[$r['month_key']]['count'] = (int)$r['count'];
            }
        }

        $custGrowth = ORM::for_table('tbl_customers')
            ->select_expr("DATE_FORMAT(created_at, '%Y-%m')", 'month_key')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('created_at', date('Y-m-01 00:00:00', strtotime('-11 months', strtotime($thisMonthStart))))
            ->group_by_expr("DATE_FORMAT(created_at, '%Y-%m')")
            ->find_array();
        foreach ($custGrowth as $r) {
            if (isset($monthsMap[$r['month_key']])) {
                $monthsMap[$r['month_key']]['new_customers'] = (int)$r['count'];
            }
        }

        $yearsMap = [];
        $currentYear = (int)date('Y');
        for ($y = $currentYear - 4; $y <= $currentYear; $y++) {
            $yearsMap[$y] = ['label' => (string)$y, 'value' => 0.0, 'count' => 0];
        }
        $yearTrx = ORM::for_table('tbl_transactions')
            ->select_expr("YEAR(recharged_on)", 'year_key')
            ->select_expr('SUM(price)', 'total')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('recharged_on', ($currentYear - 4) . '-01-01')
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->group_by_expr("YEAR(recharged_on)")
            ->find_array();
        foreach ($yearTrx as $r) {
            $yKey = (int)$r['year_key'];
            if (isset($yearsMap[$yKey])) {
                $yearsMap[$yKey]['value'] = (float)$r['total'];
                $yearsMap[$yKey]['count'] = (int)$r['count'];
            }
        }

        // Payment Methods Breakdown (last 90 days)
        // A method is stored as "<gateway> - <channel>", and the channel is the voucher code for a voucher (or the admin's name for cash),
        // so group on the gateway part: all vouchers are one "Voucher" bar instead of one bar per code.
        $paymentMethods = ORM::for_table('tbl_transactions')
            ->select_expr("TRIM(SUBSTRING_INDEX(method, ' - ', 1))", 'service')
            ->select_expr('SUM(price)', 'total')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('recharged_on', date('Y-m-d', strtotime('-90 days')))
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->group_by_expr("TRIM(SUBSTRING_INDEX(method, ' - ', 1))")
            ->order_by_desc('total')
            ->find_array();
        $pmLabels = [];
        $pmTotals = [];
        $trxCounts = [];
        $pmLimit = 10; // the rest is summed into one "Other" bar so the chart stays readable
        foreach ($paymentMethods as $i => $pm) {
            if ($i >= $pmLimit) {
                if (count($pmLabels) === $pmLimit) {
                    $pmLabels[] = Lang::T('Other');
                    $pmTotals[] = 0.0;
                    $trxCounts[] = 0;
                }
                $pmTotals[$pmLimit] += (float)$pm['total'];
                $trxCounts[$pmLimit] += (int)$pm['count'];
                continue;
            }
            $pmLabels[] = $pm['service'] !== '' && $pm['service'] !== null ? $pm['service'] : 'Direct';
            $pmTotals[] = (float)$pm['total'];
            $trxCounts[] = (int)$pm['count'];
        }
        if (empty($pmLabels)) {
            $pmLabels = ['Cash', 'Transfer'];
            $pmTotals = [0, 0];
            $trxCounts = [0, 0];
        }

        // Routers Traffic & Users
        $routerUsers = ORM::for_table('tbl_user_recharges')
            ->select('routers')
            ->select_expr('COUNT(*)', 'count')
            ->where('status', 'on')
            ->group_by('routers')
            ->order_by_desc('count')
            ->limit(5)
            ->find_array();
        $ruLabels = [];
        $ruCounts = [];
        foreach ($routerUsers as $ru) {
            $ruLabels[] = !empty($ru['routers']) ? $ru['routers'] : 'Default';
            $ruCounts[] = (int)$ru['count'];
        }
        if (empty($ruLabels)) {
            $ruLabels = ['None'];
            $ruCounts = [0];
        }

        // Day of Week Activity (last 90 days)
        $dowNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dowCounts = array_fill(0, 7, 0);
        $dowTrx = ORM::for_table('tbl_transactions')
            ->select_expr('DAYOFWEEK(recharged_on)', 'dow')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('recharged_on', date('Y-m-d', strtotime('-90 days')))
            ->group_by_expr('DAYOFWEEK(recharged_on)')
            ->find_array();
        foreach ($dowTrx as $d) {
            $idx = (int)$d['dow'] - 1;
            if ($idx >= 0 && $idx < 7) {
                $dowCounts[$idx] = (int)$d['count'];
            }
        }

        // Plans Distribution by Type
        $planTypes = ORM::for_table('tbl_plans')
            ->select('type')
            ->select_expr('COUNT(*)', 'count')
            ->group_by('type')
            ->find_array();
        $ptLabels = [];
        $ptCounts = [];
        foreach ($planTypes as $pt) {
            $ptLabels[] = !empty($pt['type']) ? $pt['type'] : 'Other';
            $ptCounts[] = (int)$pt['count'];
        }
        if (empty($ptLabels)) {
            $ptLabels = ['Hotspot', 'PPPoE', 'VPN'];
            $ptCounts = [0, 0, 0];
        }

        // Top Selling Plans (last 90 days)
        $topPlans = ORM::for_table('tbl_transactions')
            ->select('plan_name')
            ->select_expr('COUNT(*)', 'count')
            ->select_expr('SUM(price)', 'total')
            ->where_gte('recharged_on', date('Y-m-d', strtotime('-90 days')))
            ->group_by('plan_name')
            ->order_by_desc('count')
            ->limit(5)
            ->find_array();
        $tpLabels = [];
        $tpCounts = [];
        $tpTotals = [];
        foreach ($topPlans as $tp) {
            $tpLabels[] = !empty($tp['plan_name']) ? $tp['plan_name'] : 'Unnamed';
            $tpCounts[] = (int)$tp['count'];
            $tpTotals[] = (float)$tp['total'];
        }
        if (empty($tpLabels)) {
            $tpLabels = ['None'];
            $tpCounts = [0];
            $tpTotals = [0];
        }

        // Hourly Activity (last 30 days)
        $hoursLabels = [];
        $hoursCounts = array_fill(0, 24, 0);
        for ($h = 0; $h < 24; $h++) {
            $hoursLabels[] = sprintf('%02d:00', $h);
        }
        $hourTrx = ORM::for_table('tbl_transactions')
            ->select_expr("HOUR(COALESCE(time, recharged_time, '00:00:00'))", 'hr')
            ->select_expr('COUNT(*)', 'count')
            ->where_gte('recharged_on', date('Y-m-d', strtotime('-30 days')))
            ->group_by_expr("HOUR(COALESCE(time, recharged_time, '00:00:00'))")
            ->find_array();
        $peakCount = 0;
        $peakHourIdx = 12;
        foreach ($hourTrx as $h) {
            $hr = (int)$h['hr'];
            if ($hr >= 0 && $hr < 24) {
                $cnt = (int)$h['count'];
                $hoursCounts[$hr] = $cnt;
                if ($cnt > $peakCount) {
                    $peakCount = $cnt;
                    $peakHourIdx = $hr;
                }
            }
        }
        $peak_hour_str = date('g:i A', strtotime("$peakHourIdx:00"));

        // Peak Usage (3-hour blocks)
        $blockLabels = ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM'];
        $blockCounts = [0, 0, 0, 0, 0, 0, 0, 0];
        for ($h = 0; $h < 24; $h++) {
            $bIdx = (int)floor($h / 3);
            $blockCounts[$bIdx] += $hoursCounts[$h];
        }

        // Equipment Status
        $equipmentData = [
            'labels' => ['Routers', 'Plans', 'Pools', 'Bandwidths', 'Vouchers', 'Customers'],
            'values' => [
                (int)ORM::for_table('tbl_routers')->count(),
                (int)ORM::for_table('tbl_plans')->count(),
                (int)ORM::for_table('tbl_pool')->count(),
                (int)ORM::for_table('tbl_bandwidth')->count(),
                (int)ORM::for_table('tbl_voucher')->count(),
                (int)ORM::for_table('tbl_customers')->count()
            ]
        ];
        if ($radius_enabled) {
            $equipmentData['labels'][] = 'Radius NAS';
            $equipmentData['values'][] = $radius_nas_count;
        }

        $allDashboardData = [
            'sales' => [
                'day' => [
                    'labels' => array_column(array_values($daysMap), 'label'),
                    'values' => array_column(array_values($daysMap), 'value'),
                    'counts' => array_column(array_values($daysMap), 'count')
                ],
                'week' => [
                    'labels' => array_column(array_values($weeksMap), 'label'),
                    'values' => array_column(array_values($weeksMap), 'value'),
                    'counts' => array_column(array_values($weeksMap), 'count')
                ],
                'month' => [
                    'labels' => array_column(array_values($monthsMap), 'label'),
                    'values' => array_column(array_values($monthsMap), 'value'),
                    'counts' => array_column(array_values($monthsMap), 'count')
                ],
                'year' => [
                    'labels' => array_column(array_values($yearsMap), 'label'),
                    'values' => array_column(array_values($yearsMap), 'value'),
                    'counts' => array_column(array_values($yearsMap), 'count')
                ]
            ],
            'customerGrowth' => [
                'labels' => array_column(array_values($monthsMap), 'label'),
                'new' => array_column(array_values($monthsMap), 'new_customers'),
                'churned' => array_column(array_values($monthsMap), 'churned')
            ],
            'network' => [
                'labels' => ['Online', 'Offline'],
                'values' => [$online_network_nodes, $offline_network_nodes],
                'uptime' => $network_uptime,
                'total' => $total_network_nodes,
                'online' => $online_network_nodes,
                'offline' => $offline_network_nodes,
                'routers' => $routers_list,
                'routers_total' => $routers_total,
                'routers_online' => $routers_online,
                'routers_offline' => $routers_offline,
                'radius_enabled' => $radius_enabled,
                'radius_online' => $radius_online,
                'radius_host' => $radius_host ?? 'localhost',
                'radius_nas' => $radius_nas_list,
                'radius_nas_count' => $radius_nas_count,
                'radius_nas_online' => $radius_nas_online,
                'radius_active_sessions' => $radius_active_sessions
            ],
            'transactions' => [
                'labels' => $pmLabels,
                'counts' => $trxCounts,
                'totals' => $pmTotals
            ],
            'routerUsage' => [
                'labels' => $ruLabels,
                'values' => $ruCounts
            ],
            'portalLogins' => [
                'labels' => $dowNames,
                'values' => $dowCounts
            ],
            'planTypes' => [
                'labels' => $ptLabels,
                'values' => $ptCounts
            ],
            'topPlans' => [
                'labels' => $tpLabels,
                'counts' => $tpCounts,
                'totals' => $tpTotals
            ],
            'hourly' => [
                'labels' => $hoursLabels,
                'values' => $hoursCounts,
                'peak_hour' => $peak_hour_str
            ],
            'peakUsage' => [
                'labels' => $blockLabels,
                'values' => $blockCounts
            ],
            'equipment' => $equipmentData
        ];

        return [
            'c_all' => $c_all,
            'c_percentage_change' => $c_percentage_change,
            'u_act' => $u_act,
            'u_all' => $u_all,
            'u_act_pct' => $u_act_pct,
            'u_expired' => $u_expired,
            'imonth' => $imonth,
            'imonth_formatted' => number_format($imonth, 2),
            'rev_percentage_change' => $rev_percentage_change,
            'iday' => $iday,
            'iday_formatted' => number_format($iday, 2),
            'iday_count' => $iday_count,
            'routers_total' => $routers_total,
            'routers_online' => $routers_online,
            'routers_offline' => $routers_offline,
            'routers_list' => $routers_list,
            'radius_enabled' => $radius_enabled,
            'radius_online' => $radius_online,
            'radius_host' => $radius_host ?? 'localhost',
            'radius_nas_list' => $radius_nas_list,
            'radius_nas_count' => $radius_nas_count,
            'radius_nas_online' => $radius_nas_online,
            'radius_active_sessions' => $radius_active_sessions,
            'total_network_nodes' => $total_network_nodes,
            'online_network_nodes' => $online_network_nodes,
            'offline_network_nodes' => $offline_network_nodes,
            'network_uptime' => $network_uptime,
            'peak_hour_str' => $peak_hour_str,
            'dashboard_data_json' => json_encode($allDashboardData)
        ];
    }

    /**
     * Pre-warm dashboard metrics cache in background
     */
    public static function warmDashboard(): array
    {
        global $WIDGET_PATH;
        $data = self::calculateDynamicDashboard();
        if (self::isDashboardCacheEnabled()) {
            $ttl = self::getDashboardTTL();
            self::set('dash:metrics_dynamic', $data, $ttl);

            // Pre-warm classic widgets for Admin only; Agent/Sales output is cached per admin session
            if (!empty($WIDGET_PATH) && is_dir($WIDGET_PATH)) {
                foreach (['Admin'] as $tipeUser) {
                    try {
                        $widgets = ORM::for_table('tbl_widgets')->where("enabled", 1)->where('user', $tipeUser)->order_by_asc("orders")->findArray();
                        $count = count($widgets);
                        for ($i = 0; $i < $count; $i++) {
                            $wFile = $WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'] . ".php";
                            if (file_exists($wFile)) {
                                require_once $wFile;
                                $widgets[$i]['content'] = (new $widgets[$i]['widget'])->getWidget($widgets[$i]);
                            } else {
                                $widgets[$i]['content'] = "Widget not found";
                            }
                        }
                        self::set('dash:widgets_' . $tipeUser, $widgets, $ttl);
                    } catch (Throwable $e) {}
                }
            }
        }
        return $data;
    }
}

