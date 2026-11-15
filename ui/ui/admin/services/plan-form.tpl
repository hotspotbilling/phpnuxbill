{*
    The Hotspot, PPPoE and VPN package form, shared by hotspot/, pppoe/ and vpn/ add.tpl and edit.tpl.
    $nf_kind: 'hotspot', 'pppoe' or 'vpn'. $nf_edit: false on add ($d empty), true on edit ($d the plan).
    $nf_bw: the bandwidths (the controller assigns them as $d on add and $b on edit). $r: routers (add), $p: pools (edit,
    PPPoE and VPN), $exps: packages a customer moves to when this one expires (edit), $devices: device names.
    Field names are what services.php reads: name (Hotspot) or name_plan (PPPoE, VPN), enabled, prepaid, plan_type, radius
    (add), device, typebp, limit_type, time_limit, time_unit, data_limit, data_unit, sharedusers (Hotspot), id_bw, price,
    price_old (edit), validity, validity_unit, expired_date, routers, pool_name (PPPoE, VPN), plan_expired, on_login,
    on_logout (edit). csrf_token is assigned by boot.php. custom.js loads the pools of the chosen router into #pool_name
    and also shows #Type / #TimeLimit / #DataLimit from the #Unlimited / #Limited / #*_Limit radios, so those ids stay.
*}
{if $nf_kind == 'pppoe'}
    {$nf_name = 'name_plan'}{$nf_icon = 'fa-exchange'}{$nf_list = 'services/pppoe'}{$nf_dev = 'MikrotikPppoe'}
    {if $nf_edit}{$nf_post = 'services/edit-pppoe-post'}{$nf_ask = Lang::T('Continue the PPPoE Package change process?')}{else}{$nf_post = 'services/pppoe-add-post'}{$nf_ask = Lang::T('Continue the process of adding the PPPoE Package?')}{/if}
{elseif $nf_kind == 'vpn'}
    {$nf_name = 'name_plan'}{$nf_icon = 'fa-shield'}{$nf_list = 'services/vpn'}{$nf_dev = 'MikrotikVpn'}
    {if $nf_edit}{$nf_post = 'services/edit-vpn-post'}{$nf_ask = Lang::T('Continue the VPN Package change process?')}{else}{$nf_post = 'services/vpn-add-post'}{$nf_ask = Lang::T('Continue the VPN creation process?')}{/if}
{else}
    {$nf_name = 'name'}{$nf_icon = 'fa-wifi'}{$nf_list = 'services/hotspot'}{$nf_dev = 'MikrotikHotspot'}
    {if $nf_edit}{$nf_post = 'services/edit-post'}{$nf_ask = Lang::T('Continue the process of changing Hotspot Package data?')}{else}{$nf_post = 'services/add-post'}{$nf_ask = Lang::T('Continue the Hotspot Package creation process?')}{/if}
{/if}
{$nf_radius = $nf_edit && $d['is_radius']}
{$nf_prepaid = ($d['prepaid']|default:'yes') != 'no'}
{$nf_limited = ($d['typebp']|default:'Unlimited') == 'Limited'}
{$nf_lt = $d['limit_type']|default:'Time_Limit'}
{$nf_tax = 0}
{if $_c['enable_tax'] == 'yes'}{if $_c['tax_rate'] == 'custom'}{$nf_tax = floatval($_c['custom_tax_rate'])}{else}{$nf_tax = floatval($_c['tax_rate']) * 100}{/if}{/if}

