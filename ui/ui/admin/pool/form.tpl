{*
    The IP pool form, shared by add.tpl ($nf_edit false, $d empty, $r the routers) and edit.tpl ($nf_edit true, $d the pool).
    Field names are what pool.php reads: add-post: name, local_ip, ip_address, routers; edit-post: id, local_ip, ip_address,
    routers (name and router cannot change). csrf_token is assigned by boot.php. On a MikroTik the pool is created as
    /ip pool with these ranges; Local IP becomes the PPP profile's local address (the pool name when empty).
*}
<form class="ls st mc nf" method="post" role="form" autocomplete="off"
    action="{if $nf_edit}{Text::url('pool/edit-post')}{else}{Text::url('pool/add-post')}{/if}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $nf_edit}
    <input type="hidden" name="id" value="{$d['id']|escape}">
    <input type="hidden" name="name" value="{$d['pool_name']|escape}">
    <input type="hidden" name="routers" value="{$d['routers']|escape}">
    {/if}

    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-sitemap" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit Pool')}{else}{Lang::T('Add Pool')}{/if}</h3>
                        <small>{Lang::T('The addresses PPPoE and VPN customers get when they connect.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    {if !$nf_edit}
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="name">{Lang::T('Name Pool')}</label>
                            <input type="text" class="form-control" id="name" name="name" minlength="3" maxlength="30" required
                                placeholder="pppoe-pool" spellcheck="false">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="routers">{Lang::T('Routers')} <small><a href="{Text::url('routers/add')}">{Lang::T('Add Router')}</a></small></label>
                            <select id="routers" name="routers" class="form-control select2" required data-nf-router>
                                {if $_c['radius_enable']}<option value="radius">Radius</option>{/if}
                                {foreach $r as $rs}
                                    <option value="{$rs['name']|escape}">{$rs['name']|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('The name is also the pool\'s name on the router, and cannot be changed later.')}</p>
                    {/if}

                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="local_ip">{Lang::T('Local IP')} <small>{Lang::T('Optional')}</small></label>
                            <input type="text" class="form-control" id="local_ip" name="local_ip" maxlength="40"
                                value="{$d['local_ip']|default:''|escape}" placeholder="192.168.88.1" spellcheck="false" inputmode="decimal">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="ip_address">{Lang::T('Range IP')}</label>
                            <input type="text" class="form-control" id="ip_address" name="ip_address" maxlength="40" required
                                value="{$d['range_ip']|default:''|escape}" placeholder="192.168.88.2-192.168.88.254" spellcheck="false">
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('Local IP: the router\'s own address in the customers\' tunnels; empty uses the pool name. Range IP: first-last, such as 10.0.0.2-10.0.0.254.')}</p>

                    <div class="nf__calc" data-nf-calc aria-live="polite"
                        data-t-addresses="{Lang::T('addresses')|escape}" data-t-address="{Lang::T('address')|escape}"
                        data-t-bad="{Lang::T('The router will not accept this range')|escape}"
                        data-t-inside="{Lang::T('The local IP is inside the range: a customer could be given the router\'s own address.')|escape}"
                        data-t-one="{Lang::T('VPN packages can only give out addresses from one first-last range inside a /24, such as 10.0.0.2-10.0.0.254.')|escape}"
                        data-t-use="{Lang::T('Use')|escape}" hidden>
                        <span class="nf__calc-main"></span>
                        <span class="nf__calc-warn"></span>
                        <button type="button" class="ls-btn nf__calc-use" hidden></button>
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
                        <div><dt>{Lang::T('Name Pool')}</dt><dd>{$d['pool_name']|escape}</dd></div>
                        <div><dt>{Lang::T('Routers')}</dt><dd>{$d['routers']|escape}</dd></div>
                    </dl>
                    <p class="nf__hint">{Lang::T('The name and router cannot be changed. To move a pool, remove it and add it again.')}</p>
                    {/if}
                    <p class="nf__hint" data-nf-radius {if !$nf_edit || $d['routers'] != 'radius'}hidden{/if}>
                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                        {Lang::T('For Radius, you need to add')} <b>{Lang::T('Name Pool')}</b> {Lang::T('in Mikrotik manually')}.
                    </p>
                    <p class="nf__hint" data-nf-mikrotik {if $nf_edit && $d['routers'] == 'radius'}hidden{/if}>
                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                        {Lang::T('Saving creates or updates the pool on the router, so it must be reachable.')}
                    </p>
                </div>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('pool/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{if $nf_edit}{Lang::T('Continue the Pool change process?')|escape:'javascript'}{else}{Lang::T('Continue the Pool addition process?')|escape:'javascript'}{/if}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
(function () {
    var box = document.querySelector('[data-nf-calc]');
    var local = document.getElementById('local_ip');
    var range = document.getElementById('ip_address');
    var t = function (k) { return box.getAttribute('data-t-' + k); };
    var ip = function (s) {
        var m = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/.exec(String(s).trim());
        if (!m) return null;
        var n = 0;
        for (var i = 1; i <= 4; i++) { var o = +m[i]; if (o > 255) return null; n = n * 256 + o; }
        return n;
    };
    var txt = function (n) { return [n >>> 24 & 255, n >>> 16 & 255, n >>> 8 & 255, n & 255].join('.'); };
    // the ranges as [first, last] pairs, or null when one part is not a.b.c.d, a.b.c.d-a.b.c.d or a.b.c.d/len
    var parse = function (s) {
        var out = [];
        var parts = String(s).split(',');
        for (var i = 0; i < parts.length; i++) {
            var p = parts[i].trim(), m;
            if (!p) return null;
            if ((m = /^([^-\/]+)-([^-\/]+)$/.exec(p))) {
                var a = ip(m[1]), b = ip(m[2]);
                if (a === null || b === null || a > b) return null;
                out.push([a, b]);
            } else if ((m = /^([^-\/]+)\/(\d{1,2})$/.exec(p))) {
                var base = ip(m[1]), len = +m[2];
                if (base === null || len > 32) return null;
                var size = Math.pow(2, 32 - len), first = base - base % size;
                out.push([first, first + size - 1]);
            } else {
                var one = ip(p);
                if (one === null) return null;
                out.push([one, one]);
            }
        }
        return out;
    };
    // one first-last range in the /24 around the local IP, on the bigger side of it: a single range is what every
    // device can use (MikrotikVpn::checkIpAddr reads one a.b.c.X-a.b.c.Y range)
    var suggest = function (l) {
        var net = l - l % 256, k = l % 256, parts = [];
        if (k > 1) parts.push({ s: txt(net + 1) + '-' + txt(net + k - 1), n: k - 1 });
        if (k < 254) parts.push({ s: txt(net + k + 1) + '-' + txt(net + 254), n: 254 - k });
        if (!parts.length) return null;
        parts.sort(function (a, b) { return b.n - a.n; });
        return parts[0].s;
    };
    var main = box.querySelector('.nf__calc-main'), warn = box.querySelector('.nf__calc-warn'), use = box.querySelector('.nf__calc-use');
    var update = function () {
        var l = ip(local.value), v = range.value.trim(), r = v ? parse(v) : null;
        var sug = l !== null ? suggest(l) : null;
        main.textContent = ''; warn.textContent = ''; use.hidden = true; box.classList.remove('is-bad');
        // the router accepts lists and prefixes, VPN packages only one range inside a /24: say so, and offer a prefix as first-last
        var single = r && r.length === 1 && v.indexOf('/') < 0 && Math.floor(r[0][0] / 256) === Math.floor(r[0][1] / 256);
        if (r && r.length === 1 && v.indexOf('/') > 0 && r[0][1] - r[0][0] >= 2 && Math.floor(r[0][0] / 256) === Math.floor(r[0][1] / 256)) {
            // the usable addresses of the prefix, without the local IP: the bigger side of it when it falls inside
            var lo = r[0][0] + 1, hi = r[0][1] - 1;
            if (l !== null && l >= lo && l <= hi) {
                if (l - lo >= hi - l) hi = l - 1; else lo = l + 1;
            }
            sug = lo <= hi ? txt(lo) + '-' + txt(hi) : null;
        }
        if (v && !r) {
            box.classList.add('is-bad');
            warn.textContent = t('bad');
        } else if (r) {
            var count = r.reduce(function (n, x) { return n + x[1] - x[0] + 1; }, 0);
            main.textContent = count.toLocaleString() + ' ' + (count === 1 ? t('address') : t('addresses')) + (r.length === 1 && count > 1 ? ' · ' + txt(r[0][0]) + ' – ' + txt(r[0][1]) : '');
            if (l !== null && r.some(function (x) { return l >= x[0] && l <= x[1]; })) {
                box.classList.add('is-bad');
                warn.textContent = t('inside');
            } else if (!single) {
                warn.textContent = t('one');
            }
        }
        if (sug && sug !== v && (!v || box.classList.contains('is-bad') || !single)) {
            use.hidden = false;
            use.textContent = t('use') + ' ' + sug;
            use.onclick = function () { range.value = sug; update(); range.focus(); };
        }
        box.hidden = !main.textContent && !warn.textContent && use.hidden;
    };
    local.addEventListener('input', update);
    range.addEventListener('input', update);
    update();
    // a pool on Radius is added to the MikroTik by hand; on a router it is created when saved
    var sel = document.querySelector('[data-nf-router]');
    if (sel) {
        var side = function () {
            var radius = sel.value === 'radius';
            document.querySelector('[data-nf-radius]').hidden = !radius;
            document.querySelector('[data-nf-mikrotik]').hidden = radius;
        };
        sel.addEventListener('change', side);
        if (window.jQuery) jQuery(sel).on('change', side); // select2 fires jQuery's change
        side();
    }
})();
</script>
{/literal}
