{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('Referral Settings')}</div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-settings" class="form-horizontal" method="post">
                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Enable Referral System')}</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="referral_enabled">
                                        <option value="yes" {if $_c.referral_enabled=='yes' }selected{/if}>
                                            {Lang::T('Yes')}
                                        </option>
                                        <option value="no" {if $_c.referral_enabled=='no' }selected{/if}>{Lang::T('No')}
                                        </option>
                                    </select>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Enable or Disable Referral system')}</p>
                                </small>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Referral Code Length')}</label>
                                <div class="col-md-6">
                                    <input type="number" class="form-control" name="referral_code_length" value="{$_c['referral_code_length']|default:'8'}" placeholder="8">
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Length of referral code')}</p>
                                </small>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Require Service Purchase')}</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="require_purchase">
                                        <option value="yes" {if $_c.require_purchase=='yes' }selected{/if}>
                                            {Lang::T('Yes')}
                                        </option>
                                        <option value="no" {if $_c.require_purchase=='no' }selected{/if}>{Lang::T('No')}
                                        </option>
                                    </select>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('User required to buy package or services before the
                                        reward can be granted')}</p>
                                </small>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Referrer Reward Type')}</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="referrer_reward_type">
                                        <option value="balance" {if $_c.referrer_reward_type=='balance' }selected{/if}>
                                            {Lang::T('Balance')}</option>
                                        {* <option value="discount" {if $_c.referrer_reward_type=='discount'
                                            }selected{/if}>
                                            {Lang::T('Discount')}</option>
                                        <option value="plan_extension" {if $_c.referrer_reward_type=='plan_extension'
                                            }selected{/if}>{Lang::T('Extend Plan (N/A)')}</option> *}
                                    </select>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Type of reward for referrer (balance, discount,
                                        plan_extension)')}</p>
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Referrer Reward Calculation')}</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="referrer_calculation_type">
                                        <option value="fixed" {if $_c.referrer_calculation_type=='fixed' || $_c.referrer_calculation_type=='' }selected{/if}>
                                            {Lang::T('Fixed Amount')}</option>
                                        <option value="percentage" {if $_c.referrer_calculation_type=='percentage' }selected{/if}>
                                            {Lang::T('Percentage of Purchase')}</option>
                                    </select>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Calculate reward as fixed amount or percentage of purchase value')}</p>
                                </small>
                            </div>

                            <div class="form-group referrer-fixed-amount">
                                <label class="col-md-3 control-label">{Lang::T('Referrer Fixed Reward Amount')}</label>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="referrer_reward_amount"
                                            value="{$_c['referrer_reward_amount']}" placeholder="0.00">
                                        <span class="input-group-addon">{$_c['currency_code']}</span>
                                    </div>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Fixed amount of reward for referrer (used when calculation type is fixed)')}</p>
                                </small>
                            </div>

                            <div class="form-group referrer-percentage">
                                <label class="col-md-3 control-label">{Lang::T('Referrer Reward Percentage')}</label>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="referrer_reward_percentage"
                                            value="{$_c.referrer_reward_percentage|default:'5'}" placeholder="5">
                                        <span class="input-group-addon">%</span>
                                    </div>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Percentage of purchase amount to reward the referrer (used when calculation type is percentage)')}</p>
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Referred Reward Type')}</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="referred_reward_type">
                                        <option value="balance" {if $_c.referred_reward_type=='balance' }selected{/if}>
                                            {Lang::T('Balance')}</option>
                                        {* <option value="discount" {if $_c.referred_reward_type=='discount'
                                            }selected{/if}>{Lang::T('Discount')}</option>
                                        <option value="plan_extension" {if $_c.referred_reward_type=='plan_extension'
                                            }selected{/if}>{Lang::T('Extend Plan (N/A)')}
                                        </option> *}
                                    </select>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Type of reward for referred user (balance, discount,
                                        plan_extension)')}</p>
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Referred Reward Calculation')}</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="referred_calculation_type">
                                        <option value="fixed" {if $_c.referred_calculation_type=='fixed' || $_c.referred_calculation_type=='' }selected{/if}>
                                            {Lang::T('Fixed Amount')}</option>
                                        <option value="percentage" {if $_c.referred_calculation_type=='percentage' }selected{/if}>
                                            {Lang::T('Percentage of Purchase')}</option>
                                    </select>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Calculate reward as fixed amount or percentage of purchase value')}</p>
                                </small>
                            </div>
                            
                            <div class="form-group referred-fixed-amount">
                                <label class="col-md-3 control-label">{Lang::T('Referred Fixed Reward Amount')}</label>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="referred_reward_amount"
                                            value="{$_c.referred_reward_amount}" placeholder="0.00">
                                        <span class="input-group-addon">{$_c['currency_code']}</span>
                                    </div>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Fixed amount of reward for referred user (used when calculation type is fixed)')}</p>
                                </small>
                            </div>
                            
                            <div class="form-group referred-percentage">
                                <label class="col-md-3 control-label">{Lang::T('Referred Reward Percentage')}</label>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="referred_reward_percentage"
                                            value="{$_c.referred_reward_percentage|default:'5'}" placeholder="5">
                                        <span class="input-group-addon">%</span>
                                    </div>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Percentage of purchase amount to reward the referred user (used when calculation type is percentage)')}</p>
                                </small>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">{Lang::T('Minimum Purchase Amount')}</label>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="min_purchase_amount"
                                            value="{$_c.min_purchase_amount}">
                                        <span class="input-group-addon">{$_c['currency_code']}</span>
                                    </div>
                                </div>
                                <small>
                                    <p class="help-block">{Lang::T('Minimum purchase amount to qualify for referral
                                        rewards')}
                                    </p>
                                </small>
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-6">
                                    <button class="btn btn-primary" type="submit">{Lang::T('Save Changes')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        // Helpers
        function show(el) { if (!el) return; el.style.display = ''; }
        function hide(el) { if (!el) return; el.style.display = 'none'; }

        // Toggle logic for one pair (selectEl controls fixedEl and percentEl)
        // Also manages required attribute for the fixed input inside fixedEl
        function togglePair(selectEl, fixedEl, percentEl) {
            if (!selectEl) return;

            const val = (selectEl.value || 'fixed').toLowerCase(); // treat empty as 'fixed'

            // find the input inside fixedEl to toggle required
            let fixedInput = null;
            if (fixedEl) {
                fixedInput = fixedEl.querySelector('input, textarea, select');
            }

            if (val === 'percentage' || val === 'percent') {
                hide(fixedEl);
                show(percentEl);
                if (fixedInput) fixedInput.removeAttribute('required');
            } else {
                // default to fixed
                show(fixedEl);
                hide(percentEl);
                if (fixedInput) fixedInput.setAttribute('required', 'required');
            }
        }

        // Handle Require Service Purchase logic for both referrer & referred
        function handleRequirePurchaseChange(requireSelect, referrerSelect, referrerFixed, referrerPercent, referredSelect, referredFixed, referredPercent) {
            if (!requireSelect) return;

            const requireVal = (requireSelect.value || '').toLowerCase();
            const forceFixed = (requireVal === 'no');

            if (forceFixed) {
                // Force both selects to fixed and disable them
                if (referrerSelect) {
                    referrerSelect.value = 'fixed';
                    referrerSelect.setAttribute('disabled', 'disabled');
                }
                if (referredSelect) {
                    referredSelect.value = 'fixed';
                    referredSelect.setAttribute('disabled', 'disabled');
                }
            } else {
                // Enable selects (restore normal behavior)
                if (referrerSelect) referrerSelect.removeAttribute('disabled');
                if (referredSelect) referredSelect.removeAttribute('disabled');
            }

            // Update visibility & required for both pairs
            togglePair(referrerSelect, referrerFixed, referrerPercent);
            togglePair(referredSelect, referredFixed, referredPercent);
        }

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function () {
            // Referrer controls
            const referrerSelect = document.querySelector('select[name="referrer_calculation_type"]');
            const referrerFixed = document.querySelector('.referrer-fixed-amount');
            const referrerPercent = document.querySelector('.referrer-percentage');

            // Referred controls
            const referredSelect = document.querySelector('select[name="referred_calculation_type"]');
            const referredFixed = document.querySelector('.referred-fixed-amount');
            const referredPercent = document.querySelector('.referred-percentage');

            // Require purchase control
            const requirePurchaseSelect = document.querySelector('select[name="require_purchase"]');

            // Run once on load to set initial visibility & required attributes
            togglePair(referrerSelect, referrerFixed, referrerPercent);
            togglePair(referredSelect, referredFixed, referredPercent);
            handleRequirePurchaseChange(requirePurchaseSelect, referrerSelect, referrerFixed, referrerPercent, referredSelect, referredFixed, referredPercent);

            // Add change listeners
            if (referrerSelect) {
                referrerSelect.addEventListener('change', function () {
                    togglePair(referrerSelect, referrerFixed, referrerPercent);
                });
            }

            if (referredSelect) {
                referredSelect.addEventListener('change', function () {
                    togglePair(referredSelect, referredFixed, referredPercent);
                });
            }

            if (requirePurchaseSelect) {
                requirePurchaseSelect.addEventListener('change', function () {
                    handleRequirePurchaseChange(requirePurchaseSelect, referrerSelect, referrerFixed, referrerPercent, referredSelect, referredFixed, referredPercent);
                });
            }
        });
    })();
</script>

{include file="sections/footer.tpl"}