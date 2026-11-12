{include file="sections/header.tpl"}
<!-- reports-daily -->
{* Daily Report. The filter is a GET form (sd, ts, ed, te, tps[], plns[], mts[], rts[]); reports.php reads it through
   TransactionReport::filterFromRequest(), and the insight panels load from reports/ajax/summary with the same query string. *}

<div class="ls rf rp">

    <form method="get" class="ls-card rp-filter" id="rpFilter" data-t-loading="{Lang::T('Loading')|escape}">
        <input type="hidden" name="_route" value="reports">
        <div class="rp-ranges ls-tabs" role="group" aria-label="{Lang::T('Quick ranges')}">
            {foreach $ranges as $r}
            <a class="ls-tab{if $full_day && $r['sd'] == $sd && $r['ed'] == $ed} is-on{/if}"
                href="{Text::url('reports&sd=', $r['sd'], '&ed=', $r['ed'])}">{$r['label']|escape}</a>
            {/foreach}
        </div>
        <div class="rp-fields">
            <label class="rp-field">
                <span>{Lang::T('From')}</span>
                <span class="rp-field__pair">
                    <input type="date" name="sd" value="{$sd|escape}" max="{$mdate}" required aria-label="{Lang::T('Start Date')}">
                    <input type="time" name="ts" value="{$ts|escape}" step="1" aria-label="{Lang::T('Start time')}">
                </span>
            </label>
            <label class="rp-field">
                <span>{Lang::T('To')}</span>
                <span class="rp-field__pair">
                    <input type="date" name="ed" value="{$ed|escape}" required aria-label="{Lang::T('End Date')}">
                    <input type="time" name="te" value="{$te|escape}" step="1" aria-label="{Lang::T('End Time')}">
                </span>
            </label>
            <div class="rp-fields__actions">
                <button type="button" class="ls-btn rp-more-btn" aria-expanded="{if $active_filters}true{else}false{/if}" aria-controls="rpMore">
                    <i class="fa fa-sliders" aria-hidden="true"></i> {Lang::T('Filters')}
                    {if $active_filters}<small class="rp-badge">{$active_filters}</small>{/if}
                </button>
                <button type="submit" class="ls-btn ls-btn--primary"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Apply')}</button>
                <a class="ls-btn" href="{Text::url('reports')}" title="{Lang::T('Reset')}"><i class="fa fa-undo" aria-hidden="true"></i><span class="rp-hide-sm"> {Lang::T('Reset')}</span></a>
            </div>
        </div>
        <div class="rp-more" id="rpMore" {if !$active_filters}hidden{/if}>
            <label class="rp-field">
                <span>{Lang::T('Type')}</span>
                <select class="select2" name="tps[]" multiple data-placeholder="{Lang::T('All types')|escape}">
                    {foreach $types as $type}
                    <option value="{$type|escape}" {if in_array($type, $sel['tps'])}selected{/if}>{$type|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="rp-field">
                <span>{Lang::T('Internet Plans')}</span>
                <select class="select2" name="plns[]" multiple data-placeholder="{Lang::T('All plans')|escape}">
                    {foreach $plans as $plan}
                    <option value="{$plan|escape}" {if in_array($plan, $sel['plns'])}selected{/if}>{$plan|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="rp-field">
                <span>{Lang::T('Methods')}</span>
                <select class="select2" name="mts[]" multiple data-placeholder="{Lang::T('All methods')|escape}">
                    {foreach $methods as $method}
                    <option value="{$method|escape}" {if in_array($method, $sel['mts'])}selected{/if}>{$method|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="rp-field">
                <span>{Lang::T('Routers')}</span>
                <select class="select2" name="rts[]" multiple data-placeholder="{Lang::T('All routers')|escape}">
                    {foreach $routers as $router}
                    <option value="{$router|escape}" {if in_array($router, $sel['rts'])}selected{/if}>{$router|escape}</option>
                    {/foreach}
                </select>
            </label>
        </div>
    </form>

    {* overview *}
    <div class="rf-kpis">
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-money-bill" aria-hidden="true"></i></span>
            <small>{Lang::T('Revenue')}</small>
            <b>{Lang::moneyFormat($kpi['sum'])}</b>
            <span class="rf-kpi__sub">{if $kpi['sum_change'] === null}{Lang::T('Nothing to compare with')}{else}<em class="rp-delta {if $kpi['sum_change'] >= 0}rp-delta--up{else}rp-delta--down{/if}"><i class="fa fa-caret-{if $kpi['sum_change'] >= 0}up{else}down{/if}" aria-hidden="true"></i> {abs($kpi['sum_change'])}%</em> {Lang::T('vs previous')} {$kpi['days']} {Lang::T('days')}{/if}</span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-exchange" aria-hidden="true"></i></span>
            <small>{Lang::T('Transactions')}</small>
            <b>{$kpi['count']|number_format}</b>
            <span class="rf-kpi__sub">{if $kpi['count_change'] === null}{Lang::T('Nothing to compare with')}{else}<em class="rp-delta {if $kpi['count_change'] >= 0}rp-delta--up{else}rp-delta--down{/if}"><i class="fa fa-caret-{if $kpi['count_change'] >= 0}up{else}down{/if}" aria-hidden="true"></i> {abs($kpi['count_change'])}%</em> {Lang::T('vs previous')} {$kpi['days']} {Lang::T('days')}{/if}</span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-tag" aria-hidden="true"></i></span>
            <small>{Lang::T('Average sale')}</small>
            <b>{Lang::moneyFormat($kpi['avg'])}</b>
            <span class="rf-kpi__sub">{Lang::T('Revenue per transaction')}</span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-calendar" aria-hidden="true"></i></span>
            <small>{Lang::T('Per day')}</small>
            <b>{Lang::moneyFormat($kpi['per_day'])}</b>
            <span class="rf-kpi__sub">{Lang::T('Average over')} {$kpi['days']} {if $kpi['days'] == 1}{Lang::T('day')}{else}{Lang::T('days')}{/if}</span>
        </div>
    </div>

    {* insights: loaded after the page, only while shown *}
    <div class="rp-insights-head">
        <h3>{Lang::T('Insights')}</h3>
        <label class="st-all rp-toggle"><input type="checkbox" id="rpShowCharts"> {Lang::T('Show chart')}</label>
    </div>
    <div id="rpInsights" hidden>
        <section class="ls-card rf-panel rp-trend">
            <header class="rf-panel__head">
                <div>
                    <h3>{Lang::T('Revenue per day')}</h3>
                    <small id="rpTrendRange">{Lang::dateFormat($sd)} - {Lang::dateFormat($ed)}</small>
                </div>
                <span class="rp-legend" aria-hidden="true">
                    <span><i class="rp-swatch rp-swatch--bar"></i> {Lang::T('Revenue')}</span>
                    <span><i class="rp-swatch rp-swatch--line"></i> {Lang::T('Transactions')}</span>
                </span>
            </header>
            <div class="rf-chart">
                <canvas id="rpTrend" role="img" aria-label="{Lang::T('Revenue per day')}"></canvas>
                <div class="ls-empty rp-state"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>
            </div>
        </section>
        <div class="rp-grid">
            {foreach ['type' => 'By type', 'plan' => 'By plan', 'method' => 'By payment method', 'router' => 'By router'] as $dim => $title}
            <section class="ls-card rf-panel">
                <header class="rf-panel__head">
                    <div><h3>{Lang::T($title)}</h3><small>{Lang::T('Share of revenue')}</small></div>
                </header>
                <ol class="rf-top rp-breakdown" data-dim="{$dim}">
                    <li class="ls-empty rp-state"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></li>
                </ol>
            </section>
            {/foreach}
        </div>
    </div>

    {* transactions *}
    <div class="ls-card">
        <div class="ls-bar rp-bar">
            <div class="rp-bar__title">
                <b>{Lang::T('Transactions')}</b>
                <small>
                    {if $kpi['count']}
                    {($paginator['startpoint'] + 1)|number_format}&ndash;{min($paginator['startpoint'] + 100, $kpi['count'])|number_format} {Lang::T('of')} {$kpi['count']|number_format}
                    &middot; {Lang::dateAndTimeFormat($sd, $ts)} - {Lang::dateAndTimeFormat($ed, $te)}
                    {else}{Lang::dateAndTimeFormat($sd, $ts)} - {Lang::dateAndTimeFormat($ed, $te)}{/if}
                </small>
            </div>
            <span class="ls-actions">
                <a href="{Text::url('export/print-by-date&')}{$filter}" class="ls-btn rp-export" target="_blank" title="{Lang::T('Print')}"><i class="fa fa-print" aria-hidden="true"></i> {Lang::T('Print')}</a>
                <a href="{Text::url('export/pdf-by-date&')}{$filter}" class="ls-btn rp-export" title="PDF"><i class="fa fa-file-pdf-o" aria-hidden="true"></i> PDF</a>
            </span>
        </div>
        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Invoice')}</th>
                        <th>{Lang::T('Customer')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th class="ls-num">{Lang::T('Plan Price')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th>{Lang::T('Expires On')}</th>
                        <th>{Lang::T('Method')}</th>
                        <th>{Lang::T('Routers')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('Invoice')}"><a class="ls-strong" href="{Text::url('plan/view/', $ds['id'])}">{$ds['invoice']|escape}</a></td>
                        <td data-label="{Lang::T('Customer')}" class="rp-cust">
                            <a href="{Text::url('')}customers/viewu/{$ds['username']|escape:'url'}">{$ds['username']|escape}</a>
                            {if $ds['fullname']}<small class="ls-dim">{$ds['fullname']|escape}</small>{/if}
                        </td>
                        <td data-label="{Lang::T('Plan Name')}">{$ds['plan_name']|escape} <em class="ls-chip">{$ds['type']|escape}</em></td>
                        <td data-label="{Lang::T('Plan Price')}" class="ls-num">{Lang::moneyFormat($ds['price'])}</td>
                        <td data-label="{Lang::T('Created On')}" class="ls-when">{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                        <td data-label="{Lang::T('Expires On')}" class="ls-when ls-dim">{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                        <td data-label="{Lang::T('Method')}" class="ls-dim">{$ds['method']|escape}</td>
                        <td data-label="{Lang::T('Routers')}" class="ls-dim">{$ds['routers']|escape}</td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="8">
                        <div class="ls-empty"><b>{Lang::T('No transactions found')}</b>{Lang::T('Try a wider date range or fewer filters.')}</div>
                    </td></tr>
                    {/foreach}
                </tbody>
                {if $kpi['count']}
                <tfoot>
                    <tr class="rp-total">
                        <td colspan="3" class="ls-strong">{Lang::T('Total')} <small class="ls-dim">({$kpi['count']|number_format} {Lang::T('transactions, all pages')})</small></td>
                        <td data-label="{Lang::T('Total')}" class="ls-num ls-strong">{Lang::moneyFormat($dr)}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                {/if}
            </table>
        </div>
        <div class="rp-pager">{include file="pagination.tpl"}</div>
    </div>
    <p class="rp-note"><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('Export and Print will show all data without pagination')}.</p>
</div>

<script src="{$app_url}/ui/ui/scripts/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('.rp');
    var form = document.getElementById('rpFilter');
    var more = document.getElementById('rpMore');
    var moreBtn = form.querySelector('.rp-more-btn');
    var URL_SUMMARY = {Text::url('reports/ajax/summary&', $filter)|json_encode};
    var COUNT = {$kpi['count']};
    var T = {
        others: {Lang::T('Others')|json_encode},
        none: {Lang::T('No transactions in this range')|json_encode},
        failed: {Lang::T('Could not load the charts. Reload the page to try again.')|json_encode},
        revenue: {Lang::T('Revenue')|json_encode},
        trx: {Lang::T('Transactions')|json_encode},
        big: {Lang::T('This export holds every transaction of the filter on one page and can take a long time to open. Continue?')|json_encode}
    };
    var cur = {$_c['currency_code']|json_encode};
    var money = function (v) { return cur + ' ' + Number(v).toLocaleString(undefined, { maximumFractionDigits: 2 }); };

    // the open filter list is moved to the end of <body>, outside .rp: tag it so it takes the same field styling (.mc-s2)
    if (window.jQuery) {
        jQuery(form).on('select2:open', function () {
            document.querySelectorAll('body > .select2-container--open').forEach(function (d) { d.classList.add('mc-s2'); });
        });
    }
    // more filters
    moreBtn.addEventListener('click', function () {
        var open = more.hidden;
        more.hidden = !open;
        moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    // From after To: swap them instead of showing nothing
    form.addEventListener('submit', function () {
        var sd = form.elements.sd, ed = form.elements.ed;
        if (sd.value && ed.value && sd.value > ed.value) { var t = sd.value; sd.value = ed.value; ed.value = t; }
    });
    // a large export is one long page: confirm first
    if (COUNT > 5000) {
        root.querySelectorAll('.rp-export').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                ask(a, T.big, function () { if (a.target === '_blank') { window.open(a.href, '_blank'); } else { window.location.href = a.href; } });
            });
        });
    }

    // insights
    var box = document.getElementById('rpInsights');
    var toggle = document.getElementById('rpShowCharts');
    var loaded = false;
    function show(on) {
        box.hidden = !on;
        toggle.checked = on;
        if (on && !loaded) { loaded = true; load(); }
    }
    toggle.addEventListener('change', function () {
        setCookie('show_report_graph', toggle.checked ? 'show' : 'hide', 30);
        show(toggle.checked);
    });
    show(getCookie('show_report_graph') !== 'hide');

    function state(el, text) {
        el.innerHTML = '';
        var p = document.createElement(el.tagName === 'OL' ? 'li' : 'div');
        p.className = 'ls-empty rp-state';
        p.textContent = text;
        el.appendChild(p);
    }

    function load() {
        fetch(URL_SUMMARY, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
            .then(function (data) {
                trend(data.daily);
                root.querySelectorAll('.rp-breakdown').forEach(function (ol) { breakdown(ol, data[ol.getAttribute('data-dim')]); });
            })
            .catch(function () {
                state(root.querySelector('.rp-trend .rf-chart'), T.failed);
                root.querySelectorAll('.rp-breakdown').forEach(function (ol) { state(ol, T.failed); });
            });
    }

    function breakdown(ol, d) {
        if (!d || !d.items.length) { state(ol, T.none); return; }
        var items = d.items.slice();
        if (d.others.count) { items.push({ name: T.others, count: d.others.count, sum: d.others.sum, others: true }); }
        ol.innerHTML = '';
        items.forEach(function (it, n) {
            var li = document.createElement('li');
            var pct = d.total > 0 ? Math.round(it.sum / d.total * 100) : 0;
            li.innerHTML = '<span class="rf-top__rank"></span><div class="rf-top__body"><div class="rf-top__line"><span class="rf-top__name"></span>'
                + '<span class="rf-top__n"></span></div><div class="rf-top__bar"><span></span></div><small class="rf-top__earned"></small></div>';
            li.querySelector('.rf-top__rank').textContent = it.others ? '+' : String(n + 1);
            li.querySelector('.rf-top__name').textContent = it.name === '' ? '-' : it.name;
            li.querySelector('.rf-top__name').title = it.name;
            li.querySelector('.rf-top__n').textContent = money(it.sum);
            // the bar is the item's share of the revenue, the same figure as the percentage under it
            li.querySelector('.rf-top__bar span').style.width = (d.total > 0 ? Math.max(1, it.sum / d.total * 100) : 0) + '%';
            li.querySelector('.rf-top__earned').textContent = pct + '% · ' + Number(it.count).toLocaleString() + ' ' + T.trx.toLowerCase();
            ol.appendChild(li);
        });
    }

    function trend(d) {
        var wrap = root.querySelector('.rp-trend .rf-chart');
        var el = document.getElementById('rpTrend');
        var spin = wrap.querySelector('.rp-state');
        if (!d || !d.labels.length || !window.Chart) { state(wrap, T.none); return; }
        if (spin) { spin.remove(); }
        // colours come from the page's tokens, so the chart follows light/dark mode and the theme
        var css = getComputedStyle(root);
        var ink = css.getPropertyValue('--ls-muted').trim() || '#64748b';
        var grid = css.getPropertyValue('--ls-line').trim() || '#e2e8f0';
        var bar = css.getPropertyValue('--rf-bar').trim() || '#6366f1';
        var line = css.getPropertyValue('--rp-line').trim() || '#f59e0b';
        var dates = d.labels.map(function (x) { return new Date(x + 'T00:00:00'); });
        var short = function (x) { return x.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }); };
        new Chart(el, {
            data: {
                labels: dates.map(short),
                datasets: [
                    { type: 'bar', label: T.revenue, data: d.revenue, yAxisID: 'y', backgroundColor: bar, hoverBackgroundColor: bar, borderRadius: 4, maxBarThickness: 22, order: 2 },
                    { type: 'line', label: T.trx, data: d.count, yAxisID: 'y1', borderColor: line, backgroundColor: line, borderWidth: 2, pointRadius: d.labels.length > 45 ? 0 : 2, tension: .3, order: 1 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10, cornerRadius: 8,
                        callbacks: {
                            title: function (i) { return dates[i[0].dataIndex].toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }); },
                            label: function (i) { return i.dataset.yAxisID === 'y' ? T.revenue + ': ' + money(i.raw) : T.trx + ': ' + Number(i.raw).toLocaleString(); }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: ink, maxRotation: 0, autoSkip: true, maxTicksLimit: el.clientWidth < 520 ? 4 : 10, font: { size: 11 } } },
                    y: { beginAtZero: true, grid: { color: grid, drawBorder: false }, ticks: { color: ink, maxTicksLimit: 5, font: { size: 11 }, callback: function (v) { return Number(v).toLocaleString(); } } },
                    y1: { beginAtZero: true, position: 'right', grid: { display: false, drawBorder: false }, ticks: { color: ink, maxTicksLimit: 5, precision: 0, font: { size: 11 } } }
                }
            }
        });
    }
});
</script>

{include file="sections/footer.tpl"}