<form class="ls st mc nf nf-plan" method="post" role="form" autocomplete="off" action="{Text::url($nf_post)}"
    data-kind="{$nf_kind}" data-device="{$nf_dev}" data-tax="{$nf_tax}" data-cur="{$_c['currency_code']|escape}"
    data-pool-url="{Text::url('autoload/pool')}"
    data-t-unlimited="{Lang::T('Unlimited')|escape}" data-t-devices="{Lang::T('devices at once')|escape}"
    data-t-device="{Lang::T('device at once')|escape}" data-t-day="{Lang::T('Expires on day')|escape}"
    data-t-month="{Lang::T('of each month')|escape}" data-t-tax="{Lang::T('incl. tax')|escape}" data-t-off="{Lang::T('off')|escape}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $nf_edit}<input type="hidden" name="id" value="{$d['id']|escape}">{/if}

    <template id="nfUnitsPre">
        <option value="Mins">{Lang::T('Mins')}</option>
        <option value="Hrs">{Lang::T('Hrs')}</option>
        <option value="Days">{Lang::T('Days')}</option>
        <option value="Months">{Lang::T('Months')}</option>
    </template>
    <template id="nfUnitsPost">
        <option value="Period">{Lang::T('Period')}</option>
        {if $nf_kind == 'pppoe'}<option value="Months">{Lang::T('Months')}</option>{/if}
    </template>

    <div class="nf__grid">
        <div class="nf__main">
            {* the package *}
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa {$nf_icon}" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit Service Package')}{else}{Lang::T('Add Service Package')}{/if}
                            {if $nf_radius}<span class="ls-pill nf-pill">RADIUS</span>{/if}</h3>
                        <small>{if $nf_kind == 'pppoe'}{Lang::T('A PPPoE package customers buy or are recharged with.')}{elseif $nf_kind == 'vpn'}{Lang::T('A VPN package customers buy or are recharged with.')}{else}{Lang::T('A hotspot package customers buy or are recharged with.')}{/if}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="{$nf_name}">{Lang::T('Package Name')}</label>
                        <input type="text" class="form-control" id="{$nf_name}" name="{$nf_name}" maxlength="40" required
                            value="{$d['name_plan']|default:''|escape}" placeholder="{if $nf_kind == 'hotspot'}{Lang::T('1 Day Unlimited')}{else}{Lang::T('Home 10 Mbps')}{/if}" data-nf-sum-name>
                    </div>

                    <div class="nf__row">
                        <div class="nf__field">
                            <span class="nf__label">{Lang::T('Type')}
                                <small>{Lang::T('Postpaid will have fix expired date')}</small></span>
                            <div class="nf-seg" role="radiogroup" aria-label="{Lang::T('Type')}">
                                <label><input type="radio" name="prepaid" value="yes" {if $nf_prepaid}checked{/if} data-nf-billing> <span>{Lang::T('Prepaid')}</span></label>
                                <label><input type="radio" name="prepaid" value="no" {if !$nf_prepaid}checked{/if} data-nf-billing> <span>{Lang::T('Postpaid')}</span></label>
                            </div>
                        </div>
                        <div class="nf__field">
                            <span class="nf__label">{Lang::T('Customer')}
                                <small>{Lang::T('Who sees it')}</small></span>
                            <div class="nf-seg" role="radiogroup" aria-label="{Lang::T('Customer')}">
                                <label><input type="radio" name="plan_type" value="Personal" {if ($d['plan_type']|default:'Personal') != 'Business'}checked{/if}> <span>{Lang::T('Personal')}</span></label>
                                <label><input type="radio" name="plan_type" value="Business" {if ($d['plan_type']|default:'') == 'Business'}checked{/if}> <span>{Lang::T('Business')}</span></label>
                            </div>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('Personal Package will only show to personal Customer, Business package will only show to Business Customer')}</p>

                    <div class="nf__row">
                        {* Radius first: ticking it picks the Radius device *}
                        {if !$nf_edit && $_c['radius_enable'] && $nf_kind != 'vpn'}
                        <div class="nf__field">
                            <span class="nf__label">Radius <small>{Lang::T('Cannot be change after saved')}</small></span>
                            <label class="nf-check">
                                <input type="checkbox" name="radius" value="1" data-nf-radius>
                                <span>{Lang::T('Radius Package')}</span>
                            </label>
                        </div>
                        {/if}
                        <div class="nf__field">
                            <label class="nf__label" for="device">{Lang::T('Device')}</label>
                            <select class="form-control" id="device" name="device" required>
                                {foreach $devices as $dev}
                                    <option value="{$dev|escape}" {if $nf_edit}{if $dev == $d['device']}selected{/if}{elseif $dev == $nf_dev}selected{/if}>{$dev|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('This Device are the logic how PHPNuxBill Communicate with Mikrotik or other Devices')}</p>
                </div>
            </section>

            {* speed and limits *}
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-tachometer" aria-hidden="true"></i> {if $nf_kind == 'hotspot'}{Lang::T('Speed and limits')}{else}{Lang::T('Speed')}{/if}</h3>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="id_bw">{Lang::T('Bandwidth Name')} <small><a href="{Text::url('bandwidth/add')}">{Lang::T('Add Bandwidth')}</a></small></label>
                        <select id="id_bw" name="id_bw" class="form-control select2" required>
                            {if !$nf_edit}<option value="">{Lang::T('Select Bandwidth')}...</option>{/if}
                            {foreach $nf_bw as $bs}
                                <option value="{$bs['id']|escape}" {if ($d['id_bw']|default:'') == $bs['id']}selected{/if}>{$bs['name_bw']|escape} &middot; {$bs['rate_down']|escape}{if $bs['rate_down_unit'] == 'Kbps'}K{else}M{/if} / {$bs['rate_up']|escape}{if $bs['rate_up_unit'] == 'Kbps'}K{else}M{/if}</option>
                            {/foreach}
                        </select>
                        <p class="nf__hint">{Lang::T('Download / upload speed.')}</p>
                    </div>

                    {if $nf_kind == 'hotspot'}
                    <div class="nf__row">
                        <div class="nf__field">
                            <span class="nf__label">{Lang::T('Usage')}</span>
                            <div class="nf-seg" role="radiogroup" aria-label="{Lang::T('Usage')}">
                                <label><input type="radio" id="Unlimited" name="typebp" value="Unlimited" {if !$nf_limited}checked{/if} data-nf-limit> <span>{Lang::T('Unlimited')}</span></label>
                                <label><input type="radio" id="Limited" name="typebp" value="Limited" {if $nf_limited}checked{/if} data-nf-limit> <span>{Lang::T('Limited')}</span></label>
                            </div>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="sharedusers">{Lang::T('Shared Users')}</label>
                            <input type="number" class="form-control" id="sharedusers" name="sharedusers" min="1" step="1" required
                                value="{$d['shared_users']|default:1|escape}" data-nf-sum>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('How many devices can online in one Customer account')}</p>

                    <div id="Type" class="nf__sub" {if !$nf_limited}style="display:none"{/if}>
                        <span class="nf__label">{Lang::T('Limit Type')}</span>
                        <div class="nf-seg nf-seg--3" role="radiogroup" aria-label="{Lang::T('Limit Type')}">
                            <label><input type="radio" id="Time_Limit" name="limit_type" value="Time_Limit" {if $nf_lt == 'Time_Limit'}checked{/if} data-nf-limit> <span>{Lang::T('Time Limit')}</span></label>
                            <label><input type="radio" id="Data_Limit" name="limit_type" value="Data_Limit" {if $nf_lt == 'Data_Limit'}checked{/if} data-nf-limit> <span>{Lang::T('Data Limit')}</span></label>
                            <label><input type="radio" id="Both_Limit" name="limit_type" value="Both_Limit" {if $nf_lt == 'Both_Limit'}checked{/if} data-nf-limit> <span>{Lang::T('Both Limit')}</span></label>
                        </div>
                        <div class="nf__row nf__sub-row">
                            <div id="TimeLimit" class="nf__field" {if !$nf_limited || $nf_lt == 'Data_Limit'}style="display:none"{/if}>
                                <label class="nf__label" for="time_limit">{Lang::T('Time Limit')}</label>
                                <div class="nf-pair">
                                    <input type="number" class="form-control" id="time_limit" name="time_limit" min="0" step="1"
                                        value="{$d['time_limit']|default:0|escape}" data-nf-sum>
                                    <select class="form-control" id="time_unit" name="time_unit" data-nf-sum>
                                        <option value="Hrs" {if ($d['time_unit']|default:'Hrs') == 'Hrs'}selected{/if}>{Lang::T('Hrs')}</option>
                                        <option value="Mins" {if ($d['time_unit']|default:'') == 'Mins'}selected{/if}>{Lang::T('Mins')}</option>
                                    </select>
                                </div>
                            </div>
                            <div id="DataLimit" class="nf__field" {if !$nf_limited || $nf_lt == 'Time_Limit'}style="display:none"{/if}>
                                <label class="nf__label" for="data_limit">{Lang::T('Data Limit')}</label>
                                <div class="nf-pair">
                                    <input type="number" class="form-control" id="data_limit" name="data_limit" min="0" step="1"
                                        value="{$d['data_limit']|default:0|escape}" data-nf-sum>
                                    <select class="form-control" id="data_unit" name="data_unit" data-nf-sum>
                                        <option value="MB" {if ($d['data_unit']|default:'MB') == 'MB'}selected{/if}>MB</option>
                                        <option value="GB" {if ($d['data_unit']|default:'') == 'GB'}selected{/if}>GB</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    {/if}
                </div>
            </section>

            {* price and validity *}
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-tag" aria-hidden="true"></i> {Lang::T('Price and validity')}</h3></div>
                </header>
                <div class="st-card-body">
                    {* one row that wraps: price, price before discount (edit), validity, expiry day (postpaid) *}
                    <div class="nf__row nf__row--fit">
                        <div class="nf__field">
                            <label class="nf__label" for="price">{Lang::T('Package Price')}
                                {if $nf_tax > 0}<small>{number_format($nf_tax, 2)} % {Lang::T('Tax Rates will be added')}</small>{/if}</label>
                            <div class="nf__money"><span aria-hidden="true">{$_c['currency_code']|escape}</span>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="any" required
                                    value="{$d['price']|default:''|escape}" placeholder="0" data-nf-sum></div>
                        </div>
                        {if $nf_edit}
                        <div class="nf__field">
                            <label class="nf__label" for="price_old">{Lang::T('Price Before Discount')} <small>{Lang::T('Optional')}</small></label>
                            <div class="nf__money"><span aria-hidden="true">{$_c['currency_code']|escape}</span>
                                <input type="number" class="form-control" id="price_old" name="price_old" min="0" step="any"
                                    value="{$d['price_old']|default:''|escape}" placeholder="0" data-nf-sum></div>
                        </div>
                        {/if}
                        <div class="nf__field nf__field--pair">
                            <label class="nf__label" for="validity">{Lang::T('Package Validity')}</label>
                            <div class="nf-pair">
                                <input type="number" class="form-control" id="validity" name="validity" min="0" step="1" required
                                    value="{$d['validity']|default:''|escape}" placeholder="30" data-nf-sum>
                                <select class="form-control" id="validity_unit" name="validity_unit" data-nf-sum data-value="{$d['validity_unit']|default:'Days'|escape}"></select>
                            </div>
                        </div>
                        <div class="nf__field" id="expired_date" {if $nf_prepaid}style="display:none"{/if}>
                            <label class="nf__label" for="expired_day">{Lang::T('Expired Date')} <small>{Lang::T('Day of the month')}</small></label>
                            <input type="number" class="form-control" id="expired_day" name="expired_date" min="1" max="28" step="1"
                                value="{if ($d['expired_date']|default:0) >= 1 && ($d['expired_date']|default:0) <= 28}{$d['expired_date']|escape}{else}20{/if}" {if $nf_prepaid}disabled{/if} data-nf-sum>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{if $nf_edit}{Lang::T('For Discount Rate, this is price before get discount, must be more expensive with real price')}. {/if}{Lang::T('1 Period = 1 Month, Expires the 20th of each month')}</p>
                </div>
            </section>

            {* router and pool *}
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-server" aria-hidden="true"></i> {Lang::T('Router')}</h3></div>
                </header>
                <div class="st-card-body">
                    {* one row: the router and (PPPoE, VPN) its IP pool *}
                    <div class="nf__row nf__row--fit">
                        {if $nf_edit}
                        <input type="hidden" name="routers" value="{$d['routers']|default:''|escape}">
                        <div class="nf__field">
                            <label class="nf__label" for="nf_router_ro">{Lang::T('Router Name')}</label>
                            <input type="text" class="form-control nf__ro" id="nf_router_ro" readonly tabindex="-1"
                                value="{if $nf_radius}RADIUS{else}{$d['routers']|escape}{/if}">
                        </div>
                        {else}
                        <div class="nf__field" data-nf-router-field>
                            <label class="nf__label" for="routers">{Lang::T('Router Name')} <small><a href="{Text::url('routers/add')}">{Lang::T('Add Router')}</a></small></label>
                            <select id="routers" name="routers" class="form-control select2" required>
                                <option value="">{Lang::T('Select Routers')}</option>
                                {foreach $r as $rs}
                                    <option value="{$rs['name']|escape}">{$rs['name']|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                        {/if}
                        {if $nf_kind != 'hotspot'}
                        <div class="nf__field">
                            <label class="nf__label" for="pool_name">{Lang::T('IP Pool')} <small><a href="{Text::url('pool/add')}">{Lang::T('Add Pool')}</a></small></label>
                            <select id="pool_name" name="pool_name" class="form-control select2" required>
                                {if $nf_edit}
                                    {foreach $p as $ps}
                                        <option value="{$ps['pool_name']|escape}" {if $d['pool'] == $ps['pool_name']}selected{/if}>{$ps['pool_name']|escape}</option>
                                    {/foreach}
                                {else}
                                    <option value="">{Lang::T('Select Pool')}</option>
                                {/if}
                            </select>
                        </div>
                        {/if}
                    </div>
                    <p class="nf__hint nf__hint--tight">{if $nf_edit}{Lang::T('The router cannot be changed after the package is saved.')}{else}{Lang::T('Cannot be change after saved')}.{if $nf_kind != 'hotspot'} {Lang::T('The pools of the chosen router.')}{/if}{/if}</p>
                </div>
            </section>

            {if $nf_edit}
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-hourglass-end" aria-hidden="true"></i> {Lang::T('Expired Action')}</h3>
                        <small>{Lang::T('When Expired, customer will be move to selected internet package')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="plan_expired">{Lang::T('Expired Internet Package')} <small>{Lang::T('Optional')}</small></label>
                        <select id="plan_expired" name="plan_expired" class="form-control select2">
                            <option value="0">{Lang::T('Default - Remove Customer')}</option>
                            {foreach $exps as $exp}
                                {if $exp['id'] != $d['id']}<option value="{$exp['id']|escape}" {if $d['plan_expired'] == $exp['id']}selected{/if}>{$exp['name_plan']|escape}</option>{/if}
                            {/foreach}
                        </select>
                    </div>
                </div>
            </section>

            {if !$nf_radius}
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-code" aria-hidden="true"></i> {Lang::T('Router scripts')} <small class="nf__inline-opt">{Lang::T('Optional')}</small></h3>
                        <small>{Lang::T('RouterOS script the router runs when a customer on this package connects and disconnects.')}</small>
                    </div>
                </header>
                <div class="st-card-body nf-code">
                    <div class="nf__field">
                        <label class="nf__label" for="code">{Lang::T('on-login / on-up')}</label>
                        <textarea class="form-control" id="code" name="on_login" rows="8" spellcheck="false">{$d['on_login']|default:''|escape}</textarea>
                    </div>
                    <div class="nf__field">
                        <label class="nf__label" for="code2">{Lang::T('on-logout / on-down')}</label>
                        <textarea class="form-control" id="code2" name="on_logout" rows="8" spellcheck="false">{$d['on_logout']|default:''|escape}</textarea>
                    </div>
                </div>
            </section>
            {else}
            {* not shown for a RADIUS package, but kept: the save writes both fields *}
            <input type="hidden" name="on_login" value="{$d['on_login']|default:''|escape}">
            <input type="hidden" name="on_logout" value="{$d['on_logout']|default:''|escape}">
            {/if}
            {/if}
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-eye" aria-hidden="true"></i> {Lang::T('Summary')}</h3></div>
                </header>
                <div class="mc-rows nf__switches">
                    <input type="hidden" name="enabled" value="0">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" name="enabled" value="1" {if ($d['enabled']|default:1) == 1}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-power-off" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Active')}</b>
                            <small>{Lang::T('Customer cannot buy disabled Package, but admin can recharge it, use it if you want only admin recharge it')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                </div>
                <dl class="nf__facts nf-sum" aria-live="polite">
                    <div><dt>{Lang::T('Price')}</dt><dd data-sum="price">&ndash;</dd></div>
                    <div><dt>{Lang::T('Validity')}</dt><dd data-sum="validity">&ndash;</dd></div>
                    <div><dt>{Lang::T('Speed')}</dt><dd data-sum="speed">&ndash;</dd></div>
                    {if $nf_kind == 'hotspot'}
                    <div><dt>{Lang::T('Usage')}</dt><dd data-sum="usage">&ndash;</dd></div>
                    <div><dt>{Lang::T('Shared Users')}</dt><dd data-sum="shared">&ndash;</dd></div>
                    {/if}
                </dl>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url($nf_list)}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{$nf_ask|escape:'javascript'}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
(function () {
    var form = document.querySelector('form.nf-plan');
    var $ = function (s) { return form.querySelector(s); };
    var t = function (k) { return form.getAttribute('data-t-' + k); };
    var kind = form.getAttribute('data-kind');
    var unit = $('#validity_unit');

    // the validity units: prepaid counts minutes to months, postpaid runs in periods (a month that ends on a set day)
    var units = function () {
        var pre = $('input[name=prepaid][value=yes]').checked;
        var want = unit.value || unit.getAttribute('data-value');
        unit.innerHTML = document.getElementById(pre ? 'nfUnitsPre' : 'nfUnitsPost').innerHTML;
        if (Array.prototype.some.call(unit.options, function (o) { return o.value === want; })) unit.value = want;
        else if (pre) unit.value = 'Days';
        $('#expired_date').style.display = pre ? 'none' : '';
        $('#expired_day').disabled = pre; // prepaid: the day is not used, so it is neither checked nor sent
    };
    form.querySelectorAll('[data-nf-billing]').forEach(function (r) { r.addEventListener('change', function () { units(); sum(); }); });
    units();

    // limits (Hotspot): the same as custom.js does, also on load
    var limits = function () {
        if (kind !== 'hotspot') return;
        var limited = $('#Limited').checked, lt = form.querySelector('input[name=limit_type]:checked');
        lt = lt ? lt.value : 'Time_Limit';
        $('#Type').style.display = limited ? '' : 'none';
        $('#TimeLimit').style.display = limited && lt !== 'Data_Limit' ? '' : 'none';
        $('#DataLimit').style.display = limited && lt !== 'Time_Limit' ? '' : 'none';
        // a limit that is shown must be filled in (the database keeps whole numbers); a hidden one is still sent, so an
        // invalid value left in it (1.5, -1) goes back to 0 instead of silently blocking Save
        [['#time_limit', limited && lt !== 'Data_Limit'], ['#data_limit', limited && lt !== 'Time_Limit']].forEach(function (x) {
            var f = $(x[0]);
            f.required = x[1];
            if (!x[1] && !f.checkValidity()) f.value = '0';
        });
    };
    form.querySelectorAll('[data-nf-limit]').forEach(function (r) { r.addEventListener('change', function () { limits(); sum(); }); });

    // a RADIUS package (add): no router; its device is Radius, and PPPoE takes the pools kept for Radius
    var rad = $('[data-nf-radius]');
    if (rad) {
        var dev = $('#device'), routers = $('#routers'), field = $('[data-nf-router-field]');
        rad.addEventListener('change', function () {
            var on = rad.checked, def = form.getAttribute('data-device');
            if (on && Array.prototype.some.call(dev.options, function (o) { return o.value === 'Radius'; }) && dev.value === def) dev.value = 'Radius';
            if (!on && dev.value === 'Radius') dev.value = def;
            routers.required = !on;
            routers.disabled = on && kind !== 'hotspot';
            if (kind === 'hotspot') field.style.display = on ? 'none' : '';
            if (on && kind !== 'hotspot' && window.jQuery) {
                jQuery.ajax({ url: form.getAttribute('data-pool-url'), data: 'routers=radius', cache: false, success: function (msg) { jQuery('#pool_name').html(msg); } });
            } else if (!on && kind !== 'hotspot' && window.jQuery) {
                jQuery('#routers').trigger('change'); // custom.js loads the router's pools again
            }
        });
    }

    // the summary on the side
    var cur = form.getAttribute('data-cur'), tax = parseFloat(form.getAttribute('data-tax')) || 0;
    var money = function (n) { return cur + ' ' + n.toLocaleString(undefined, { maximumFractionDigits: 2 }); };
    var put = function (k, v) { var e = form.querySelector('[data-sum="' + k + '"]'); if (e) e.textContent = v || '–'; };
    var sel = function (s) { var e = $(s); return e && e.selectedIndex >= 0 ? e.options[e.selectedIndex].text.trim() : ''; };
    var sum = function () {
        var price = parseFloat($('#price').value), old = $('#price_old') ? parseFloat($('#price_old').value) : NaN;
        if (isFinite(price)) {
            var s = money(tax > 0 ? price * (1 + tax / 100) : price) + (tax > 0 ? ' (' + t('tax') + ')' : '');
            if (isFinite(old) && old > price && old > 0) s += ' · ' + Math.round((1 - price / old) * 100) + '% ' + t('off');
            put('price', s);
        } else put('price', '');
        var v = $('#validity').value;
        if (!$('input[name=prepaid][value=yes]').checked && unit.value === 'Period') {
            put('validity', (v ? v + ' × ' : '') + sel('#validity_unit') + ' · ' + t('day') + ' ' + ($('#expired_day').value || 20) + ' ' + t('month'));
        } else put('validity', v ? v + ' ' + sel('#validity_unit') : '');
        var bw = $('#id_bw'); put('speed', bw && bw.value ? sel('#id_bw') : '');
        if (kind === 'hotspot') {
            if (!$('#Limited').checked) put('usage', t('unlimited'));
            else {
                var lt = form.querySelector('input[name=limit_type]:checked').value, parts = [];
                if (lt !== 'Data_Limit') parts.push(($('#time_limit').value || 0) + ' ' + sel('#time_unit'));
                if (lt !== 'Time_Limit') parts.push(($('#data_limit').value || 0) + ' ' + sel('#data_unit'));
                put('usage', parts.join(' + '));
            }
            var n = parseInt($('#sharedusers').value, 10);
            put('shared', isFinite(n) ? n + ' ' + (n === 1 ? t('device') : t('devices')) : '');
        }
    };
    form.querySelectorAll('[data-nf-sum], #id_bw, #validity_unit').forEach(function (e) { e.addEventListener('input', sum); e.addEventListener('change', sum); });
    if (window.jQuery) jQuery('#id_bw').on('change', sum); // select2 fires jQuery's change
    limits();
    sum();
})();
</script>
{/literal}
