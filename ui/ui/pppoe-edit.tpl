{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}services/edit-pppoe-post">
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Edit Service Plan')} || {$d['name_plan']}</div>
                <div class="panel-body">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Status')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="Customer cannot buy disabled Plan, but admin can recharge it, use it if you want only admin recharge it">?</a>
                        </label>
                        <div class="col-md-9">
                            <input type="radio" name="enabled" value="1" {if $d['enabled'] == 1}checked{/if}> Enable
                            <input type="radio" name="enabled" value="0" {if $d['enabled'] == 0}checked{/if}> Disable
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Type')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="Postpaid will have fix expired date">?</a>
                        </label>
                        <div class="col-md-9">
                            <input type="radio" name="prepaid" onclick="prePaid()" value="yes"
                                {if $d['prepaid'] == 'yes'}checked{/if}>
                            Prepaid
                            <input type="radio" name="prepaid" onclick="postPaid()" value="no"
                                {if $d['prepaid'] == 'no'}checked{/if}> Postpaid
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Plan Type')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="Personal Plan will only show to personal Customer, Business plan will only show to Business Customer">?</a>
                        </label>
                        <div class="col-md-9">
                            <input type="radio" name="plan_type" value="Personal"
                                {if $d['plan_type'] == 'Personal'}checked{/if}>
                            Personal
                            <input type="radio" name="plan_type" value="Business"
                                {if $d['plan_type'] == 'Business'}checked{/if}> Business
                        </div>
                    </div>
                    {if $_c['radius_enable'] and $d['is_radius']}
                        <div class="form-group">
                            <label class="col-md-3 control-label">Radius
                                <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                    data-trigger="focus" data-container="body"
                                    data-content="If you enable Radius, choose device to radius, except if you have custom device.">?</a>
                            </label>
                            <div class="col-md-9">
                                <label class="label label-primary">RADIUS</label>
                            </div>
                        </div>
                    {/if}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Device')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="This Device are the logic how PHPNuxBill Communicate with Mikrotik or other Devices">?</a>
                        </label>
                        <div class="col-md-9">
                            <select class="form-control" id="device" name="device">
                                {foreach $devices as $dev}
                                    <option value="{$dev}" {if $dev == $d['device']}selected{/if}>{$dev}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Plan Name')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="name_plan" maxlength="40" name="name_plan"
                                value="{$d['name_plan']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><a
                                href="{$_url}bandwidth/add">{Lang::T('Bandwidth Name')}</a></label>
                        <div class="col-md-9">
                            <select id="id_bw" name="id_bw" class="form-control select2">
                                {foreach $b as $bs}
                                    <option value="{$bs['id']}" {if $d['id_bw'] eq $bs['id']} selected {/if}>
                                        {$bs['name_bw']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Plan Price')}</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-addon">{$_c['currency_code']}</span>
                                <input type="number" class="form-control" name="price" required value="{$d['price']}">
                            </div>
                        </div>
                        {if $_c['enable_tax'] == 'yes'}
                            {if $_c['tax_rate'] == 'custom'}
                                <p class="help-block col-md-4">{number_format($_c['custom_tax_rate'], 2)} % {Lang::T('Tax Rates
                            will be added')}</p>
                            {else}
                                <p class="help-block col-md-4">{number_format($_c['tax_rate'] * 100, 2)} % {Lang::T('Tax Rates
                            will be added')}</p>
                            {/if}
                        {/if}

                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Plan Validity')}</label>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="validity" name="validity"
                                value="{$d['validity']}">
                        </div>
                        <div class="col-md-6">
                            <select class="form-control" id="validity_unit" name="validity_unit">
                                {if $d['prepaid'] == yes}
                                    <option value="Mins" {if $d['validity_unit'] eq 'Mins'} selected {/if}>{Lang::T('Mins')}
                                    </option>
                                    <option value="Hrs" {if $d['validity_unit'] eq 'Hrs'} selected {/if}>{Lang::T('Hrs')}
                                    </option>
                                    <option value="Days" {if $d['validity_unit'] eq 'Days'} selected {/if}>{Lang::T('Days')}
                                    </option>
                                    <option value="Months" {if $d['validity_unit'] eq 'Months'} selected {/if}>
                                        {Lang::T('Months')}</option>
                                {else}
                                    <option value="Period" {if $d['validity_unit'] eq 'Period'} selected {/if}>
                                        {Lang::T('Period')}</option>
                                {/if}
                            </select>
                            <p class="help-block">{Lang::T('1 Period = 1 Month, Expires the 20th of each month')}</p>
                        </div>

                    </div>
                    <div class="form-group {if $d['prepaid'] == yes}hidden{/if}" id="expired_date">
                        <label class="col-md-3 control-label">{Lang::T('Expired Date')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="Expired will be this date every month">?</a>
                        </label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="expired_date" maxlength="2"
                                value="{if $d['expired_date']}{$d['expired_date']}{else}20{/if}" min="1" max="28"
                                step="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><a href="{$_url}pool/add">{Lang::T('IP Pool')}</a></label>
                        <div class="col-md-9">
                            <select id="pool_name" name="pool_name" required class="form-control select2">
                                {foreach $p as $ps}
                                    <option value="{$ps['pool_name']}" {if $d['pool'] eq $ps['pool_name']} selected {/if}>
                                        {$ps['pool_name']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Router Name')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="routers" name="routers" value="{$d['routers']}"
                                readonly>
                        </div>
                    </div>
                    <legend>{Lang::T('Expired Action')} <sub>{Lang::T('Optional')}</sub></legend>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Expired Internet Plan')}</label>
                        <div class="col-md-9">
                            <select id="plan_expired" name="plan_expired" class="form-control select2">
                                <option value='0'>Default - Remove Customer</option>
                                {foreach $exps as $exp}
                                    <option value="{$exp['id']}" {if $d['plan_expired'] eq $exp['id']} selected {/if}>
                                        {$exp['name_plan']}</option>
                                {/foreach}
                            </select>
                            <p class="help-block">
                                {Lang::T('When Expired, customer will be move to selected internet plan')}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {if !$d['is_radius']}
            <div class="col-md-6">
                <div class="panel panel-primary panel-hovered panel-stacked mb30">
                    <div class="panel-heading">on-login / on-up</div>
                    <div class="panel-body">
                        <textarea class="form-control" id="code" name="on_login"
                            style="font-family: 'Courier New', Courier, monospace;" rows="15">{$d['on_login']}</textarea>
                    </div>
                </div>
                <div class="panel panel-primary panel-hovered panel-stacked mb30">
                    <div class="panel-heading">on-logout / on-down</div>
                    <div class="panel-body">
                        <textarea class="form-control" id="code2" name="on_logout"
                            style="font-family: 'Courier New', Courier, monospace;" rows="15">{$d['on_logout']}</textarea>
                    </div>
                </div>
            </div>
        {/if}
    </div>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <button class="btn btn-success" type="submit">{Lang::T('Save Changes')}</button>
            Or <a href="{$_url}services/pppoe">{Lang::T('Cancel')}</a>
        </div>
    </div>
</form>


<script>
    var preOpt = `<option value="Mins">{Lang::T('Mins')}</option>
    <option value="Hrs">{Lang::T('Hrs')}</option>
    <option value="Days">{Lang::T('Days')}</option>
    <option value="Months">{Lang::T('Months')}</option>`;
    var postOpt = `<option value="Period">{Lang::T('Period')}</option>`;
    function prePaid() {
        $("#validity_unit").html(preOpt);
        $('#expired_date').addClass('hidden');
    }

    function postPaid() {
        $("#validity_unit").html(postOpt);
        $("#expired_date").removeClass('hidden');
    }
</script>

<script language="javascript" type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.js"></script>
<script language="javascript" type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/perl/perl.min.js"></script>

<link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.css">
</link>
<link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/abbott.min.css">
</link>

<script>
    CodeMirror.fromTextArea(document.getElementById('code'), {
        lineNumbers: true,
        mode: 'text/x-perl',
    });
    CodeMirror.fromTextArea(document.getElementById('code2'), {
        lineNumbers: true,
        mode: 'text/x-perl',
    });
</script>

{include file="sections/footer.tpl"}