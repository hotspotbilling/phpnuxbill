{*
    The NAS form, shared by nas-add.tpl ($nf_edit false, $d empty) and nas-edit.tpl ($nf_edit true, $d the nas row).
    Field names are what radius.php reads (nas-add-post / nas-edit-post): shortname, nasname, secret, ports, type, server,
    community, description, routers, coa. csrf_token is assigned by boot.php. The CoA address is kept in tbl_appconfig
    as radius_coa_{id}. $routely_vpn (optional plugin): addresses, address => [device, state, port, proto].
*}
{$nf_coa = ''}
{if $nf_edit}{$nf_coa = $_c['radius_coa_'|cat:$d['id']]|default:''}{/if}
<form class="ls st mc nf" method="post" role="form" autocomplete="off"
    action="{if $nf_edit}{Text::url('radius/nas-edit-post/')}{$d['id']|escape}{else}{Text::url('radius/nas-add-post')}{/if}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">

    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-shield" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit NAS')}{else}{Lang::T('Add NAS')}{/if}</h3>
                        <small>{Lang::T('A router that sends RADIUS requests to this server.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="shortname">{Lang::T('Router Name')}</label>
                            <input type="text" class="form-control" id="shortname" name="shortname" maxlength="32" required
                                value="{$d['shortname']|default:''|escape}" placeholder="{Lang::T('Main office')}">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="routers">{Lang::T('Routers')} <small><a href="{Text::url('routers/add')}">{Lang::T('Add Router')}</a></small></label>
                            <select id="routers" name="routers" class="form-control select2">
                                <option value="">{Lang::T('No Router')}</option>
                                {foreach $routers as $rs}
                                    <option value="{$rs['name']|escape}" {if $rs['name'] == ($d['routers']|default:'')}selected{/if}>{$rs['name']|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('Name of Area that router operated')}. {Lang::T('Assign NAS to Router')}.</p>

                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="nasname">{Lang::T('IP Address')}</label>
                            <input type="text" class="form-control" id="nasname" name="nasname" maxlength="128" required
                                value="{$d['nasname']|default:''|escape}" placeholder="192.168.88.1" spellcheck="false">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="secret">{Lang::T('Secret')}</label>
                            <div class="nf__secret">
                                <input type="password" class="form-control" id="secret" name="secret" maxlength="60" required
                                    value="{$d['secret']|default:''|escape}" autocomplete="new-password" spellcheck="false">
                                <button type="button" class="nf__eye" data-nf-eye="secret" aria-label="{Lang::T('Show')}" title="{Lang::T('Show')}"><i class="fa fa-eye" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('The address RADIUS requests come from, and the secret set on the router for this server.')}</p>

                    <div class="nf__field">
                        <label class="nf__label" for="description">{Lang::T('Description')} <small>{Lang::T('Optional')}</small></label>
                        <textarea class="form-control" id="description" name="description" rows="3">{$d['description']|default:''|escape}</textarea>
                        <p class="nf__hint">{Lang::T('Explain Coverage of router')}</p>
                    </div>
                </div>
            </section>

            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-plug" aria-hidden="true"></i> {Lang::T('Disconnects (CoA)')}</h3>
                        <small>{Lang::T('Where PHPNuxBill sends a disconnect when a customer expires or is changed.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__row nf__row--coa">
                        <div class="nf__field">
                            <label class="nf__label" for="ports">{Lang::T('Port')}</label>
                            <input type="number" class="form-control" id="ports" name="ports" min="1" max="65535"
                                value="{$d['ports']|default:''|escape}" placeholder="3799">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="coa">{Lang::T('CoA address')} <small>{Lang::T('Optional')}</small></label>
                            <input type="text" class="form-control" id="coa" name="coa" maxlength="260"
                                value="{$nf_coa|escape}" placeholder="vpn1.example.com:34567" spellcheck="false"
                                {if $routely_vpn['addresses']}list="nfCoaAddr"{/if}>
                            {if $routely_vpn['addresses']}
                            <datalist id="nfCoaAddr">
                                {foreach $routely_vpn['addresses'] as $addr => $a}
                                    {if ($a['proto']|default:'') == 'udp'}<option value="{$addr|escape}" label="{$a['device']|escape} &rarr; {$a['port']|escape}"></option>{/if}
                                {/foreach}
                            </datalist>
                            {/if}
                        </div>
                    </div>
                    <p class="nf__hint">{Lang::T('Port: the router\'s CoA port, 3799 when empty. CoA address: for a router with no public IP, where disconnects are sent instead of IP address and port, such as a Routely VPN forward to UDP 3799.')}</p>
                </div>
            </section>

            <details class="ls-card nf__adv" {if ($d['server']|default:'') != '' || ($d['community']|default:'') != '' || ($d['type']|default:'other') != 'other'}open{/if}>
                <summary>
                    <i class="fa fa-sliders" aria-hidden="true"></i>
                    <span class="nf__adv-text">{Lang::T('Advanced')}
                        <small>{Lang::T('Type, server and SNMP community. Most routers need none of these.')}</small></span>
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </summary>
                <div class="st-card-body">
                    <div class="nf__row nf__row--3">
                        <div class="nf__field">
                            <label class="nf__label" for="type">{Lang::T('Type')}</label>
                            <input type="text" class="form-control" id="type" name="type" required
                                value="{$d['type']|default:'other'|escape}" placeholder="other" spellcheck="false">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="server">{Lang::T('Server')}</label>
                            <input type="text" class="form-control" id="server" name="server"
                                value="{$d['server']|default:''|escape}" placeholder="{Lang::T('None')}" spellcheck="false">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="community">{Lang::T('Community')}</label>
                            <input type="text" class="form-control" id="community" name="community"
                                value="{$d['community']|default:''|escape}" placeholder="{Lang::T('None')}" spellcheck="false">
                        </div>
                    </div>
                </div>
            </details>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-check-circle" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Save Changes')}{else}{Lang::T('Add NAS')}{/if}</h3>
                        <small>{Lang::T('FreeRADIUS reads the NAS list when it starts: restart it after adding or changing one.')}</small>
                    </div>
                </header>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('radius/nas-list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{if $nf_edit}{Lang::T('Continue the process of changing the Radius of the NAS?')|escape:'javascript'}{else}{Lang::T('Continue the process of adding Radius NAS?')|escape:'javascript'}{/if}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

<script>
    // show or hide a secret field
    document.querySelectorAll('[data-nf-eye]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var f = document.getElementById(btn.getAttribute('data-nf-eye'));
            var show = f.type === 'password';
            f.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
        });
    });
    // a required field inside the folded Advanced part: open it so the browser can point at the field
    document.querySelectorAll('form.nf').forEach(function (form) {
        form.addEventListener('invalid', function (e) {
            var d = e.target.closest('details');
            if (d) d.open = true;
        }, true);
    });
</script>
