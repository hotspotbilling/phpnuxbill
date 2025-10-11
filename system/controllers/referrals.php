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
            $update = array(
                'referral_enabled' => _post('referral_enabled'),
                'referral_code_length' => _post('referral_code_length'),
                'require_purchase' => _post('require_purchase'),
                'referrer_reward_type' => _post('referrer_reward_type'),
                'referred_reward_type' => _post('referred_reward_type'),
                'referrer_reward_amount' => _post('referrer_reward_amount'),
                'referred_reward_amount' => _post('referred_reward_amount'),
                'min_purchase_amount' => _post('min_purchase_amount'),
                'referrer_calculation_type' => _post('referrer_calculation_type'),
                'referred_calculation_type' => _post('referred_calculation_type'),
                'referrer_reward_percentage' => _post('referrer_reward_percentage'),
                'referred_reward_percentage' => _post('referred_reward_percentage'),
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
        $ui->display('admin/referral/settings.tpl');
        break;

    case 'list':
        _admin();
        $referrals = ORM::for_table('tbl_referral_relationships')
            ->table_alias('r')
            ->select('r.*')
            ->select('c1.username', 'referrer_username')
            ->select('c1.fullname', 'referrer_name')
            ->select('c2.username', 'referred_username')
            ->select('c2.fullname', 'referred_name')
            ->join('tbl_customers', array('c1.id', '=', 'r.referrer_id'), 'c1')
            ->join('tbl_customers', array('c2.id', '=', 'r.referred_id'), 'c2')
            ->order_by_desc('r.created_at')
            ->find_many();

        $ui->assign('referrals', $referrals);
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
