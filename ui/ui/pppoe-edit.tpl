{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Edit Service Plan')} || {$d['name_plan']}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}services/edit-pppoe-post">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                        <div class="col-md-10">
                            <input type="radio" name="enabled" value="1" {if $d['enabled'] == 1}checked{/if}> Enable
                            <input type="radio" name="enabled" value="0" {if $d['enabled'] == 0}checked{/if}> Disable
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}</label>
                        <div class="col-md-10">
                            <input type="radio" name="prepaid" onclick="prePaid()" value="yes" {if $d['prepaid'] == yes}checked{/if}>
                            Prepaid
                            <input type="radio" name="prepaid" onclick="postPaid()" value="no" {if $d['prepaid'] == no}checked{/if}> Postpaid
                        </div>
                    </div>
                     <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Type')}</label>
                        <div class="col-md-10">
                            <input type="radio" name="plan_type"   value="Personal"
                                {if $d['plan_type'] == 'Personal'}checked{/if}>
                            Personal
                            <input type="radio" name="plan_type"   value="Business"
                                {if $d['plan_type'] == 'Business'}checked{/if}> Business
                        </div>
                    </div>
                    
                    {if $_c['radius_enable'] and $d['is_radius']}
                        <div class="form-group">
                            <label class="col-md-2 control-label">Radius</label>
                            <div class="col-md-10">
                                <label class="label label-primary">RADIUS</label>
                            </div>
                        </div>
                    {/if}
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="name_plan" maxlength="40" name="name_plan"
                                value="{$d['name_plan']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{$_url}bandwidth/add">{Lang::T('Bandwidth Name')}</a></label>
                        <div class="col-md-6">
                            <select id="id_bw" name="id_bw" class="form-control select2">
                                {foreach $b as $bs}
                                    <option value="{$bs['id']}" {if $d['id_bw'] eq $bs['id']} selected {/if}>
                                        {$bs['name_bw']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Price')}</label>
                        <div class="col-md-6">
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
                        <label class="col-md-2 control-label">{Lang::T('Plan Validity')}</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="validity" name="validity"
                                value="{$d['validity']}">
                        </div>
                        <div class="col-md-2">
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
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('1 Period = 1 Month, Expires the 20th of each month')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a href="{$_url}pool/add">{Lang::T('IP Pool')}</a></label>
                        <div class="col-md-6">
                            <select id="pool_name" name="pool_name" required class="form-control select2">
                                {foreach $p as $ps}
                                    <option value="{$ps['pool_name']}" {if $d['pool'] eq $ps['pool_name']} selected {/if}>
                                        {$ps['pool_name']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Router Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="routers" name="routers" value="{$d['routers']}"
                                readonly>
                        </div>
                    </div>
                    <legend>{Lang::T('Expired Action')} <sub>{Lang::T('Optional')}</sub></legend>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{$_url}pool/add">{Lang::T('Expired IP Pool')}</a></label>
                        <div class="col-md-6">
                            <select id="pool_expired" name="pool_expired" class="form-control select2">
                                <option value=''>{Lang::T('Select Pool')}</option>
                                {foreach $p as $ps}
                                    <option value="{$ps['pool_name']}" {if $d['pool_expired'] eq $ps['pool_name']} selected
                                        {/if}>{$ps['pool_name']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    {* <div class="form-group" id="AddressList">
                        <label class="col-md-2 control-label">{Lang::T('Address List')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="list_expired" id="list_expired" value="{$d['list_expired']}">
                        </div>
                    </div> *}
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success" type="submit">{Lang::T('Save Changes')}</button>
                            Or <a href="{$_url}services/pppoe">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    var preOpt = `<option value="Mins">{Lang::T('Mins')}</option>
    <option value="Hrs">{Lang::T('Hrs')}</option>
    <option value="Days">{Lang::T('Days')}</option>
    <option value="Months">{Lang::T('Months')}</option>`;
    var postOpt = `<option value="Period">{Lang::T('Period')}</option>`;
    function prePaid() {
        $("#validity_unit").html(preOpt);
    }

    function postPaid() {
        $("#validity_unit").html(postOpt);
    }
</script>
{if $_c['radius_enable'] && $d['is_radius']}
    {literal}
        <script>
            document.getElementById("routers").required = false;
            document.getElementById("routers").disabled = true;
            setTimeout(() => {
                $.ajax({
                    url: "index.php?_route=autoload/pool",
                    data: "routers=radius",
                    cache: false,
                    success: function(msg) {
                        $("#pool_expired").html(msg);
                    }
                });
            }, 2000);
        </script>
    {/literal}
{/if}
{include file="sections/footer.tpl"}