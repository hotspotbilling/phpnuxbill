<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *  Maintained by: https://t.me/focuslinkstech
 **/

class Referral
{
    /**
     * Get referral settings
     * 
     * @param string $key Setting key
     * @param string $default Default value if setting not found
     * @return string Setting value
     */
    public static function getSetting($key, $default = '')
    {
        $setting = ORM::for_table('tbl_appconfig')
            ->where('setting', $key)
            ->find_one();
        
        return $setting ? $setting->value : $default;
    }

    /**
     * Update referral setting
     * 
     * @param string $key Setting key
     * @param string $value Setting value
     * @return bool Success status
     */
    public static function updateSetting($key, $value)
    {
        $setting = ORM::for_table('tbl_appconfig')
            ->where('setting', $key)
            ->find_one();
        
        if($setting) {
            $setting->value = $value;
            return $setting->save();
        } else {
            // Create new setting if it doesn't exist
            $setting = ORM::for_table('tbl_appconfig')->create();
            $setting->setting = $key;
            $setting->value = $value;
            return $setting->save();
        }
    }

    /**
     * Generate a unique referral code
     * 
     * @param int $length Code length (default 8)
     * @return string Unique referral code
     */
    public static function generateReferralCode($length = null)
    {
        if($length === null) {
            $length = intval(self::getSetting('referral_code_length', 8));
        }
        
        $length = max(4, min(20, $length)); // Ensure length is between 4 and 20
        
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        $maxAttempts = 50; // Maximum number of retry attempts
        $attempts = 0;
        
        do {
            if ($attempts >= $maxAttempts) {
                // Log the error
                _log('Failed to generate a unique referral code after ' . $maxAttempts . ' attempts', 'Warning');
                throw new Exception('Could not generate a unique referral code after ' . $maxAttempts . ' attempts. Please try again later.');
            }
            
            $code = '';
            $charLen = strlen($characters) - 1;
            for ($i = 0; $i < $length; $i++) {
                try {
                    // Use cryptographically secure random_int instead of rand
                    $code .= $characters[random_int(0, $charLen)];
                } catch (Exception $e) {
                    // Fallback to rand() if random_int fails (should be rare)
                    _log('random_int failed, falling back to rand(): ' . $e->getMessage(), 'Warning');
                    $code .= $characters[rand(0, $charLen)];
                }
            }

            // Check if code already exists
            $exists = ORM::for_table('tbl_customers')
                ->where('referral_code', $code)
                ->count();
                
            $attempts++;
        } while ($exists > 0);

        return $code;
    }

    /**
     * Get referral code for a customer
     * 
     * @param int $customer_id Customer ID
     * @return string|null Referral code or null if not found
     */
    public static function getCustomerReferralCode($customer_id)
    {
        $customer = ORM::for_table('tbl_customers')
            ->where('id', $customer_id)
            ->find_one();
        
        // If customer not found, return null
        if(!$customer) {
            return null;
        }
        
        // If the customer exists but doesn't have a referral code, generate one (if enabled)
        if(!$customer->referral_code && self::getSetting('referral_enabled', 'yes') === 'yes') {
            return self::createCustomerReferralCode($customer_id);
        }
        
        return $customer->referral_code;
    }

    /**
     * Create a new referral code for a customer
     * 
     * @param int $customer_id Customer ID
     * @return string Generated referral code
     * @throws Exception If unable to generate a unique code within retry limit
     */
    public static function createCustomerReferralCode($customer_id)
    {
        // Get the customer record
        $customer = ORM::for_table('tbl_customers')
            ->where('id', $customer_id)
            ->find_one();
        
        if(!$customer) {
            return null; // Customer not found
        }
        
        // If the customer already has a referral code, return it
        if($customer->referral_code) {
            return $customer->referral_code;
        }
        
        $maxAttempts = 10;
        $attempts = 0;
        $success = false;
        
        // Get PDO instance to handle constraint violations properly
        $db = ORM::get_db();
        
        while (!$success && $attempts < $maxAttempts) {
            try {
                $code = self::generateReferralCode();
                
                // Try to update with a transaction to handle potential race conditions
                $db->beginTransaction();
                
                // First verify the code is still unique right before inserting
                $exists = ORM::for_table('tbl_customers')
                    ->where('referral_code', $code)
                    ->count();
                
                if ($exists == 0) {
                    $customer->referral_code = $code;
                    $customer->save();
                    $success = true;
                }
                
                $db->commit();
                
            } catch (PDOException $e) {
                $db->rollBack();
                
                // Check if this was a duplicate key error
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false || 
                    strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    // Log the collision
                    _log('Referral code collision detected: ' . $code, 'Warning');
                } else {
                    // For other errors, log and re-throw
                    _log('Error creating referral code: ' . $e->getMessage(), 'Error');
                    throw $e;
                }
            }
            
            $attempts++;
        }
        
