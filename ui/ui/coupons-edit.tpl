{include file="sections/header.tpl"}
<!-- Coupon-edit -->



<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Edit Coupon')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}coupons/edit-post">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">

                    <!-- Coupon Code -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Coupon Code')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="code" id="code" maxlength="50" readonly
                                value="{$coupon['code']}">
                            <p class="help-block"><small>{Lang::T('Unique code for the coupon')}</small></p>
                        </div>
                    </div>

                    <!-- Type -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="type" id="type" required onchange="updateValueInput()">
                                <option value="fixed" {if $coupon['type']== 'fixed'} selected {/if}>{Lang::T('Fixed Discount')}</option>
                                <option value="percent" {if $coupon['type']== 'percent'} selected {/if}>{Lang::T('Percent Discount')}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Value -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Discount Value')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="value" id="value" step="0.01"
                                placeholder="Enter amount" required value="{$coupon['value']}">
                            <p class="help-block"><small id="value-help">{Lang::T('Value of the discount (amount or
                                    percentage)')}</small></p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Description')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" name="description" required>{$coupon['description']}</textarea>
                            <p class="help-block"><small>{Lang::T('Brief explanation of the coupon')}</small></p>
                        </div>
                    </div>

                    <!-- Max Usage -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Max Usage')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="max_usage" required value="{$coupon['max_usage']}">
                            <p class="help-block"><small>{Lang::T('Maximum number of times this coupon can be used 0 is Unlimited')}</small></p>
                        </div>
                    </div>

                    <!-- Minimum Order Amount -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Minimum Order Amount')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="min_order_amount" step="0.01" required value="{$coupon['min_order_amount']}">
                            <p class="help-block"><small>{Lang::T('Minimum cart total required to use this
                                    coupon')}</small></p>
                        </div>
                    </div>

                    <!-- Max Discount Amount -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Max Discount Amount')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="max_discount_amount" step="0.01" value="{$coupon['max_discount_amount']}">
                            <p class="help-block"><small>{Lang::T('Maximum discount amount applicable (for percent
                                    type)')}</small></p>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                        <div class="col-md-6">
                            <label class="radio-inline">
                                <input type="radio" name="status" value="active" {if $coupon['status']== 'active'} checked {/if}> {Lang::T('Active')}
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="status" value="inactive"  {if $coupon['status']== 'inactive'} checked {/if}> {Lang::T('Inactive')}
                            </label>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Start Date')}</label>
                        <div class="col-md-6">
                            <input type="date" class="form-control" name="start_date" required value="{$coupon['start_date']}">
                        </div>
                    </div>

                    <!-- Expiry Date -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('End Date')}</label>
                        <div class="col-md-6">
                            <input type="date" class="form-control" name="end_date" required value="{$coupon['end_date']}">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary" type="submit">
                                {Lang::T('Save')}
                            </button>
                            Or <a href="{$_url}coupons/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    function updateValueInput() {
        const type = document.getElementById('type').value;
        const valueInput = document.getElementById('value');
        const helpText = document.getElementById('value-help');

        if (type === 'percent') {
            valueInput.setAttribute('max', '100');
            valueInput.setAttribute('placeholder', 'Enter percentage');
            helpText.textContent = '{Lang::T('Value of the discount(percentage, max 100)')}';
        } else {
            valueInput.removeAttribute('max');
            valueInput.setAttribute('placeholder', 'Enter amount');
            helpText.textContent = '{Lang::T('Value of the discount(amount)')}';
        }
    }
</script>



{include file="sections/footer.tpl"}