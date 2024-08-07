{include file="sections/header.tpl"}


<div class="row">
    <div class="col-sm-5">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {$pg['gateway_trx_id']}
            </div>
            <div class="panel-body">
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>TRX ID</b> <span class="pull-right">&nbsp;{$pg['id']}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Invoice')}</b> <span class="pull-right">&nbsp;
                        <a href="{$_url}reports/activation&q={$pg['trx_invoice']}" class="text-black">{$pg['trx_invoice']}</a>
                        &nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Status')}</b> <span
                            class="pull-right">&nbsp;{if $pg['status'] == 1}UNPAID{elseif $pg['status'] == 2}PAID{elseif $pg['status'] == 3}FAILED{else}CANCELED{/if}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Username')}</b>
                        <span class="pull-right">&nbsp;<a href="{$_url}customers/viewu/{$pg['username']}" class="text-black">{$pg['username']}</a>&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Plan Name')}</b> <span class="pull-right">&nbsp;{$pg['plan_name']}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Routers')}</b> <span class="pull-right">&nbsp;{$pg['routers']}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Price')}</b> <span
                            class="pull-right">&nbsp;{Lang::moneyFormat($pg['price'])}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Payment Link')}</b> <span class="pull-right">&nbsp;{if $pg['pg_url_payment']}
                                <a href="{$pg['pg_url_payment']}" target="_blank" class="btn btn-xs btn-default"
                                    rel="noopener noreferrer">open</a>
                            {/if}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Channel')}</b> <span class="pull-right">&nbsp;{$pg['payment_method']} -
                            {$pg['payment_channel']}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Created')}</b> <span
                            class="pull-right">&nbsp;{if $pg['created_date'] != null}{Lang::dateTimeFormat($pg['created_date'])}{/if}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Expired')}</b> <span
                            class="pull-right">&nbsp;{if $pg['expired_date'] != null}{Lang::dateTimeFormat($pg['expired_date'])}{/if}&nbsp;</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Paid')}</b> <span
                            class="pull-right">&nbsp;{if $pg['paid_date'] != null}{Lang::dateTimeFormat($pg['paid_date'])}{/if}&nbsp;</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-hovered mb20 panel-primary">
    <div class="panel-heading">
        Response when request payment
    </div>
    <pre class="panel-body p-1">{if $pg['pg_request'] != null}{Lang::jsonArray2text(json_decode($pg['pg_request'], true))}{/if}</pre>
</div>
<div class="panel panel-hovered mb20 panel-primary">
    <div class="panel-heading">
        Response when payment PAID
    </div>
    <pre class="panel-body p-1">{if $pg['pg_request'] != null}{Lang::jsonArray2text(json_decode($pg['pg_paid_response'], true))}{/if}</pre>
</div>

{include file="sections/footer.tpl"}