        if (!$success) {
            $errorMsg = 'Failed to generate a unique referral code after ' . $maxAttempts . ' attempts';
            _log($errorMsg, 'Error');
            throw new Exception($errorMsg);
        }
        
        return $code;
    }

    /**
     * Get referrer by a referral code
     * 
     * @param string $code Referral code
     * @return object|null Customer object of the referrer
     */
    public static function getReferrerByCode($code)
    {
        // Validate and sanitize input
        if (empty($code) || !is_string($code)) {
            return null;
        }
        
        $code = trim($code);
        
        // Look for the customer with this referral code directly in tbl_customers
        // ORM automatically uses parameterized queries for where conditions
        return ORM::for_table('tbl_customers')
            ->where('referral_code', $code)
            ->find_one();
    }

    /**
     * Check if a user has been referred
     * 
     * @param int $customer_id Customer ID
     * @return bool True if customer was referred
     */
    public static function isReferred($customer_id)
    {
        return ORM::for_table('tbl_referral_relationships')
            ->where('referred_id', $customer_id)
            ->count() > 0;
    }

    /**
     * Get the referrer of a customer
     * 
     * @param int $customer_id Customer ID
     * @return object|null Referrer customer object with relationship data
     */
    public static function getReferrer($customer_id)
    {
        $relationship = ORM::for_table('tbl_referral_relationships')
            ->table_alias('r')
            ->select('r.*')
            ->select('c.username', 'referrer_username')
            ->select('c.fullname', 'referrer_name')
            ->select('c.email', 'referrer_email')
            ->join('tbl_customers', array('c.id', '=', 'r.referrer_id'), 'c')
            ->where('r.referred_id', $customer_id)
            ->find_one();
            
        return $relationship;
    }

    /**
     * Get all referred customers by a referrer
     * 
     * @param int $customer_id Referrer ID
     * @return array Array of customer objects with relationship data
     */
    public static function getReferredCustomers($customer_id)
    {
        return ORM::for_table('tbl_referral_relationships')
            ->table_alias('r')
            ->select('r.*')
            ->join('tbl_customers', array('c.id', '=', 'r.referred_id'), 'c')
            ->select('c.username', 'referred_username')
            ->select('c.fullname', 'referred_fullname')
            ->select('c.email', 'referred_email')
            ->select('c.created_at', 'signup_date')
            ->where('r.referrer_id', $customer_id)
            ->order_by_desc('r.created_at')
            ->find_many();
    }

    /**
     * Record a referral relationship
     * 
     * @param int $referrer_id Referrer customer ID
     * @param int $referred_id Referred customer ID
     * @param string $referral_code Code used for the referral
     * @return bool Success status
     */
    public static function recordReferral($referrer_id, $referred_id, $referral_code)
    {
        // Check if the referred customer already has a referrer
        if(self::isReferred($referred_id)) {
            return false;
        }
        
        // Get database connection for transaction
        $db = ORM::get_db();
        
        try {
            // Begin transaction to ensure atomicity
            $db->beginTransaction();
            
            // Record the relationship
            $relationship = ORM::for_table('tbl_referral_relationships')->create();
            $relationship->referrer_id = $referrer_id;
            $relationship->referred_id = $referred_id;
            $relationship->referral_code = $referral_code;
            
            if(!$relationship->save()) {
                throw new Exception("Failed to save referral relationship");
            }
            
            $relationshipId = $relationship->id();
            
            // Create and process rewards immediately if no purchase required
            if(self::getSetting('require_purchase', 'yes') === 'no') {
                // Create referrer reward
                $referrerRewardObj = self::createRewardAndReturn($relationshipId, $referrer_id, $referred_id, 0, 'referrer');
                if ($referrerRewardObj) {
                    // Process referrer reward immediately
                    $referrerProcessed = self::processReferrerReward($referrerRewardObj->id);
                    if (!$referrerProcessed) {
                        throw new Exception("Failed to process referrer reward");
                    }
                }
                
                // Create referred reward
                $referredRewardObj = self::createRewardAndReturn($relationshipId, $referrer_id, $referred_id, 0, 'referred');
                if ($referredRewardObj) {
                    // Process referred reward immediately
                    $referredProcessed = self::processReferredReward($referredRewardObj->id);
                    if (!$referredProcessed) {
                        throw new Exception("Failed to process referred reward");
                    }
                }
            }
            
            // Commit the transaction if everything succeeded
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on any error
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Log the error
            _log("Error recording referral: " . $e->getMessage(), 'Error');
            
            return false;
        }
    }
    
    /**
     * Create a reward and return the ORM object (for transaction support)
     * 
     * @param int $relationship_id Relationship ID
     * @param int $referrer_id Referrer customer ID
     * @param int $referred_id Referred customer ID
     * @param float $purchase_amount Purchase amount (optional, for purchase-based rewards)
     * @param string $reward_for Who the reward is for ('referrer' or 'referred')
     * @return object|false The created reward object or false on failure
     */
    /**
     * Create a reward for a successful referral and return the reward object
     * 
     * @param int $relationship_id Relationship ID
     * @param int $referrer_id Referrer customer ID
     * @param int $referred_id Referred customer ID
     * @param float $purchase_amount Purchase amount (optional, for purchase-based rewards)
     * @param string $reward_for Who the reward is for ('referrer' or 'referred')
     * @return mixed Reward object on success, false on failure
     */
    public static function createRewardAndReturn($relationship_id, $referrer_id, $referred_id, $purchase_amount = 0, $reward_for = 'referrer')
    {
        $require_purchase = self::getSetting('require_purchase', 'yes');
        
        // Create a new reward record
        $reward = ORM::for_table('tbl_referral_rewards')->create();
        $reward->relationship_id = $relationship_id;
        $reward->referrer_id = $referrer_id;
        $reward->referred_id = $referred_id;
        $reward->reward_for = $reward_for; // Store who the reward is for
        $reward->status = 'pending'; // Set initial status to pending
        $reward->created_at = date('Y-m-d H:i:s');
        
        // Set purchase amount (even for non-purchase rewards, for consistency)
        $reward->purchase_amount = $purchase_amount;
        
        // Set appropriate reward settings based on who the reward is for
        if ($reward_for === 'referrer') {
            $reward->reward_type = self::getSetting('referrer_reward_type', 'balance');
            $calculation_type = self::getSetting('referrer_calculation_type', 'fixed');
            
            if ($require_purchase === 'no') {
                // Signup reward - use fixed amount
                $reward->reward_amount = self::getSetting('referrer_reward_amount', '10.00');
                $reward->calculation_type = 'fixed';
            } else {
                // Purchase reward - use either fixed amount or percentage based on setting
                if ($calculation_type == 'percentage') {
                    $percentage = floatval(self::getSetting('referrer_reward_percentage', '5'));
                    $reward->reward_amount = ($percentage / 100) * $purchase_amount;
                    $reward->calculation_type = 'percentage';
                    $reward->percentage_value = $percentage;
                } else {
                    // Fixed amount
                    $reward->reward_amount = self::getSetting('referrer_reward_amount', '10.00');
                    $reward->calculation_type = 'fixed';
                }
            }
        } else {
            // Reward for referred customer
            $reward->reward_type = self::getSetting('referred_reward_type', 'balance');
            $calculation_type = self::getSetting('referred_calculation_type', 'fixed');

            if ($require_purchase === 'no') {
                // Signup reward - use fixed amount
                $reward->reward_amount = self::getSetting('referred_reward_amount', '5.00');
                $reward->calculation_type = 'fixed';
            } else {
                // For purchase rewards
                if ($calculation_type == 'percentage') {
                    $percentage = floatval(self::getSetting('referred_reward_percentage', '5'));
                    $reward->reward_amount = ($percentage / 100) * $purchase_amount;
                    $reward->calculation_type = 'percentage';
                    $reward->percentage_value = $percentage;
                } else {
                    // Fixed amount
                    $reward->reward_amount = self::getSetting('referred_reward_amount', '5.00');
                    $reward->calculation_type = 'fixed';
                }
            }
        }
        
        // Save the reward and return the object (with ID) on success
        if ($reward->save()) {
            return $reward;
        }
        
        return false;
    }

    /**
     * Create a reward for a successful referral
     * 
     * @param int $relationship_id Relationship ID
     * @param int $referrer_id Referrer customer ID
     * @param int $referred_id Referred customer ID
     * @param float $purchase_amount Purchase amount (optional, for purchase-based rewards)
     * @param string $reward_for Who the reward is for ('referrer' or 'referred')
     * @return bool Success status
     */
    public static function createReward($relationship_id, $referrer_id, $referred_id, $purchase_amount = 0, $reward_for = 'referrer')
    {
        // Call createRewardAndReturn and return bool success status for backward compatibility
        $reward = self::createRewardAndReturn($relationship_id, $referrer_id, $referred_id, $purchase_amount, $reward_for);
        return ($reward !== false);
    }

    /**
     * Process a pending reward (for referrer)
     * 
     * @param int $reward_id Reward ID
     * @return bool Success status
     */
    public static function processReferrerReward($reward_id)
    {
        $reward = ORM::for_table('tbl_referral_rewards')
            ->where('id', $reward_id)
            ->where('status', 'pending')
            ->find_one();
        
        if(!$reward) {
            return false;
        }
        
        $customer = ORM::for_table('tbl_customers')
            ->where('id', $reward->referrer_id)
            ->find_one();
        
        if(!$customer) {
            return false;
        }
        
        // Get the reward amount - this is either fixed or calculated from percentage
        $reward_amount = floatval($reward->reward_amount);
        $reward_description = '';
        
        // Check if reward was for signup or purchase
        $require_purchase = self::getSetting('require_purchase', 'yes');
        
        // Process reward based on type
        switch($reward->reward_type) {
            case 'balance':
                // Add to customer balance
                $customer->balance += $reward_amount;
                $customer->save();
                
                // Create notification message based on calculation type and whether purchase is required
                if ($require_purchase === 'no') {
                    // Reward is for signup (no purchase required)
                    $reward_description = sprintf(
                        Lang::T('You have received a referral reward of %s to your balance for referring a new customer who signed up.'),
                        number_format($reward_amount, 2)
                    );
                } else if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                    // Reward is percentage-based on a purchase
                    $percentage = isset($reward->percentage_value) ? 
                        floatval($reward->percentage_value) : 0;
                    $purchase_amount = isset($reward->purchase_amount) ? 
                        floatval($reward->purchase_amount) : 0;
                    
                    $reward_description = sprintf(
                        Lang::T('You have received a referral reward of %s (%s%% of %s) to your balance for referring a customer who made a purchase.'),
                        number_format($reward_amount, 2),
                        $percentage,
                        number_format($purchase_amount, 2)
                    );
                } else {
                    // Reward is fixed amount for a purchase
                    $reward_description = Lang::T('You have received a referral reward of ') . 
                        number_format($reward_amount, 2) . ' ' . 
                        Lang::T('to your balance for referring a customer who made a purchase.');
                }
                
                // Send notification to customer
                $d = ORM::for_table('tbl_customers_inbox')->create();
                $d->customer_id = $customer->id;
                $d->date_created = date('Y-m-d H:i:s');
                $d->subject = 'Referral Reward';
                $d->body = $reward_description;
                $d->save();
                break;
                
            case 'plan_extension':
                // Todo: Implement plan extension reward processing
                break;
                
            default:
                // Discount (coupon generation)
                // Generate coupon code and send it to customer inbox, email and whatsApp message or SMS
                $coupon = ORM::for_table('tbl_coupons')->create();
                $coupon->customer_id = $customer->id;
                $coupon->code = substr(bin2hex(random_bytes(8)), 0, 8);
                
                // Set coupon type based on calculation type
                if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                    $coupon->type = 'percent';
                    $coupon->value = isset($reward->percentage_value) ? 
                        floatval($reward->percentage_value) : floatval($reward->reward_amount);
                } else {
                    $coupon->type = 'fixed';
                    $coupon->value = $reward_amount;
                }
                
                $coupon->save();
                
                // Create notification message based on calculation type and whether purchase is required
                if ($require_purchase === 'no') {
                    // Reward is for signup (no purchase required)
                    if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                        $reward_description = sprintf(
                            Lang::T('You have received a %s%% discount coupon (code: %s) for referring a new customer who signed up.'),
                            $coupon->value,
                            $coupon->code
                        );
                    } else {
                        $reward_description = sprintf(
                            Lang::T('You have received a discount coupon worth %s (code: %s) for referring a new customer who signed up.'),
                            number_format($coupon->value, 2),
                            $coupon->code
                        );
                    }
                } else if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                    // Reward is percentage-based on a purchase
                    $reward_description = sprintf(
                        Lang::T('You have received a %s%% discount coupon (code: %s) for referring a customer who made a purchase.'),
                        $coupon->value,
                        $coupon->code
                    );
                } else {
                    // Reward is fixed amount for a purchase
                    $reward_description = sprintf(
                        Lang::T('You have received a discount coupon worth %s (code: %s) for referring a customer who made a purchase.'),
                        number_format($coupon->value, 2),
                        $coupon->code
                    );
                }
                
                // Send notification to customer
                $d = ORM::for_table('tbl_customers_inbox')->create();
                $d->customer_id = $customer->id;
                $d->date_created = date('Y-m-d H:i:s');
                $d->subject = Lang::T('Referral Reward Coupon');
                $d->body = $reward_description;
                $d->save();
                
                break;
        }
        
        // Mark reward as processed
        $reward->status = 'processed';
        $reward->processed_at = date('Y-m-d H:i:s');
        $reward->save();
        
        return true;
    }

    /**
     * Check if purchase meets minimum requirement for rewards
     * 
     * @param float $amount Purchase amount
     * @return bool True if purchase qualifies for referral rewards
     */
    public static function purchaseQualifiesForReward($amount)
    {
        $min_purchase_amount = floatval(self::getSetting('min_purchase_amount', '0'));
        return $amount >= $min_purchase_amount;
    }
    
    /**
     * Apply discount for referred customer during purchase
     * 
     * @param int $customer_id Customer ID
     * @param float $amount Original purchase amount
     * @return float Discounted amount
     */
    public static function applyReferredDiscount($customer_id, $amount)
    {
        // Check if purchase meets minimum requirement
        if (!self::purchaseQualifiesForReward($amount)) {
            return $amount;
        }
        
        $reward = ORM::for_table('tbl_referral_rewards')
            ->where('referred_id', $customer_id)
            ->where('status', 'pending')
            ->where('reward_type', 'discount')
            ->find_one();
        
        if(!$reward) {
            return $amount;
        }
        
        // Calculate discount amount based on calculation type
        $discounted_amount = $amount;
        
        if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
            // Percentage discount
            $percentage = isset($reward->percentage_value) ? 
                floatval($reward->percentage_value) : floatval($reward->reward_amount);
                
            if ($percentage > 0 && $percentage <= 100) {
                $discounted_amount = $amount - ($amount * ($percentage / 100));
                
                // Record the actual discount amount applied for reporting
                $reward->actual_discount_amount = $amount - $discounted_amount;
                $reward->purchase_amount = $amount;
            }
        } else {
            // Fixed amount discount
            $discount = floatval($reward->reward_amount);
            $discounted_amount = max(0, $amount - $discount);
            
            // Record the actual discount amount applied for reporting
            $reward->actual_discount_amount = $amount - $discounted_amount;
            $reward->purchase_amount = $amount;
        }
        
        // Mark reward as processed
        $reward->status = 'processed';
        $reward->processed_at = date('Y-m-d H:i:s');
        $reward->save();
        
        return $discounted_amount;
    }
    
    /**
     * Process a pending reward for referred user
     * 
     * @param int $reward_id Reward ID
     * @return bool Success status
     */
    public static function processReferredReward($reward_id)
    {
        $reward = ORM::for_table('tbl_referral_rewards')
            ->where('id', $reward_id)
            ->where('status', 'pending')
            ->find_one();
        
        if(!$reward) {
            return false;
        }
        
        $customer = ORM::for_table('tbl_customers')
            ->where('id', $reward->referred_id)
            ->find_one();
        
        if(!$customer) {
            return false;
        }
        
        // Get the reward amount - this is either fixed or calculated from percentage
        $reward_amount = floatval($reward->reward_amount);
        $reward_description = '';
        
        // Check if reward was for signup or purchase
        $require_purchase = self::getSetting('require_purchase', 'yes');
        
        // Process reward based on type
        switch($reward->reward_type) {
            case 'balance':
                // Add to customer balance
                $customer->balance += $reward_amount;
                $customer->save();
                
                // Create notification message based on calculation type and whether purchase is required
                if ($require_purchase === 'no') {
                    // Reward is for signup (no purchase required)
                    $reward_description = sprintf(
                        Lang::T('You have received a signup bonus of %s to your balance for joining through a referral.'),
                        number_format($reward_amount, 2)
                    );
                } else if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                    // Reward is percentage-based on a purchase
                    $percentage = isset($reward->percentage_value) ? 
                        floatval($reward->percentage_value) : 0;
                    $purchase_amount = isset($reward->purchase_amount) ? 
                        floatval($reward->purchase_amount) : 0;
                    
                    $reward_description = sprintf(
                        Lang::T('You have received a referral reward of %s (%s%% of %s) to your balance for making a purchase as a referred customer.'),
                        number_format($reward_amount, 2),
                        $percentage,
                        number_format($purchase_amount, 2)
                    );
                } else {
                    // Reward is fixed amount for a purchase
                    $reward_description = Lang::T('You have received a referral reward of ') . 
                        number_format($reward_amount, 2) . ' ' . 
                        Lang::T('to your balance as a referred customer who made a purchase.');
                }
                
                // Send notification to customer
                $d = ORM::for_table('tbl_customers_inbox')->create();
                $d->customer_id = $customer->id;
                $d->date_created = date('Y-m-d H:i:s');
                $d->subject = 'Referral Reward';
                $d->body = $reward_description;
                $d->save();
                break;
                
            case 'plan_extension':
                // Todo: Implement plan extension reward processing
                break;
                
            default:
                // Discount handling (similar to processReferrerReward but for referred customers)
                $coupon = ORM::for_table('tbl_coupons')->create();
                $coupon->customer_id = $customer->id;
                $coupon->code = substr(md5(time()), 0, 8);
                
                // Set coupon type based on calculation type
                if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                    $coupon->type = 'percent';
                    $coupon->value = isset($reward->percentage_value) ? 
                        floatval($reward->percentage_value) : floatval($reward->reward_amount);
                } else {
                    $coupon->type = 'fixed';
                    $coupon->value = $reward_amount;
                }
                
                $coupon->save();
                
                // Create notification message based on calculation type and whether purchase is required
                if ($require_purchase === 'no') {
                    // Reward is for signup (no purchase required)
                    if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                        $reward_description = sprintf(
                            Lang::T('You have received a %s%% discount coupon (code: %s) as a signup bonus for joining through a referral.'),
                            $coupon->value,
                            $coupon->code
                        );
                    } else {
                        $reward_description = sprintf(
                            Lang::T('You have received a discount coupon worth %s (code: %s) as a signup bonus for joining through a referral.'),
                            number_format($coupon->value, 2),
                            $coupon->code
                        );
                    }
                } else if (isset($reward->calculation_type) && $reward->calculation_type == 'percentage') {
                    // Reward is percentage-based on a purchase
                    $reward_description = sprintf(
                        Lang::T('You have received a %s%% discount coupon (code: %s) as a referred customer who made a purchase.'),
                        $coupon->value,
                        $coupon->code
                    );
                } else {
                    // Reward is fixed amount for a purchase
                    $reward_description = sprintf(
                        Lang::T('You have received a discount coupon worth %s (code: %s) as a referred customer who made a purchase.'),
                        number_format($coupon->value, 2),
                        $coupon->code
                    );
                }
                
                // Send notification to customer
                $d = ORM::for_table('tbl_customers_inbox')->create();
                $d->customer_id = $customer->id;
                $d->date_created = date('Y-m-d H:i:s');
                $d->subject = Lang::T('Referral Reward Coupon');
                $d->body = $reward_description;
                $d->save();
                
                break;
        }
        
        // Mark reward as processed
        $reward->status = 'processed';
        $reward->processed_at = date('Y-m-d H:i:s');
        $reward->save();
        
        return true;
    }
}