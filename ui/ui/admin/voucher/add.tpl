{include file="sections/header.tpl"}
<!-- voucher-add -->
{*
    Add Vouchers. plan.php (voucher-post) reads type, server, plan, batch_name, numbervoucher, voucher_format, prefix,
    lengthcode, print_now (yes) and voucher_per_page, with csrf_token. custom.js loads the routers into #server when #Hot or
    #POE is chosen, and that router's packages into #plan. $voucher_max: the most vouchers in one go.
*}
<form class="ls st mc nf nf-vg" method="post" role="form" autocomplete="off" action="{Text::url('plan/voucher-post')}" id="voucherForm"
    data-max="{$voucher_max}" data-t-vouchers="{Lang::T('vouchers')|escape}" data-t-voucher="{Lang::T('voucher')|escape}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-ticket" aria-hidden="true"></i> {Lang::T('Add Vouchers')}</h3>
                        <small>{Lang::T('Codes customers type to get a package.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <span class="nf__label">{Lang::T('Type')}</span>
                        <div class="nf-seg" role="radiogroup" aria-label="{Lang::T('Type')}">
                            <label><input type="radio" id="Hot" name="type" value="Hotspot" checked> <span>{Lang::T('Hotspot Plans')}</span></label>
                            <label><input type="radio" id="POE" name="type" value="PPPOE"> <span>{Lang::T('PPPOE Plans')}</span></label>
                        </div>
                    </div>
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="server">{Lang::T('Routers')}</label>
                            <select id="server" name="server" class="form-control select2" required>
                                <option value="">{Lang::T('Select Routers')}</option>
                            </select>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="plan">{Lang::T('Service Plan')}</label>
                            <select id="plan" name="plan" class="form-control select2" required>
                                <option value="">{Lang::T('Select Plans')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="batch_name">{Lang::T('Batch Name')} <small>{Lang::T('Optional')}</small></label>
                            <input type="text" class="form-control" id="batch_name" name="batch_name" maxlength="50" placeholder="{Lang::T('Batch 1')}">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="numbervoucher">{Lang::T('Number of Vouchers')} <small>{Lang::T('Up to')} {$voucher_max}</small></label>
                            <input type="number" class="form-control" name="numbervoucher" id="numbervoucher" value="1" min="1" max="{$voucher_max}" step="1" required>
                            <div class="nf-presets__list nf-vg__counts">
                                {foreach [10, 50, 100, 500, 1000, 5000] as $n}
                                    {if $n <= $voucher_max}<button type="button" class="nf-chip vg-count" data-n="{$n}">{$n}</button>{/if}
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-font" aria-hidden="true"></i> {Lang::T('Voucher Format')}</h3>
                        <small>{Lang::T('How the codes look.')}</small></div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <span class="nf__label">{Lang::T('Characters')}</span>
                        {* buttons and a hidden field, not radios: custom.js reloads the routers whenever any radio changes *}
                        {$vf = $_c['voucher_format']|default:'up'}
                        {if !in_array($vf, ['numbers','up','low','rand'])}{$vf = 'up'}{/if}
                        <input type="hidden" name="voucher_format" id="voucher_format" value="{$vf}">
                        <div class="nf-seg nf-seg--4 nf-seg--btn" role="group" aria-label="{Lang::T('Voucher Format')}">
                            <button type="button" data-vf="numbers" aria-pressed="{if $vf == 'numbers'}true{else}false{/if}" title="{Lang::T('Numbers')}">123</button>
                            <button type="button" data-vf="up" aria-pressed="{if $vf == 'up'}true{else}false{/if}" title="UPPERCASE">ABC</button>
                            <button type="button" data-vf="low" aria-pressed="{if $vf == 'low'}true{else}false{/if}" title="lowercase">abc</button>
                            <button type="button" data-vf="rand" aria-pressed="{if $vf == 'rand'}true{else}false{/if}" title="RaNdoM">AbC</button>
                        </div>
                    </div>
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="prefix">{Lang::T('Voucher Prefix')} <small>{Lang::T('Optional')}</small></label>
                            <input type="text" class="form-control" name="prefix" id="prefix" placeholder="NUX-" maxlength="20"
                                pattern="[A-Za-z0-9_\-]*" value="{$_c['voucher_prefix']|escape}" spellcheck="false">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="lengthcode">{Lang::T('Length Code')} <small>{Lang::T('4 to 32, at least 6 for Numbers')}</small></label>
                            <input type="number" class="form-control" name="lengthcode" id="lengthcode" value="12" min="4" max="32" step="1" required>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('The prefix can only have letters, numbers, - and _ (20 at most)')}</p>
                </div>
            </section>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-eye" aria-hidden="true"></i> {Lang::T('Preview')}</h3></div>
                </header>
                <div class="st-card-body nf-vg__preview">
                    <div class="nf-vg__code" id="vgExample">&ndash;</div>
                    <div class="nf-vg__sum" id="vgSummary"></div>
                </div>
                <div class="mc-rows nf__switches">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" id="print_now" name="print_now" value="yes">
                        <span class="mc-row__icon"><i class="fa fa-print" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Print Now')}</b>
                            <small>{Lang::T('Open the print page for the new vouchers.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                </div>
                <div class="st-card-body" id="printers" style="display:none">
                    <div class="nf__field">
                        <label class="nf__label" for="voucher-print">{Lang::T('Vouchers Per Page')}</label>
                        <input type="number" id="voucher-print" class="form-control" name="voucher_per_page" value="36" min="1" step="1">
                    </div>
                </div>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('plan/voucher')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary" id="vgGenerate"
                        onclick="return this.form.reportValidity() && ask(this, '{Lang::T('Continue the Voucher creation process?')|escape:'javascript'}')">
                        <i class="fa fa-magic" aria-hidden="true"></i> {Lang::T('Generate')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>
<!-- /voucher-add -->

{literal}
<script>
    // a sample code and a one-line summary that follow the form; jQuery and custom.js come with the footer, so this waits
    window.addEventListener('load', function () {
        var form = document.getElementById('voucherForm'), $ = window.jQuery;
        var max = parseInt(form.getAttribute('data-max'), 10) || 5000;
        var printNow = document.getElementById('print_now'), perPage = document.getElementById('voucher-print');
        var sample = function (format, len) {
            var pool = format === 'numbers' ? '0123456789' : format === 'low' ? '0123456789abcdef' : '0123456789ABCDEF', out = '';
            for (var i = 0; i < len; i++) {
                var c = pool.charAt(Math.floor(Math.random() * pool.length));
                out += (format === 'rand' && Math.random() < 0.5) ? c.toLowerCase() : c;
            }
            return out;
        };
        var refresh = function () {
            var len = Math.max(0, Math.min(32, parseInt(form.lengthcode.value, 10) || 0));
            var n = parseInt(form.numbervoucher.value, 10) || 0;
            var fmt = form.voucher_format.value || 'up';
            document.getElementById('vgExample').textContent = (form.prefix.value || '') + sample(fmt, len);
            var plan = form.plan, txt = n + ' ' + form.getAttribute(n === 1 ? 'data-t-voucher' : 'data-t-vouchers');
            if (plan.value && plan.selectedIndex >= 0) txt += ' • ' + plan.options[plan.selectedIndex].text.replace(/\s+/g, ' ').trim();
            var bad = n < 1 || n > max || len < 4 || len > 32 || (fmt === 'numbers' && len < 6);
            var s = document.getElementById('vgSummary');
            s.textContent = txt;
            s.classList.toggle('is-bad', bad);
            document.getElementById('vgGenerate').disabled = bad;
        };
        var printers = function () {
            document.getElementById('printers').style.display = printNow.checked ? '' : 'none';
            perPage.required = printNow.checked;
            perPage.disabled = !printNow.checked; // hidden: neither checked nor sent (the save uses 36 then)
        };
        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);
        printNow.addEventListener('change', printers);
        form.querySelectorAll('[data-vf]').forEach(function (b) {
            b.addEventListener('click', function () {
                form.voucher_format.value = b.getAttribute('data-vf');
                form.querySelectorAll('[data-vf]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
                refresh();
            });
        });
        form.querySelectorAll('.vg-count').forEach(function (b) {
            b.addEventListener('click', function () { form.numbervoucher.value = b.getAttribute('data-n'); refresh(); });
        });
        if ($) {
            $('#plan, #server').on('change', refresh); // select2 fires jQuery's change
            $('#Hot:checked, #POE:checked').trigger('change'); // custom.js loads the routers for the chosen type
        }
        printers();
        refresh();
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
