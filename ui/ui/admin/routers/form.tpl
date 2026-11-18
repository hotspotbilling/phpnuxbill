{*
    The router form, shared by add.tpl ($nf_edit false, $d empty) and edit.tpl ($nf_edit true, $d the router).
    Field names are what routers.php reads: add-post: enabled (0/1), name, ip_address + api_port (joined as host:port), username,
    password, description, site (once the database has it),
    testIt; edit-post: the same plus id, coordinates, coverage. csrf_token is assigned by boot.php.
    $routely_vpn (optional plugin): addresses, address => [device, state, port, proto], offered as suggestions.
*}
{$nf_addr = $d['ip_address']|default:''}
{$nf_routely = null}
{if $nf_addr !== '' && isset($routely_vpn['addresses'][$nf_addr])}{$nf_routely = $routely_vpn['addresses'][$nf_addr]}{/if}
<form class="ls st mc nf" method="post" role="form" autocomplete="off"
    action="{if $nf_edit}{Text::url('routers/edit-post')}{else}{Text::url('routers/add-post')}{/if}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $nf_edit}<input type="hidden" name="id" value="{$d['id']|escape}">{/if}

    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-server" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit Router')}{else}{Lang::T('Add Router')}{/if}</h3>
                        <small>{Lang::T('The MikroTik that PHPNuxBill manages through its API.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="name">{Lang::T('Router Name / Location')}</label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="32" required
                            value="{$d['name']|default:''|escape}" placeholder="{Lang::T('Main office')}">
                        <p class="nf__hint">{Lang::T('Name of Area that router operated')}</p>
                    </div>

                    <div class="nf__field">
                        <div class="nf__row nf__row--addr">
                            <div>
                                <label class="nf__label" for="ip_address">{Lang::T('IP Address')}</label>
                                <input type="text" class="form-control" id="ip_address" name="ip_address" maxlength="128"
                                    value="{$nf_host|default:''|escape}" placeholder="192.168.88.1"
                                    {if $routely_vpn['addresses']}list="nfRouterAddr"{/if} spellcheck="false">
                            </div>
                            <div>
                                <label class="nf__label" for="api_port">{Lang::T('API Port')}</label>
                                <input type="number" class="form-control" id="api_port" name="api_port" min="1" max="65535" step="1"
                                    value="{$nf_port|default:''|escape}" placeholder="8728">
                            </div>
                        </div>
                        {if $routely_vpn['addresses']}
                        <datalist id="nfRouterAddr">
                            {foreach $routely_vpn['addresses'] as $addr => $a}
                                {if ($a['proto']|default:'tcp') != 'udp'}<option value="{$addr|escape}" label="{$a['device']|escape} &rarr; {$a['port']|escape}"></option>{/if}
                            {/foreach}
                        </datalist>
                        {/if}
                        {if $nf_routely}
                        <p class="nf__hint nf__hint--routely">
                            <span class="ls-pill ls-pill--routely{if $nf_routely['state'] == 'down'} is-down{/if}"><i class="fa fa-shield" aria-hidden="true"></i> Routely</span>
                            {Lang::T('Through Routely VPN to')} <b>{$nf_routely['device']|escape}</b>, {Lang::T('port')} {$nf_routely['port']|escape}
                        </p>
                        {else}
                        <p class="nf__hint">{Lang::T('The router\'s IP or host name. Leave the port empty for the usual 8728.')}</p>
                        {/if}
                    </div>

                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="username">{Lang::T('Username')}</label>
                            <input type="text" class="form-control" id="username" name="username" maxlength="64"
                                value="{$d['username']|default:''|escape}" placeholder="admin" spellcheck="false">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="password">{Lang::T('Router Secret')}</label>
                            <div class="nf__secret">
                                <input type="password" class="form-control" id="password" name="password"
                                    value="{$d['password']|default:''|escape}" autocomplete="new-password" spellcheck="false">
                                <button type="button" class="nf__eye" data-nf-eye="password" aria-label="{Lang::T('Show')}" title="{Lang::T('Show')}"><i class="fa fa-eye" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="nf__test">
                        <button type="button" class="ls-btn" id="nfTestNow"><i class="fa fa-plug" aria-hidden="true"></i> {Lang::T('Test now')}</button>
                        <div class="nf__test-out" id="nfTestOut" role="status" aria-live="polite" hidden></div>
                    </div>

                    <div class="nf__field">
                        <label class="nf__label" for="description">{Lang::T('Description')} <small>{Lang::T('Optional')}</small></label>
                        <textarea class="form-control" id="description" name="description" rows="3">{$d['description']|default:''|escape}</textarea>
                        <p class="nf__hint">{Lang::T('Explain Coverage of router')}</p>
                    </div>

                    {if $has_site}
                    <div class="nf__field">
                        <label class="nf__label" for="site">{Lang::T('Site / Group')} <small>{Lang::T('Optional')}</small></label>
                        <input type="text" class="form-control" id="site" name="site" maxlength="32" list="nfSites"
                            value="{$d['site']|default:''|escape}" placeholder="{Lang::T('North zone')}">
                        <datalist id="nfSites">{foreach $sites as $st}<option value="{$st|escape}"></option>{/foreach}</datalist>
                        <p class="nf__hint">{Lang::T('Group routers by area or branch, then filter the list by it.')}</p>
                    </div>
                    {/if}
                </div>
            </section>

            {if $nf_edit}
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-map-marker" aria-hidden="true"></i> {Lang::T('Location')}</h3>
                        <small>{Lang::T('Click the map to place the router. Coverage draws the area it serves.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__row nf__row--loc">
                        <div class="nf__field">
                            <label class="nf__label" for="coordinates">{Lang::T('Coordinates')}</label>
                            <input type="text" class="form-control" id="coordinates" name="coordinates"
                                value="{$d['coordinates']|default:''|escape}" placeholder="6.465422, 3.406448" spellcheck="false">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="coverage">{Lang::T('Coverage')}</label>
                            <div class="nf__unit">
                                <input type="number" class="form-control" id="coverage" name="coverage" min="0" step="1"
                                    value="{$d['coverage']|default:''|escape}" placeholder="0">
                                <span aria-hidden="true">{Lang::T('meter(s)')}</span>
                            </div>
                        </div>
                    </div>
                    <div id="map" class="nf__map"></div>
                </div>
            </section>
            {/if}
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-toggle-on" aria-hidden="true"></i> {Lang::T('Status')}</h3></div>
                </header>
                <div class="mc-rows nf__switches">
                    <input type="hidden" name="enabled" value="0">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" name="enabled" value="1" {if ($d['enabled']|default:1) == 1}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-power-off" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Enabled')}</b>
                            <small>{Lang::T('Turn off to stop using this router without deleting it.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" name="testIt" value="yes" {if !$nf_edit}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-plug" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Test Connection')}</b>
                            <small>{Lang::T('Connect with these details before saving. If it cannot connect, nothing is saved.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                </div>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('routers/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{if $nf_edit}{Lang::T('Continue the process of changing Routers?')|escape:'javascript'}{else}{Lang::T('Continue the process of adding Routers?')|escape:'javascript'}{/if}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Save Changes')}{else}{Lang::T('Save')}{/if}</button>
                </footer>
            </section>
            {if !$nf_edit}
            <p class="nf__aside-note"><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('After saving, you can place the router on the map and set its coverage.')}</p>
            {/if}
        </aside>
    </div>
</form>

<script>
    // Test now: try the address and login above without saving, and say what answered (or why it did not)
    (function () {
        var btn = document.getElementById('nfTestNow');
        var out = document.getElementById('nfTestOut');
        if (!btn) return;
        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function show(ok, html) {
            out.hidden = false;
            out.className = 'nf__test-out ' + (ok ? 'is-ok' : 'is-bad');
            out.innerHTML = html;
        }
        btn.addEventListener('click', function () {
            var form = btn.form;
            var body = new FormData();
            ['csrf_token', 'id', 'ip_address', 'api_port', 'username', 'password'].forEach(function (n) {
                if (form.elements[n]) body.append(n, form.elements[n].value);
            });
            var icon = btn.querySelector('i');
            btn.disabled = true;
            icon.className = 'fa fa-circle-o-notch fa-spin';
            show(true, '{Lang::T('Connecting')|escape:'javascript'}&hellip;');
            out.className = 'nf__test-out';
            fetch('{Text::url('routers/test')}', { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) {
                        show(false, '<i class="fa fa-times-circle" aria-hidden="true"></i> <b>{Lang::T('Could not connect')|escape:'javascript'}</b><br>' + esc(d.error));
                        return;
                    }
                    var bits = [];
                    if (d.identity) bits.push('<b>' + esc(d.identity) + '</b>');
                    if (d.board) bits.push(esc(d.board));
                    if (d.version) bits.push('RouterOS ' + esc(d.version));
                    if (d.uptime) bits.push('{Lang::T('up')|escape:'javascript'} ' + esc(d.uptime));
                    show(true, '<i class="fa fa-check-circle" aria-hidden="true"></i> <b>{Lang::T('Connected')|escape:'javascript'}</b>'
                        + (bits.length ? '<br>' + bits.join(' &middot; ') : '') + (d.note ? '<br>' + esc(d.note) : ''));
                })
                .catch(function () {
                    show(false, '<i class="fa fa-times-circle" aria-hidden="true"></i> {Lang::T('The test could not be run. Reload the page and try again.')|escape:'javascript'}');
                })
                .finally(function () {
                    btn.disabled = false;
                    icon.className = 'fa fa-plug';
                });
        });
    })();

    // "host:port" typed or picked into the address (the Routely suggestions are like that) moves the port to its own field
    (function () {
        var host = document.getElementById('ip_address'), port = document.getElementById('api_port');
        if (!host || !port) return;
        host.addEventListener('change', function () {
            var m = /^([^:]+):(\d+)$/.exec(host.value.trim());
            if (m) {
                host.value = m[1];
                port.value = m[2];
            }
        });
    })();

    // show or hide a secret field
    document.querySelectorAll('[data-nf-eye]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var f = document.getElementById(btn.getAttribute('data-nf-eye'));
            var show = f.type === 'password';
            f.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
        });
    });
</script>
