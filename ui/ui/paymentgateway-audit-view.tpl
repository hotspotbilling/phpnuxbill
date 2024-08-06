{include file="sections/header.tpl"}

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary mb-3">
                <div class="panel-heading">
                    {$pg['id']}
                </div>
                <div class="panel-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <b>Gateway trx id</b>
                            <span class="float-end">{$pg['gateway_trx_id']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Invoice')}</b>
                            <span class="float-end">
                                <a href="{$_url}reports/activation&q={$pg['trx_invoice']}" class="text-dark">{$pg['trx_invoice']}</a>
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Status')}</b>
                            <span class="float-end">
                                {if $pg['status'] == 1}UNPAID{elseif $pg['status'] == 2}PAID{elseif $pg['status'] == 3}FAILED{else}CANCELED{/if}
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Username')}</b>
                            <span class="float-end">
                                <a href="{$_url}customers/viewu/{$pg['username']}" class="text-dark">{$pg['username']}</a>
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Plan Name')}</b>
                            <span class="float-end">{$pg['plan_name']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Routers')}</b>
                            <span class="float-end">{$pg['routers']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Price')}</b>
                            <span class="float-end">{Lang::moneyFormat($pg['price'])}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Payment Link')}</b>
                            <span class="float-end">
                                {if $pg['pg_url_payment']}
                                    <a href="{$pg['pg_url_payment']}" target="_blank" class="btn btn-outline-secondary btn-sm" rel="noopener noreferrer">click here</a>
                                {/if}
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Channel')}</b>
                            <span class="float-end">{$pg['payment_method']} - {$pg['payment_channel']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Created')}</b>
                            <span class="float-end">{if $pg['created_date'] != null}{Lang::dateTimeFormat($pg['created_date'])}{/if}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Expired')}</b>
                            <span class="float-end">{if $pg['expired_date'] != null}{Lang::dateTimeFormat($pg['expired_date'])}{/if}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Paid')}</b>
                            <span class="float-end">{if $pg['paid_date'] != null}{Lang::dateTimeFormat($pg['paid_date'])}{/if}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-primary mb-3">
        <div class="panel-heading">
            Response
        </div>
        <div class="panel-body">
            {if $pg['pg_paid_response'] != null}
                {assign var='paid_response' value=json_decode($pg['pg_paid_response'], true)}
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$paid_response key=k item=v}
                        {if is_array($v)}
                            {foreach from=$v key=vk item=vv}
                                {if is_array($vv)}
                                    {foreach from=$vv key=vvk item=vvv}
                                        <tr>
                                            <td>{$k} - {$vk} - {$vvk}</td>
                                            <td>{$vvv|json_encode}</td>
                                        </tr>
                                    {/foreach}
                                {else}
                                    <tr>
                                        <td>{$k} - {$vk}</td>
                                        <td>{$vv|json_encode nofilter}</td>
                                    </tr>
                                {/if}
                            {/foreach}
                        {else}
                            <tr>
                                <td>{$k}</td>
                                <td>{if is_array(json_decode($v, true))}{$v|json_encode}{else}{$v nofilter}{/if}</td>
                            </tr>
                        {/if}
                    {/foreach}
                    </tbody>
                </table>
            {/if}
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
