{*
    The port pool form, shared by add.tpl ($nf_edit false, $d empty, $r the routers, $used_routers those that have one) and
    edit.tpl ($nf_edit true, $d the port pool). Field names are what pool.php reads: add-port-post: name, public_ip,
    port_range, routers; edit-port-post: id, name, public_ip, range_port, routers (the router cannot change).
    csrf_token is assigned by boot.php. A VPN plan gives each customer three ports from the range (Winbox, API and Web),
    forwarded from the router's public IP; a router has one port pool.
*}
<form class="ls st mc nf" method="post" role="form" autocomplete="off"
    action="{if $nf_edit}{Text::url('pool/edit-port-post')}{else}{Text::url('pool/add-port-post')}{/if}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $nf_edit}
    <input type="hidden" name="id" value="{$d['id']|escape}">
    <input type="hidden" name="routers" value="{$d['routers']|escape}">
    {/if}

    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-random" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit Port')}{else}{Lang::T('Add Port Pool')}{/if}</h3>
                        <small>{Lang::T('Ports on the router\'s public IP that VPN customers reach their own router through.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="name">{Lang::T('Port Name')}</label>
                            <input type="text" class="form-control" id="name" name="name" minlength="3" maxlength="30" required
                                value="{$d['port_name']|default:''|escape}" placeholder="VPN tunnel">
                        </div>
                        {if !$nf_edit}
                        <div class="nf__field">
                            <label class="nf__label" for="routers">{Lang::T('Routers')} <small><a href="{Text::url('routers/add')}">{Lang::T('Add Router')}</a></small></label>
                            <select id="routers" name="routers" class="form-control select2" required>
                                {foreach $r as $rs}
                                    {$taken = in_array($rs['name'], $used_routers)}
                                    <option value="{$rs['name']|escape}" {if $taken}disabled{/if}>{$rs['name']|escape}{if $taken} ({Lang::T('has one')}){/if}</option>
                                {/foreach}
                            </select>
                        </div>
                        {/if}
                    </div>
                    {if !$nf_edit}<p class="nf__hint nf__hint--tight">{Lang::T('Each router can have one port pool.')}</p>{/if}

                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="public_ip">{Lang::T('Public IP')}</label>
                            <input type="text" class="form-control" id="public_ip" name="public_ip" maxlength="40"
                                value="{$d['public_ip']|default:''|escape}" placeholder="12.34.56.78" spellcheck="false" inputmode="decimal">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="{if $nf_edit}range_port{else}port_range{/if}">{Lang::T('Range Port')}</label>
                            <input type="text" class="form-control" id="{if $nf_edit}range_port{else}port_range{/if}" name="{if $nf_edit}range_port{else}port_range{/if}"
                                maxlength="11" required pattern="\s*\d{literal}{1,5}{/literal}\s*-\s*\d{literal}{1,5}{/literal}\s*" data-nf-ports
                                value="{$d['range_port']|default:''|escape}" placeholder="30000-31000" spellcheck="false" inputmode="numeric">
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('Public IP: the address customers connect to. Range Port: first-last, ports the router does not use itself.')}</p>

                    <div class="nf__calc" data-nf-calc aria-live="polite"
                        data-t-ports="{Lang::T('ports')|escape}" data-t-customers="{Lang::T('enough for')|escape}"
                        data-t-cust="{Lang::T('VPN customers, at three ports each')|escape}"
                        data-t-bad="{Lang::T('Use first-last, from 1 to 65535, such as 30000-31000')|escape}"
                        data-t-low="{Lang::T('Ports below 1024 include ones the router uses itself, such as 80 and 443.')|escape}" hidden>
                        <span class="nf__calc-main"></span>
                        <span class="nf__calc-warn"></span>
                    </div>
                </div>
            </section>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-server" aria-hidden="true"></i> {Lang::T('Router')}</h3></div>
                </header>
                <div class="st-card-body">
                    {if $nf_edit}
                    <dl class="nf__facts">
                        <div><dt>{Lang::T('Routers')}</dt><dd>{$d['routers']|escape}</dd></div>
                    </dl>
                    <p class="nf__hint">{Lang::T('The router cannot be changed. To move a port pool, remove it and add it again.')}</p>
                    {/if}
                    <p class="nf__hint"><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('Ports already given to customers keep working; a new range applies to new VPN customers.')}</p>
                </div>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('pool/port')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{if $nf_edit}{Lang::T('Continue the Port change process?')|escape:'javascript'}{else}{Lang::T('Continue the process of adding Ports?')|escape:'javascript'}{/if}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Save Changes')}{else}{Lang::T('Save')}{/if}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
(function () {
    var box = document.querySelector('[data-nf-calc]');
    var f = document.querySelector('[data-nf-ports]');
    var t = function (k) { return box.getAttribute('data-t-' + k); };
    var main = box.querySelector('.nf__calc-main'), warn = box.querySelector('.nf__calc-warn');
    var update = function () {
        var v = f.value.trim(), m = /^(\d{1,5})\s*-\s*(\d{1,5})$/.exec(v);
        var a = m ? +m[1] : 0, b = m ? +m[2] : 0, ok = m && a >= 1 && b <= 65535 && a <= b;
        main.textContent = ''; warn.textContent = ''; box.classList.remove('is-bad');
        f.setCustomValidity(v && !ok ? t('bad') : '');
        if (v && !ok) {
            box.classList.add('is-bad');
            warn.textContent = t('bad');
        } else if (ok) {
            var n = b - a + 1;
            main.textContent = n.toLocaleString() + ' ' + t('ports') + ' · ' + t('customers') + ' ' + Math.floor(n / 3).toLocaleString() + ' ' + t('cust');
            if (a < 1024) warn.textContent = t('low');
        }
        box.hidden = !main.textContent && !warn.textContent;
    };
    f.addEventListener('input', update);
    update();
})();
</script>
{/literal}
