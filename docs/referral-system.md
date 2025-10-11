# PHPNuxBill Referral System Documentation

This document provides an overview of the referral system in PHPNuxBill, including how it works and how to configure it.

## Overview

The referral system allows customers to refer other users to your service. Both the referrer and the referred user can receive rewards when certain conditions are met.

## Configuration

### Enabling the Referral System

1. Navigate to **System Settings > Referral Settings**
2. Set "Enable Referral System" to **Yes**
3. Configure the other settings as needed

### Key Settings

- **Enable Referral System**: Turn the entire referral system on or off
- **Require Service Purchase**: Determine if rewards are granted only after the referred user makes a purchase
  - **Yes**: Rewards are only given after a purchase is made
  - **No**: Rewards are given when a user signs up with a referral code

### Reward Settings

The system supports two types of reward calculations:

1. **Fixed Amount**: A set amount regardless of purchase value
2. **Percentage**: A percentage of the purchase amount

#### Referrer Rewards

- **Reward Type**: Currently only "Balance" is fully implemented
- **Reward Calculation**: Choose "Fixed Amount" or "Percentage of Purchase"
- **Fixed Reward Amount**: Amount to add to referrer's balance (when fixed is selected)
- **Reward Percentage**: Percentage of purchase to add to referrer's balance (when percentage is selected)

#### Referred User Rewards

- **Reward Type**: Currently only "Balance" is fully implemented
- **Reward Calculation**: Choose "Fixed Amount" or "Percentage of Purchase"
- **Fixed Reward Amount**: Amount to add to referred user's balance (when fixed is selected)
- **Reward Percentage**: Percentage of purchase to add to referred user's balance (when percentage is selected)

#### Other Settings

- **Minimum Purchase Amount**: Minimum amount that must be spent for referral rewards to apply

## How It Works

1. When a user creates an account, they receive a unique referral code
2. They can share this code with others via their referral link
3. New users can enter this code during registration
4. When a referred user signs up or makes a purchase, rewards are generated based on the settings
5. Both the referrer and the referred user can receive rewards

## Reward Processing

- When "Require Service Purchase" is set to "No", rewards are created at signup
- When "Require Service Purchase" is set to "Yes", rewards are created when the referred user makes a purchase
- For percentage-based rewards, the amount is calculated based on the purchase value
- For fixed rewards, the configured amount is used regardless of purchase value

## Database Update

After upgrading to the new version of the referral system, you need to run the database update script to add the necessary columns to support the enhanced features.

1. Go to **Referral Settings**
2. If an update is required, you'll see a notification with a button to run the update
3. Click "Run Database Update" and follow the instructions

## Reward Types

Currently, the system supports the following reward types:

- **Balance**: Add credit to the user's account balance
- **Discount**: (Partially implemented) Generate a discount coupon for future purchases
- **Plan Extension**: (Not yet implemented) Extend the duration of the user's current plan

## Technical Information

The referral system consists of several database tables:

- `tbl_referrals`: Stores customer referral codes
- `tbl_referral_relationships`: Records relationships between referrers and referred users
- `tbl_referral_rewards`: Tracks rewards granted to users

Configuration settings are stored in the `tbl_appconfig` table.

The referral system is primarily handled by the `Referral` class in `system/autoload/Referral.php`.

## Best Practices

1. Start with modest reward amounts and adjust based on performance
2. Consider using percentage-based rewards for higher-value plans
3. Set a reasonable minimum purchase amount to prevent abuse
4. Regularly check the referral list to monitor system performance

## Troubleshooting

- If rewards aren't being processed, check if the database schema has been updated
- Verify the "Require Service Purchase" setting is configured correctly for your needs
- Check the minimum purchase amount to ensure it's not too high for your plans