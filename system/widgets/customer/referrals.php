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

        if (empty($user['id'])) {
            return '';
        }
        
        // Get the customer's referral code
        $referral_code = Referral::getCustomerReferralCode($user['id']);

        if (empty($referral_code)) {
            return '';
        }

        // Get referred customers
        $referred_customers = Referral::getReferredCustomers($user['id']);

        // Get referral rewards
        $rewards = ORM::for_table('tbl_referral_rewards')
            ->where('referrer_id', $user['id'])
            ->where('reward_for', 'referrer')
            ->find_many();

        $referral_link = Text::url('register') . '&ref=' . urlencode($referral_code);
        $total_rewards = 0.0;
        foreach ($rewards as $reward) {
            // Ensure reward amount is properly sanitized (handle null and non-numeric values)
            if ($reward->status == 'processed' && isset($reward->reward_amount)) {
                // Cast to float to ensure numeric addition
                $reward_amount = floatval($reward->reward_amount);
                $total_rewards += $reward_amount;

                // Make sure the reward_amount is normalized in the object
                $reward->reward_amount = $reward_amount;
            } else {
                // Ensure null values are converted to 0
                $reward->reward_amount = 0.0;
            }
        }

        // Get the customer who referred this customer
        $referrer = Referral::getReferrer($user['id']);
        $ui->assign('referral_code', $referral_code);
        $ui->assign('referral_link', $referral_link);
        $ui->assign('referred_customers', $referred_customers);
        $ui->assign('referral_rewards', $rewards);
        $ui->assign('total_rewards', $total_rewards); // Pass the pre-calculated total
        $ui->assign('referrer', $referrer);

        return $ui->fetch('widget/customers/widget_referrals.tpl');
    }
}
