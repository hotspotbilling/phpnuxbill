{include file="customer/header.tpl"}

<div class="row">
    {if file_exists("$PAGES_PATH/Payment_Info.html")}
        <div class="col-md-6">
            <div class="panel panel-warning panel-hovered">
                <div class="panel-heading">{Lang::T('Payment Info')}</div>
                <div class="panel-body">{include file="$PAGES_PATH/Payment_Info.html"}</div>
            </div>
        </div>
    {/if}
    <div class="{if file_exists("$PAGES_PATH/Payment_Info.html")}col-md-6{else}col-md-6 col-md-offset-3{/if}">
        <div class="panel panel-success panel-hovered">
            <div class="panel-heading">{Lang::T('Make Payment')}</div>

            <div class="panel-body">
                <center><b>{Lang::T('Package Details')}</b></center>
                {if !$custom}
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item">
                            <b>{Lang::T('Package Name')}</b>
                            <span class="pull-right">{$plan['name_plan']}</span>
                        </li>

                        {if $plan['is_radius'] or $plan['routers']}
                            <li class="list-group-item">
                                <b>{Lang::T('Location')}</b>
                                <span class="pull-right">{if $plan['is_radius']}Radius{else}{$plan['routers']}{/if}</span>
                            </li>
                        {/if}

                        <li class="list-group-item">
                            <b>{Lang::T('Type')}</b>
                            <span class="pull-right">
                                {if $plan['prepaid'] eq 'yes'}{Lang::T('Prepaid')}{else}{Lang::T('Postpaid')}{/if}
                                {$plan['type']}
                            </span>
                        </li>

                        <li class="list-group-item">
                            <b>{Lang::T('Package Price')}</b>
                            <span class="pull-right">
                                {if !empty($plan['price_old'])}
                                    <sup style="text-decoration: line-through; color: red">
                                        {Lang::moneyFormat($plan['price_old'])}
                                    </sup>
                                {/if}
                                {Lang::moneyFormat($plan['price'])}
                            </span>
                        </li>

                        {if $plan['validity']}
                            <li class="list-group-item">
                                <b>{Lang::T('Validity Period')}</b>
                                <span class="pull-right">{$plan['validity']} {$plan['validity_unit']}</span>
                            </li>
                        {/if}
                    </ul>
                {else}
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item">
                            <b>{Lang::T('Package Name')}</b>
                            <span class="pull-right">{Lang::T('Custom Balance')}</span>
                        </li>

                        <li class="list-group-item">
                            <b>{Lang::T('Amount')}</b>
                            <span class="pull-right">
                                {Lang::moneyFormat($amount)}
                            </span>
                        </li>
                    </ul>
                {/if}
                {if $discount == '' && $plan['type'] neq 'Balance' && $custom == '' && $_c['enable_coupons'] == 'yes'}
                    <!-- Coupon Code Form -->
                    <form action="{Text::url('order/gateway/')}{$route2}/{$route3}" method="post">
                        <div class="form-group row">
                            <label class="col-md-4 control-label">{Lang::T('Coupon Code')}</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="coupon" id="coupon" maxlength="50"
                                        required placeholder="{Lang::T('Enter your coupon code')}">
                                    <span class="input-group-btn">
                                        <button type="submit" name="add_coupon"
                                            class="btn btn-info btn-flat">{Lang::T('Apply Coupon')}</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                {/if}
                <br>
                <center><b>{Lang::T('Summary')}</b></center>
                <ul class="list-group list-group-unbordered">

                    {if $add_cost != 0}
                        {foreach $bills as $k => $v}
                            <li class="list-group-item">
                                <b>{$k}</b>
                                <span class="pull-right">{Lang::moneyFormat($v)}</span>
                            </li>
                        {/foreach}
                        <li class="list-group-item">
                            <b>{Lang::T('Additional Cost')}</b>
                            <span class="pull-right">{Lang::moneyFormat($add_cost)}</span>
                        </li>
                    {/if}
                    {if $discount}
                        <li class="list-group-item">
                            <b>{Lang::T('Discount Applied')}</b>
                            <span class="pull-right">{Lang::moneyFormat($discount)}</span>
                        </li>
                    {/if}

                    {if $amount neq '' && $custom == '1'}
                        <li class="list-group-item">
                            <b>{Lang::T('Total')}</b>
                            <span class="pull-right" style="font-size: large; font-weight: bolder;">
                                {Lang::moneyFormat($amount)}
                            </span>
                        </li>
                    {elseif $plan['type'] eq 'Balance'}
                        <li class="list-group-item">
                            <b>{Lang::T('Total')}</b>
                            <span class="pull-right" style="font-size: large; font-weight: bolder;">
                                {Lang::moneyFormat($plan['price'] + $add_cost)}
                            </span>
                        </li>
                    {else}
                        {if $tax}
                            <li class="list-group-item">
                                <b>{Lang::T('Tax')}</b>
                                <span class="pull-right">{Lang::moneyFormat($tax)}</span>
                            </li>
                        {/if}
                        <li class="list-group-item">
                            <b>{Lang::T('Total')}</b>
                            <span class="pull-right" style="font-size: large; font-weight: bolder;">
                                {Lang::moneyFormat($plan['price'] + $add_cost + $tax)}
                            </span>
                        </li>
                    {/if}
                </ul>

                <!-- Payment Gateway Form -->
                <form method="post" action="{Text::url('order/buy/')}{$route2}/{$route3}">
                    <input type="hidden" name="coupon" value="{$discount}">
                    {if $custom == '1' && $amount neq ''}
                        <input type="hidden" name="custom" value="1">
                        <input type="hidden" name="amount" value="{$amount}">
                    {/if}
                    <div class="form-group row">
                        <label class="col-md-4">{Lang::T('Payment Gateway')}</label>
                        <div class="col-md-8">
                            <select name="gateway" id="gateway" class="form-control">
                                {if $_c['enable_balance'] neq 'no' && $plan['type'] neq 'Balance' && $custom == '' &&
                                $_user['balance'] >= $plan['price'] + $add_cost + $tax}
                                <option value="balance">{Lang::T('Balance')} {Lang::moneyFormat($_user['balance'])}
                                </option>
                                {/if}
                                {foreach $pgs as $pg}
                                    <option value="{$pg}">{ucwords($pg)}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <center>
                        <button type="submit" name="pay" class="btn btn-primary"
                        onclick="return ask(this, '{Lang::T("Are You Sure?")}')">{Lang::T('Pay Now')}</button>
                        <a href="{Text::url('home')}" class="btn btn-secondary">{Lang::T('Cancel')}</a>
                    </center>
                </form>
            </div>
        </div>
    </div>

</div>

{include file="customer/footer.tpl"}