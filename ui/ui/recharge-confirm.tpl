{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Confirm')}</div>
            <div class="panel-body">
                <center><b>{Lang::T('Customer')}</b></center>
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>{Lang::T('Username')}</b> <span class="pull-right">{$cust['username']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Name')}</b> <span class="pull-right">{$cust['fullname']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Phone Number')}</b> <span class="pull-right">{$cust['phonenumber']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Email')}</b> <span class="pull-right">{$cust['email']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Address')}</b> <span class="pull-right">{$cust['address']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Balance')}</b> <span
                            class="pull-right">{Lang::moneyFormat($cust['balance'])}</span>
                    </li>
                </ul>
                <center><b>{Lang::T('Plan')}</b></center>
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>{Lang::T('Plan Name')}</b> <span class="pull-right">{$plan['name_plan']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Location')}</b> <span
                            class="pull-right">{if $plan['is_radius']}Radius{else}{$plan['routers']}{/if}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Type')}</b> <span
                            class="pull-right">{if $plan['prepaid'] eq 'yes'}Prepaid{else}Postpaid{/if}
                            {$plan['type']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Plan Price')}</b> <span
                            class="pull-right">{if $using eq 'zero'}{Lang::moneyFormat(0)}{else}{Lang::moneyFormat($plan['price'])}{/if}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Plan Validity')}</b> <span class="pull-right">{$plan['validity']}
                            {$plan['validity_unit']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Using')}</b> <span class="pull-right">{ucwords($using)}</span>
                    </li>
                </ul>
                <center><b>{Lang::T('Total')}</b></center>
                <ul class="list-group list-group-unbordered">
                    {if $using neq 'zero' and $add_cost>0}
                        {foreach $bills as $k => $v}
                            <li class="list-group-item">
                                <b>{$k}</b> <span class="pull-right">{Lang::moneyFormat($v)}</span>
                            </li>
                        {/foreach}
                        <li class="list-group-item">
                            <b>{Lang::T('Additional Cost')}</b> <span
                                class="pull-right">{Lang::moneyFormat($add_cost)}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Total')}</b> <small>({Lang::T('Plan Price')} +{Lang::T('Additional Cost')})</small><span class="pull-right"
                                style="font-size: large; font-weight:bolder; font-family: 'Courier New', Courier, monospace; ">{Lang::moneyFormat($plan['price']+$add_cost)}</span>
                        </li>
                    {else}
                        <li class="list-group-item">
                            <b>{Lang::T('Total')}</b> <span class="pull-right"
                                style="font-size: large; font-weight:bolder; font-family: 'Courier New', Courier, monospace; ">{if $using eq 'zero'}{Lang::moneyFormat(0)}{else}{Lang::moneyFormat($plan['price'])}{/if}</span>
                        </li>
                    {/if}
                </ul>
                <form class="form-horizontal" method="post" role="form" action="{$_url}plan/recharge-post">
                    <input type="hidden" name="id_customer" value="{$cust['id']}">
                    <input type="hidden" name="plan" value="{$plan['id']}">
                    <input type="hidden" name="server" value="{$server}">
                    <input type="hidden" name="using" value="{$using}">
                    <input type="hidden" name="stoken" value="{App::getToken()}">
                    <center>
                        <button class="btn btn-success" type="submit">{Lang::T('Recharge')}</button><br>
                        <a class="btn btn-link" href="{$_url}plan/recharge">{Lang::T('Cancel')}</a>
                    </center>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}