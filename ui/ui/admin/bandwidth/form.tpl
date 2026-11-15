{*
    The bandwidth form, shared by add.tpl ($nf_edit false, $d empty) and edit.tpl ($nf_edit true, $d the bandwidth,
    $burst its burst split on spaces). Field names are what bandwidth.php reads: name, rate_down, rate_down_unit, rate_up,
    rate_up_unit, burst[] (limit, threshold, time, priority, limit-at: all five or none are kept), id on edit.
    csrf_token is assigned by boot.php. A package sends MikroTik "upload/download" plus the burst.
*}
{$nf_burst = ($burst[0]|default:'') != '' || ($burst[1]|default:'') != '' || ($burst[2]|default:'') != '' || ($burst[3]|default:'') != '' || ($burst[4]|default:'') != ''}
<form class="ls st mc nf nf-bw" method="post" role="form" autocomplete="off"
    action="{if $nf_edit}{Text::url('bandwidth/edit-post')}{else}{Text::url('bandwidth/add-post')}{/if}"
    data-t-part="{Lang::T('Fill in all five burst fields, or none: a burst that is not complete is not saved.')|escape}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $nf_edit}<input type="hidden" name="id" value="{$d['id']|escape}">{/if}

    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-tachometer" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit Bandwidth')}{else}{Lang::T('Add New Bandwidth')}{/if}</h3>
                        <small>{Lang::T('A speed that packages use.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="name">{Lang::T('Bandwidth Name')}</label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="255" required
                            value="{$d['name_bw']|default:''|escape}" placeholder="10 Mbps">
                    </div>
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="rate_down"><span><i class="fa fa-arrow-down nf__dir" aria-hidden="true"></i> {Lang::T('Rate Download')}</span></label>
                            <div class="nf-pair">
                                <input type="number" class="form-control" id="rate_down" name="rate_down" min="0" step="any" required
                                    value="{$d['rate_down']|default:''|escape}" placeholder="10" data-nf-rate>
                                <select class="form-control" id="rate_down_unit" name="rate_down_unit" data-nf-rate>
                                    <option value="Kbps" {if ($d['rate_down_unit']|default:'') == 'Kbps'}selected{/if}>Kbps</option>
                                    <option value="Mbps" {if ($d['rate_down_unit']|default:'Mbps') == 'Mbps'}selected{/if}>Mbps</option>
                                </select>
                            </div>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="rate_up"><span><i class="fa fa-arrow-up nf__dir" aria-hidden="true"></i> {Lang::T('Rate Upload')}</span></label>
                            <div class="nf-pair">
                                <input type="number" class="form-control" id="rate_up" name="rate_up" min="0" step="any" required
                                    value="{$d['rate_up']|default:''|escape}" placeholder="5" data-nf-rate>
                                <select class="form-control" id="rate_up_unit" name="rate_up_unit" data-nf-rate>
                                    <option value="Kbps" {if ($d['rate_up_unit']|default:'') == 'Kbps'}selected{/if}>Kbps</option>
                                    <option value="Mbps" {if ($d['rate_up_unit']|default:'Mbps') == 'Mbps'}selected{/if}>Mbps</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="nf__calc" aria-live="polite">
                        <span class="nf__calc-main">{Lang::T('Rate limit sent to the router')}: <code data-nf-rl>&ndash;</code></span>
                        <span class="nf__calc-warn" data-nf-part></span>
                    </div>
                </div>
            </section>

            <details class="ls-card nf__adv" {if $nf_burst}open{/if} data-nf-burst-box>
                <summary>
                    <i class="fa fa-bolt" aria-hidden="true"></i>
                    <span class="nf__adv-text">{Lang::T('Burst')} <small>{Lang::T('Optional')}. {Lang::T('A short faster start, as MikroTik bursts it. Fill in all five, or pick a preset.')}</small></span>
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </summary>
                <div class="st-card-body">
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="burst_limit">Burst Limit</label>
                            <input type="text" class="form-control" id="burst_limit" name="burst[]" placeholder="4M/8M"
                                value="{$burst[0]|default:''|escape}" spellcheck="false" data-nf-rate>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="burst_threshold">Burst Threshold</label>
                            <input type="text" class="form-control" id="burst_threshold" name="burst[]" placeholder="1536K/3M"
                                value="{$burst[1]|default:''|escape}" spellcheck="false" data-nf-rate>
                        </div>
                    </div>
                    <div class="nf__row nf__row--3">
                        <div class="nf__field">
                            <label class="nf__label" for="burst_time">Burst Time</label>
                            <input type="text" class="form-control" id="burst_time" name="burst[]" placeholder="16/16"
                                value="{$burst[2]|default:''|escape}" spellcheck="false" data-nf-rate>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="burst_priority">Priority</label>
                            <input type="number" class="form-control" id="burst_priority" name="burst[]" placeholder="8" min="1" max="8"
                                value="{$burst[3]|default:''|escape}" data-nf-rate>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="burst_limit_at">Limit At</label>
                            <input type="text" class="form-control" id="burst_limit_at" name="burst[]" placeholder="1M/2M"
                                value="{$burst[4]|default:''|escape}" spellcheck="false" data-nf-rate>
                        </div>
                    </div>
                    <p class="nf__hint">{Lang::T('Each value is upload/download, such as 4M/8M. Burst Time is in seconds.')}</p>
                </div>
            </details>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-check-circle" aria-hidden="true"></i> {Lang::T('Save')}</h3>
                        {if $nf_edit}<small>{Lang::T('Editing Bandwidth will not automatically update the package, you need to edit the package then save again')}</small>{/if}</div>
                </header>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('bandwidth/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{if $nf_edit}{Lang::T('Continue the Bandwidth change process?')|escape:'javascript'}{else}{Lang::T('Continue the Bandwidth creation process?')|escape:'javascript'}{/if}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                </footer>
            </section>

            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-magic" aria-hidden="true"></i> {Lang::T('Burst Limit Preset')}</h3>
                        <small>{Lang::T('Fills in the speed and the burst.')}</small></div>
                </header>
                <div class="st-card-body nf-presets">
                    <p class="nf-presets__title">2x</p>
                    <div class="nf-presets__list">
                        <button type="button" class="nf-chip" data-burst="2M/2M 4M/4M 1536K/1536K 16/16 8 1M/1M">2M &rarr; 4M</button>
                        <button type="button" class="nf-chip" data-burst="3M/3M 6M/6M 2304K/2304K 16/16 8 1536K/1536K">3M &rarr; 6M</button>
                        <button type="button" class="nf-chip" data-burst="4M/4M 8M/8M 3M/3M 16/16 8 2M/2M">4M &rarr; 8M</button>
                        <button type="button" class="nf-chip" data-burst="5M/5M 10M/10M 3840k/3840k 16/16 8 2560k/2560k">5M &rarr; 10M</button>
                        <button type="button" class="nf-chip" data-burst="6M/6M 12M/12M 4608k/4608k 16/16 8 3M/3M">6M &rarr; 12M</button>
                        <button type="button" class="nf-chip" data-burst="7M/7M 14M/14M 5376k/5376k 16/16 8 3584k/3584k">7M &rarr; 14M</button>
                        <button type="button" class="nf-chip" data-burst="8M/8M 16M/16M 6M/6M 16/16 8 4M/4M">8M &rarr; 16M</button>
                        <button type="button" class="nf-chip" data-burst="9M/9M 18M/18M 6912k/6912k 16/16 8 4608k/4608k">9M &rarr; 18M</button>
                        <button type="button" class="nf-chip" data-burst="10M/10M 20M/20M 7680k/7680k 16/16 8 5M/5M">10M &rarr; 20M</button>
                    </div>
                    <p class="nf-presets__title">{Lang::T('Up to 1M more')}</p>
                    <div class="nf-presets__list">
                        <button type="button" class="nf-chip" data-burst="1M/1M 2M/2M 768k/768k 16/16 8 512k/512k">1M &rarr; 2M</button>
                        <button type="button" class="nf-chip" data-burst="2M/2M 3M/3M 1536k/1536k 12/12 8 1024k/1024k">2M &rarr; 3M</button>
                        <button type="button" class="nf-chip" data-burst="3M/3M 4M/4M 2450k/2450k 10/10 8 1536k/1536k">3M &rarr; 4M</button>
                        <button type="button" class="nf-chip" data-burst="4M/4M 5M/5M 3M/3M 10/10 8 2M/2M">4M &rarr; 5M</button>
                        <button type="button" class="nf-chip" data-burst="5M/5M 6M/6M 3680k/3680k 10/10 8 2450k/2450k">5M &rarr; 6M</button>
                        <button type="button" class="nf-chip" data-burst="6M/6M 7M/7M 4560k/4560k 11/11 8 3M/3M">6M &rarr; 7M</button>
                        <button type="button" class="nf-chip" data-burst="7M/7M 8M/8M 5460k/5460k 12/12 8 3640k/3640k">7M &rarr; 8M</button>
                        <button type="button" class="nf-chip" data-burst="8M/8M 9M/9M 6M/6M 12/12 8 4M/4M">8M &rarr; 9M</button>
                        <button type="button" class="nf-chip" data-burst="9M/9M 10M/10M 6820k/6820k 12/12 8 4550k/4550k">9M &rarr; 10M</button>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
(function () {
    var form = document.querySelector('form.nf-bw');
    var $ = function (s) { return form.querySelector(s); };
    var ids = ['burst_limit', 'burst_threshold', 'burst_time', 'burst_priority', 'burst_limit_at'];
    // what a package sends the router: upload/download, then the burst when all five are filled in
    var preview = function () {
        var u = function (id) { return $('#' + id).value === 'Kbps' ? 'K' : 'M'; };
        var down = $('#rate_down').value.trim(), up = $('#rate_up').value.trim();
        var vals = ids.map(function (id) { return $('#' + id).value.trim(); });
        var filled = vals.filter(Boolean).length;
        var rl = (up && down) ? up + u('rate_up_unit') + '/' + down + u('rate_down_unit') : '';
        if (rl && filled === 5) rl += ' ' + vals.join(' ');
        $('[data-nf-rl]').textContent = rl || '–';
        var part = filled > 0 && filled < 5;
        $('[data-nf-part]').textContent = part ? form.getAttribute('data-t-part') : '';
        $('[data-nf-part]').closest('.nf__calc').classList.toggle('is-bad', part);
    };
    form.querySelectorAll('[data-nf-rate]').forEach(function (e) { e.addEventListener('input', preview); e.addEventListener('change', preview); });
    // a preset: "rate burst-limit threshold time priority limit-at", every value up/down
    form.querySelectorAll('[data-burst]').forEach(function (b) {
        b.addEventListener('click', function () {
            var p = b.getAttribute('data-burst').split(' ');
            ids.forEach(function (id, i) { $('#' + id).value = p[i + 1]; });
            var r = p[0].split('/');
            $('#rate_up').value = r[0].replace(/M$/i, '');
            $('#rate_down').value = r[1].replace(/M$/i, '');
            $('#rate_up_unit').value = 'Mbps';
            $('#rate_down_unit').value = 'Mbps';
            $('[data-nf-burst-box]').open = true;
            preview();
            $('#rate_down').focus();
        });
    });
    preview();
})();
</script>
{/literal}
