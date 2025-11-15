<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * by https://t.me/ibnux
 * 
 * Interface for managing referrals
 * Maintained by: https://t.me/focuslinkstech
 **/

$ui->assign('_title', Lang::T('Referral System'));
$ui->assign('_system_menu', 'referrals');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

switch ($action) {
    case 'settings':
        _admin();
        if ($_POST) {
            // CSRF Token Validation
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }

            // Input Validation Function
            $validateNumericPositive = function ($value) {
                $val = floatval($value);
                return $val >= 0 ? $val : 0;
            };

            $validateCodeLength = function ($value) {
                $val = intval($value);
                return ($val >= 3 && $val <= 32) ? $val : 8;
            };

            $update = array(
                'referral_enabled' => in_array(_post('referral_enabled'), ['yes', 'no']) ? _post('referral_enabled') : 'no',
                'referral_code_length' => $validateCodeLength(_post('referral_code_length')),
                'require_purchase' => in_array(_post('require_purchase'), ['yes', 'no']) ? _post('require_purchase') : 'no',
                'referrer_reward_type' => in_array(_post('referrer_reward_type'), ['balance', 'discount', 'plan_extension']) ? _post('referrer_reward_type') : 'balance',
                'referred_reward_type' => in_array(_post('referred_reward_type'), ['balance', 'discount', 'plan_extension']) ? _post('referred_reward_type') : 'balance',
                'referrer_reward_amount' => $validateNumericPositive(_post('referrer_reward_amount')),
                'referred_reward_amount' => $validateNumericPositive(_post('referred_reward_amount')),
                'min_purchase_amount' => $validateNumericPositive(_post('min_purchase_amount')),
                'referrer_calculation_type' => in_array(_post('referrer_calculation_type'), ['fixed', 'percentage']) ? _post('referrer_calculation_type') : 'fixed',
                'referred_calculation_type' => in_array(_post('referred_calculation_type'), ['fixed', 'percentage']) ? _post('referred_calculation_type') : 'fixed',
                'referrer_reward_percentage' => $validateNumericPositive(_post('referrer_reward_percentage')),
                'referred_reward_percentage' => $validateNumericPositive(_post('referred_reward_percentage')),
            );

            foreach ($update as $key => $value) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
                if ($d) {
                    $d->value = $value;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = $key;
                    $d->value = $value;
                    $d->save();
                }
            }

            r2(getUrl('referrals/settings'), 's', Lang::T('Settings updated successfully'));
        }

        // Check if schema update is required
        $schema_update_required = false;
        try {
            $columns = ORM::for_table('tbl_referral_rewards')->raw_query('SHOW COLUMNS FROM tbl_referral_rewards')->find_array();
            $existing_columns = [];
            foreach ($columns as $column) {
                $existing_columns[] = $column['Field'];
            }

            // Check if new columns are missing
            $required_columns = ['calculation_type', 'percentage_value', 'purchase_amount', 'reward_for'];
            foreach ($required_columns as $column) {
                if (!in_array($column, $existing_columns)) {
                    $schema_update_required = true;
                    break;
                }
            }
        } catch (Exception $e) {
            // If there's an error, likely the table doesn't exist yet
            $schema_update_required = true;
        }

        $ui->assign('schema_update_required', $schema_update_required);
        // $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/referral/settings.tpl');
        break;

    case 'list':
        _admin();
        $query = ORM::for_table('tbl_referral_relationships')
            ->table_alias('r')
            ->select('r.*')
            ->select('c1.username', 'referrer_username')
            ->select('c1.fullname', 'referrer_name')
            ->select('c1.email', 'referrer_email')
            ->select('c1.status', 'referrer_status')
            ->select('c2.username', 'referred_username')
            ->select('c2.fullname', 'referred_name')
            ->select('c2.email', 'referred_email')
            ->select('c2.status', 'referred_status')
            ->join('tbl_customers', array('c1.id', '=', 'r.referrer_id'), 'c1')
            ->join('tbl_customers', array('c2.id', '=', 'r.referred_id'), 'c2')
            ->order_by_desc('r.created_at');

        // Enhance referral relationships with reward data
        // Important: rewards are tracked per relationship, but reward_for indicates WHO received it
        $referral_rewards_data = ORM::for_table('tbl_referral_rewards')
            ->select('referrer_id')
            ->select('referred_id')
            ->select('reward_for')
            ->select_expr('COUNT(*)', 'reward_count')
            ->select_expr('SUM(reward_amount)', 'total_earned')
            ->group_by('reward_for')
            ->find_array();

        // Create a lookup map for quick access - key includes reward_for
        $rewards_lookup = array();
        foreach ($referral_rewards_data as $rw) {
            // Store each reward record separately, keyed by relationship + recipient type
            $reward_for_value = isset($rw['reward_for']) ? trim($rw['reward_for']) : 'NULL';
            $key = $rw['referrer_id'] . '_' . $rw['referred_id'] . '_' . $reward_for_value;

            _log("DEBUG: Processing reward - referrer_id: {$rw['referrer_id']}, referred_id: {$rw['referred_id']}, reward_for: '$reward_for_value', count: {$rw['reward_count']}, earned: {$rw['total_earned']}");

            $rewards_lookup[$key] = array(
                'referrer_id' => intval($rw['referrer_id']),
                'referred_id' => intval($rw['referred_id']),
                'reward_for' => $reward_for_value,
                'count' => intval($rw['reward_count']),
                'earned' => floatval($rw['total_earned'])
            );
        }

        // Build aggregated lookup by relationship (combining both reward_for types)
        $relationship_rewards = array();
        foreach ($rewards_lookup as $key => $reward) {
            // Use referrer_id and referred_id from the stored data (not parsed from key)
            $rel_key = $reward['referrer_id'] . '_' . $reward['referred_id'];

            if (!isset($relationship_rewards[$rel_key])) {
                $relationship_rewards[$rel_key] = array(
                    'total_count' => 0,
                    'total_earned' => 0,
                    'referrer_count' => 0,
                    'referrer_earned' => 0,
                    'referred_count' => 0,
                    'referred_earned' => 0
                );
            }

            // Add to totals
            $relationship_rewards[$rel_key]['total_count'] += $reward['count'];
            $relationship_rewards[$rel_key]['total_earned'] += $reward['earned'];

            if ($reward['reward_for'] === 'referrer') {
                $relationship_rewards[$rel_key]['referrer_count'] += $reward['count'];
                $relationship_rewards[$rel_key]['referrer_earned'] += $reward['earned'];
            } elseif ($reward['reward_for'] === 'referred') {
                $relationship_rewards[$rel_key]['referred_count'] += $reward['count'];
                $relationship_rewards[$rel_key]['referred_earned'] += $reward['earned'];
            } else {
                _log("DEBUG: UNMATCHED reward_for value: '{$reward['reward_for']}' - not adding to either category!");
            }
        }

        // Paginate first (before enriching with reward data)
        $referrals = Paginator::findMany($query, array(), 20, $append_url, true);

        // Enhance paginated results with reward information
        $enriched_referrals = array();
        if (is_array($referrals)) {
            foreach ($referrals as $ref_array) {

            $key = intval($ref_array['referrer_id']) . '_' . intval($ref_array['referred_id']);
            if (isset($relationship_rewards[$key])) {
                // Total rewards from this relationship
                $ref_array['reward_count'] = $relationship_rewards[$key]['total_count'];
                $ref_array['total_earned'] = $relationship_rewards[$key]['total_earned'];
                // Breakdown by recipient
                $ref_array['referrer_reward_count'] = $relationship_rewards[$key]['referrer_count'];
                $ref_array['referrer_earned'] = $relationship_rewards[$key]['referrer_earned'];
                $ref_array['referred_reward_count'] = $relationship_rewards[$key]['referred_count'];
                $ref_array['referred_earned'] = $relationship_rewards[$key]['referred_earned'];
            } else {
                $ref_array['reward_count'] = 0;
                $ref_array['total_earned'] = 0;
                $ref_array['referrer_reward_count'] = 0;
                $ref_array['referrer_earned'] = 0;
                $ref_array['referred_reward_count'] = 0;
                $ref_array['referred_earned'] = 0;
            }

            $enriched_referrals[] = $ref_array;
        }
        }

        // Fetch rewards statistics
        $rewards_pending = ORM::for_table('tbl_referral_rewards')
            ->where('status', 'pending')
            ->count();

        $rewards_processed = ORM::for_table('tbl_referral_rewards')
            ->where('status', 'processed')
            ->count();

        $rewards_claimed = ORM::for_table('tbl_referral_rewards')
            ->where('status', 'claimed')
            ->count();

        $total_rewards_amount = ORM::for_table('tbl_referral_rewards')
            ->sum('reward_amount');

        // Rewards by type
        $rewards_by_type = array('balance' => 0, 'discount' => 0, 'plan_extension' => 0);
        $type_counts = ORM::for_table('tbl_referral_rewards')
            ->select_many('reward_type')
            ->select_expr('COUNT(*)', 'count')
            ->group_by('reward_type')
            ->find_array();

        foreach ($type_counts as $tc) {
            $rewards_by_type[$tc['reward_type']] = $tc['count'];
        }

        // Rewards amount by type
        $rewards_amount_by_type = array('balance' => 0, 'discount' => 0, 'plan_extension' => 0);
        $amount_by_type = ORM::for_table('tbl_referral_rewards')
            ->select_many('reward_type')
            ->select_expr('SUM(reward_amount)', 'total')
            ->group_by('reward_type')
            ->find_array();

        foreach ($amount_by_type as $abt) {
            $rewards_amount_by_type[$abt['reward_type']] = floatval($abt['total']);
        }

        // Rewards trend (last 30 days)
        $rewards_trend = array('labels' => array(), 'data' => array());
        $trend_data = ORM::for_table('tbl_referral_rewards')
            ->raw_query('SELECT DATE(created_at) as date, SUM(reward_amount) as total FROM tbl_referral_rewards 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                         GROUP BY DATE(created_at) ORDER BY date ASC')
            ->find_array();

        foreach ($trend_data as $td) {
            $rewards_trend['labels'][] = $td['date'];
            $rewards_trend['data'][] = floatval($td['total']);
        }

        // Top referrers (by total rewards earned)
        $top_referrers = ORM::for_table('tbl_referral_rewards')
            ->table_alias('rr')
            ->select('c.id', 'referrer_id')
            ->select('c.username', 'username')
            ->select('c.fullname', 'fullname')
            ->select_expr('COUNT(*)', 'reward_count')
            ->select_expr('SUM(rr.reward_amount)', 'total_earned')
            ->where('rr.reward_for', 'referrer')
            ->join('tbl_customers', array('c.id', '=', 'rr.referrer_id'), 'c')
            ->group_by('rr.referrer_id')
            ->order_by_desc('total_earned')
            ->limit(10)
            ->find_array();

        // Top referrers data with proper formatting
        $top_referrers_data = array();
        foreach ($top_referrers as $referrer) {
            $top_referrers_data[] = array(
                'referrer_id' => $referrer['referrer_id'],
                'username' => $referrer['username'],
                'fullname' => $referrer['fullname'],
                'reward_count' => intval($referrer['reward_count']),
                'total_earned' => floatval($referrer['total_earned'])
            );
        }

        // Total amount earned by all referrers
        $total_amount_earned = ORM::for_table('tbl_referral_rewards')
            ->where('reward_for', 'referrer')
            ->sum('reward_amount');

        // Total amount earned by referred customers
        $total_amount_referred = ORM::for_table('tbl_referral_rewards')
            ->where('reward_for', 'referred')
            ->sum('reward_amount');

        $rewards_stats = array(
            'pending_count' => $rewards_pending,
            'processed_count' => $rewards_processed,
            'claimed_count' => $rewards_claimed,
            'total_rewards' => $total_rewards_amount ? floatval($total_rewards_amount) : 0,
            'total_referrer_earned' => $total_amount_earned ? floatval($total_amount_earned) : 0,
            'total_referred_earned' => $total_amount_referred ? floatval($total_amount_referred) : 0
        );

        $ui->assign('referrals', $enriched_referrals);
        $ui->assign('rewards_stats', $rewards_stats);
        $ui->assign('rewards_by_type', $rewards_by_type);
        $ui->assign('rewards_amount_by_type', $rewards_amount_by_type);
        $ui->assign('rewards_trend', $rewards_trend);
        $ui->assign('top_referrers', $top_referrers_data);
        $ui->display('admin/referral/list.tpl');
        break;
    default:
        _auth();
        global $ui;
        $ui->assign('_title', Lang::T('Referral Program'));
        $ui->assign('_system_menu', 'referrals');
        $user = User::_info();
        $ui->assign('_user', $user);
        // Get the customer's referral code
        $referral_code = Referral::getCustomerReferralCode($user['id']);

        // Get referred customers
        $referred_customers = Referral::getReferredCustomers($user['id']);

        // Get referral rewards
        $rewards = ORM::for_table('tbl_referral_rewards')
            ->where('referrer_id', $user['id'])
            ->where('reward_for', 'referrer')
            ->find_many();

        $referral_link = Text::url('register') . (strpos(Text::url('register'), '?') !== false ? '&' : '?') . 'ref=' . $referral_code;

        // Get the customer who referred this customer
        $referrer = Referral::getReferrer($user['id']);

        $ui->assign('referral_code', $referral_code);
        $ui->assign('referral_link', $referral_link);
        $ui->assign('referred_customers', $referred_customers);
        $ui->assign('referral_rewards', $rewards);
        $ui->assign('referrer', $referrer);
        $ui->assign('xfooter', '');
        run_hook('view_customer_referrals'); #HOOK
        $ui->display('customer/referrals.tpl');
        break;
}
