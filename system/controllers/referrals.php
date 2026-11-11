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
                // every row of the setting: an older update could add a second copy, and the settings load keeps the last
                // one, so writing only the first left the old value in force (the program could not be switched off)
                $rows = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_many();
                if (count($rows)) {
                    foreach ($rows as $d) {
                        $d->value = $value;
                        $d->save();
                    }
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
        $search = trim((string) _req('q'));
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
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where_raw(
                '(c1.username LIKE ? OR c1.fullname LIKE ? OR c2.username LIKE ? OR c2.fullname LIKE ? OR r.referral_code LIKE ?)',
                array($like, $like, $like, $like, $like)
            );
        }
        $referrals = Paginator::findMany($query, $search !== '' ? array('q' => $search) : array(), 20, '', true);
        if (!is_array($referrals)) {
            $referrals = array();
        }

        // Rewards of the relationships on this page, split by who received them
        $relationship_rewards = array();
        $ids = array_map('intval', array_column($referrals, 'id'));
        if ($ids) {
            $rows = ORM::for_table('tbl_referral_rewards')
                ->select('relationship_id')
                ->select('reward_for')
                ->select_expr('COUNT(*)', 'reward_count')
                ->select_expr("SUM(status = 'pending')", 'pending_count')
                ->select_expr("SUM(CASE WHEN status <> 'pending' THEN reward_amount ELSE 0 END)", 'paid')
                ->where_in('relationship_id', $ids)
                ->group_by('relationship_id')
                ->group_by('reward_for')
                ->find_array();
            foreach ($rows as $rw) {
                $id = intval($rw['relationship_id']);
                if (!isset($relationship_rewards[$id])) {
                    $relationship_rewards[$id] = array('count' => 0, 'pending' => 0, 'referrer' => 0.0, 'referred' => 0.0);
                }
                $relationship_rewards[$id]['count'] += intval($rw['reward_count']);
                $relationship_rewards[$id]['pending'] += intval($rw['pending_count']);
                $for = trim((string) $rw['reward_for']) === 'referred' ? 'referred' : 'referrer';
                $relationship_rewards[$id][$for] += floatval($rw['paid']);
            }
        }
        foreach ($referrals as &$ref) {
            $rw = isset($relationship_rewards[intval($ref['id'])]) ? $relationship_rewards[intval($ref['id'])]
                : array('count' => 0, 'pending' => 0, 'referrer' => 0.0, 'referred' => 0.0);
            $ref['reward_count'] = $rw['count'];
            $ref['pending_count'] = $rw['pending'];
            $ref['referrer_earned'] = $rw['referrer'];
            $ref['referred_earned'] = $rw['referred'];
            $ref['total_earned'] = $rw['referrer'] + $rw['referred'];
        }
        unset($ref);

        // Overview: referrals, how many earned a reward, money paid out and what is still waiting
        $total_referrals = ORM::for_table('tbl_referral_relationships')->count();
        $new_referrals = ORM::for_table('tbl_referral_relationships')
            ->where_raw('created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')
            ->count();
        $rewarded = ORM::for_table('tbl_referral_rewards')
            ->select_expr('COUNT(DISTINCT relationship_id)', 'n')
            ->where_not_equal('status', 'pending')
            ->find_one();
        $by_status = array('pending' => array('count' => 0, 'amount' => 0.0), 'processed' => array('count' => 0, 'amount' => 0.0), 'claimed' => array('count' => 0, 'amount' => 0.0));
        foreach (ORM::for_table('tbl_referral_rewards')
            ->select('status')
            ->select_expr('COUNT(*)', 'n')
            ->select_expr('SUM(reward_amount)', 'amount')
            ->group_by('status')
            ->find_array() as $row) {
            if (isset($by_status[$row['status']])) {
                $by_status[$row['status']] = array('count' => intval($row['n']), 'amount' => floatval($row['amount']));
            }
        }
        $paid_by = array('referrer' => 0.0, 'referred' => 0.0);
        foreach (ORM::for_table('tbl_referral_rewards')
            ->select('reward_for')
            ->select_expr('SUM(reward_amount)', 'amount')
            ->where_not_equal('status', 'pending')
            ->group_by('reward_for')
            ->find_array() as $row) {
            $paid_by[trim((string) $row['reward_for']) === 'referred' ? 'referred' : 'referrer'] += floatval($row['amount']);
        }
        $rewards_stats = array(
            'referrals' => intval($total_referrals),
            'new_referrals' => intval($new_referrals),
            'rewarded' => $rewarded ? intval($rewarded['n']) : 0,
            'paid' => $paid_by['referrer'] + $paid_by['referred'],
            'paid_referrer' => $paid_by['referrer'],
            'paid_referred' => $paid_by['referred'],
            'by_status' => $by_status,
        );

        // Rewards paid per day over the last 30 days, every day present (a quiet day is 0, not a gap)
        $trend_rows = ORM::for_table('tbl_referral_rewards')
            ->select_expr('DATE(COALESCE(processed_at, created_at))', 'day')
            ->select_expr('SUM(reward_amount)', 'total')
            ->select_expr('COUNT(*)', 'n')
            ->where_not_equal('status', 'pending')
            ->where_raw('COALESCE(processed_at, created_at) >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)')
            ->group_by_expr('DATE(COALESCE(processed_at, created_at))')
            ->find_array();
        $trend_by_day = array();
        foreach ($trend_rows as $row) {
            $trend_by_day[$row['day']] = array(floatval($row['total']), intval($row['n']));
        }
        $rewards_trend = array('labels' => array(), 'data' => array(), 'counts' => array(), 'total' => 0.0);
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i day"));
            $rewards_trend['labels'][] = $day;
            $rewards_trend['data'][] = isset($trend_by_day[$day]) ? $trend_by_day[$day][0] : 0;
            $rewards_trend['counts'][] = isset($trend_by_day[$day]) ? $trend_by_day[$day][1] : 0;
            $rewards_trend['total'] += isset($trend_by_day[$day]) ? $trend_by_day[$day][0] : 0;
        }

        // Top referrers: most customers brought in, with what they earned
        $top_referrers = ORM::for_table('tbl_referral_relationships')
            ->table_alias('r')
            ->select('r.referrer_id')
            ->select('c.username')
            ->select('c.fullname')
            ->select_expr('COUNT(*)', 'referrals')
            ->join('tbl_customers', array('c.id', '=', 'r.referrer_id'), 'c')
            ->group_by('r.referrer_id')
            ->group_by('c.username')
            ->group_by('c.fullname')
            ->order_by_desc('referrals')
            ->limit(5)
            ->find_array();
        $top_ids = array_map('intval', array_column($top_referrers, 'referrer_id'));
        $earned = array();
        if ($top_ids) {
            foreach (ORM::for_table('tbl_referral_rewards')
                ->select('referrer_id')
                ->select_expr('SUM(reward_amount)', 'amount')
                ->where('reward_for', 'referrer')
                ->where_not_equal('status', 'pending')
                ->where_in('referrer_id', $top_ids)
                ->group_by('referrer_id')
                ->find_array() as $row) {
                $earned[intval($row['referrer_id'])] = floatval($row['amount']);
            }
        }
        foreach ($top_referrers as &$t) {
            $t['referrals'] = intval($t['referrals']);
            $t['earned'] = isset($earned[intval($t['referrer_id'])]) ? $earned[intval($t['referrer_id'])] : 0;
        }
        unset($t);

        $ui->assign('search', $search);
        $ui->assign('referrals', $referrals);
        $ui->assign('rewards_stats', $rewards_stats);
        $ui->assign('rewards_trend', $rewards_trend);
        $ui->assign('top_referrers', $top_referrers);
        $ui->assign('top_max', $top_referrers ? max(array_column($top_referrers, 'referrals')) : 0);
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
