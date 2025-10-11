<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * by https://t.me/ibnux
 * Used to display the referral widget in customer dashboard
 * Maintained by: https://t.me/focuslinkstech
 **/

class referrals
{
    public function getWidget()
    {
        global $ui, $user;
        // Get the customer's referral code
        $referral_code = Referral::getCustomerReferralCode($user['id']);

        // Get referred customers
        $referred_customers = Referral::getReferredCustomers($user['id']);

        // Get referral rewards
        $rewards = ORM::for_table('tbl_referral_rewards')
            ->where('referrer_id', $user['id'])
            ->find_many();

        $referral_link = Text::url('register') . '?ref=' . $referral_code;

        // Get the customer who referred this customer
        $referrer = Referral::getReferrer($user['id']);

        $ui->assign('referral_code', $referral_code);
        $ui->assign('referral_link', $referral_link);
        $ui->assign('referred_customers', $referred_customers);
        $ui->assign('referral_rewards', $rewards);
        $ui->assign('referrer', $referrer);

        return $ui->fetch('widget/customers/widget_referrals.tpl');
    }
}

