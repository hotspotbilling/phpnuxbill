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
                href="{Text::url('reports&sd=', $r['sd'], '&ed=', $r['ed'], $sel_query)}">{$r['label']|escape}</a>
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
                    <h3 id="rpTrendTitle" data-revenue="{Lang::T('Revenue per day')|escape}" data-count="{Lang::T('Transactions per day')|escape}">{Lang::T('Revenue per day')}</h3>
                    <small id="rpTrendRange">{Lang::dateFormat($sd)} - {Lang::dateFormat($ed)}</small>
                </div>
                <div class="rp-trend__side">
                    <b class="rf-panel__total" id="rpTrendTotal"></b>
                    <div class="rp-seg" role="radiogroup" aria-label="{Lang::T('Show')}">
                        <button type="button" role="radio" aria-checked="true" data-metric="revenue">{Lang::T('Revenue')}</button>
                        <button type="button" role="radio" aria-checked="false" data-metric="count">{Lang::T('Transactions')}</button>
                    </div>
                </div>
            </header>
            <div class="rf-chart">
                <canvas id="rpTrend" role="img" aria-label="{Lang::T('Revenue per day')|escape}"></canvas>
                <div class="ls-empty rp-state"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>
            </div>
            {* the same figures as a table, for screen readers *}
            <table class="sr-only" id="rpTrendTable">
                <caption>{Lang::T('Revenue per day')}</caption>
                <thead><tr><th scope="col">{Lang::T('Date')}</th><th scope="col">{Lang::T('Revenue')}</th><th scope="col">{Lang::T('Transactions')}</th></tr></thead>
                <tbody></tbody>
            </table>
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
        avg: {Lang::T('Avg')|json_encode},
        avgSale: {Lang::T('Average sale')|json_encode},
        noSales: {Lang::T('No sales')|json_encode},
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
    // From after To (date and time together): swap the two ends instead of showing nothing
    form.addEventListener('submit', function () {
        var sd = form.elements.sd, ed = form.elements.ed, ts = form.elements.ts, te = form.elements.te;
        var hms = function (v, d) { return !v ? d : (v.length === 5 ? v + ':00' : v); };
        // an empty time is filled with its own end's default first, so the swapped range is the one compared
        var st = hms(ts.value, '00:00:00'), et = hms(te.value, '23:59:59');
        if (sd.value && ed.value && sd.value + ' ' + st > ed.value + ' ' + et) {
            var t = sd.value; sd.value = ed.value; ed.value = t;
            ts.value = et; te.value = st;
        }
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

    // Revenue per day: one measure at a time on one axis (Revenue or Transactions), drawn as the dashboard's Sales Report is (a
    // smooth line over a fading area), with the daily average as a reference line and the best day marked and labelled;
    // the tooltip gives the day's revenue, transactions and average sale.
    function trend(d) {
        var wrap = root.querySelector('.rp-trend .rf-chart');
        var el = document.getElementById('rpTrend');
        var spin = wrap.querySelector('.rp-state');
        if (!window.Chart) { state(wrap, T.failed); return; }
        if (!d || !d.labels.length) { state(wrap, T.none); return; }
        if (spin) { spin.remove(); }
        var titleEl = document.getElementById('rpTrendTitle');
        var totalEl = document.getElementById('rpTrendTotal');
        var seg = root.querySelectorAll('.rp-seg button');

        // colours come from the page's tokens, so the chart follows light/dark mode and the theme
        var css = getComputedStyle(root);
        var muted = css.getPropertyValue('--ls-muted').trim() || '#64748b';
        var ink = css.getPropertyValue('--ls-ink').trim() || '#1e293b';
        var grid = css.getPropertyValue('--ls-line').trim() || '#e2e8f0';
        var bar = css.getPropertyValue('--rp-bar').trim() || '#6366f1';
        var card = css.getPropertyValue('--ls-card').trim() || '#ffffff';
        // the area under the line fades from the line colour to nothing, as on the dashboard's Sales Report
        var rgb = (function (h) {
            var m = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(h);
            return m ? [parseInt(m[1], 16), parseInt(m[2], 16), parseInt(m[3], 16)].join(', ') : '99, 102, 241';
        })(bar);
        var fade = function (c) {
            var a = c.chart.chartArea;
            if (!a) { return 'rgba(' + rgb + ', .1)'; }
            var g = c.chart.ctx.createLinearGradient(0, a.top, 0, a.bottom);
            g.addColorStop(0, 'rgba(' + rgb + ', .35)');
            g.addColorStop(.7, 'rgba(' + rgb + ', .08)');
            g.addColorStop(1, 'rgba(' + rgb + ', 0)');
            return g;
        };
        var font = getComputedStyle(document.body).fontFamily;

        var dates = d.labels.map(function (x) { return new Date(x + 'T00:00:00'); });
        var dayLong = function (x) { return x.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }); };
        var dayShort = function (x) { return x.toLocaleDateString(undefined, { day: 'numeric', month: 'short' }); };
        var compact = function (v) {
            try { return new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(v); } catch (e) { return Number(v).toLocaleString(); }
        };
        var fmt = function (m, v) { return m === 'revenue' ? money(v) : Number(v).toLocaleString(); };
        // a short figure for the labels on the plot: compact from 10,000 (12.9K), whole numbers below; the currency only on the peak
        var short = function (v, bare) {
            var n = Math.abs(v) >= 10000 ? compact(v) : Math.round(v).toLocaleString();
            return metric === 'revenue' && !bare ? cur + ' ' + n : n;
        };
        document.getElementById('rpTrendRange').textContent = dayShort(dates[0]) + ' - ' + dates[dates.length - 1].toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });

        // table view
        var tb = document.querySelector('#rpTrendTable tbody');
        tb.innerHTML = '';
        d.labels.forEach(function (x, i) {
            var tr = document.createElement('tr');
            [dayLong(dates[i]), money(d.revenue[i]), String(d.count[i])].forEach(function (v) { var td = document.createElement('td'); td.textContent = v; tr.appendChild(td); });
            tb.appendChild(tr);
        });

        var metric = 'revenue';
        try { if (localStorage.getItem('rp_trend_metric') === 'count') { metric = 'count'; } } catch (e) { }
        var series = function () { return metric === 'revenue' ? d.revenue : d.count; };
        var stats = function () {
            var v = series(), sum = 0, peak = -1;
            v.forEach(function (x, i) { sum += x; if (x > 0 && (peak < 0 || x > v[peak])) { peak = i; } });
            return { sum: sum, avg: sum / v.length, peak: peak };
        };

        // the average line and the best-day label, drawn over the columns
        var marks = {
            id: 'rpMarks',
            afterDatasetsDraw: function (chart) {
                var st = stats(), ctx = chart.ctx, area = chart.chartArea, y = chart.scales.y;
                ctx.save();
                ctx.font = '600 11px ' + font;
                if (st.avg > 0 && d.labels.length > 1) {
                    var py = Math.round(y.getPixelForValue(st.avg)) + .5;
                    // solid, in the muted ink: the gridlines are the dashed ones
                    ctx.strokeStyle = muted; ctx.lineWidth = 1;
                    ctx.beginPath(); ctx.moveTo(area.left, py); ctx.lineTo(area.right, py); ctx.stroke();
                    // labelled in the margin right of the plot, where it never sits on a column
                    ctx.fillStyle = muted; ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
                    ctx.fillText(T.avg, area.right + 8, py - 7);
                    ctx.fillStyle = ink;
                    ctx.fillText(short(st.avg, true), area.right + 8, py + 7);
                }
                if (st.peak >= 0) {
                    var b = chart.getDatasetMeta(0).data[st.peak];
                    if (b) {
                        // a dot on the best day (the line has no points of its own), ringed in the card colour
                        ctx.beginPath(); ctx.arc(b.x, b.y, 4, 0, Math.PI * 2);
                        ctx.fillStyle = bar; ctx.fill();
                        ctx.lineWidth = 2; ctx.strokeStyle = card; ctx.stroke();
                        var label = short(series()[st.peak], false);
                        var lw = ctx.measureText(label).width;
                        var lx = Math.min(Math.max(b.x, area.left + lw / 2), area.right - lw / 2);
                        ctx.fillStyle = ink; ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
                        ctx.fillText(label, lx, b.y - 9);
                    }
                }
                ctx.restore();
            }
        };

        var chart = new Chart(el, {
            type: 'line',
            data: {
                labels: dates.map(dayShort),
                datasets: [{
                    data: series().slice(), borderColor: bar, backgroundColor: fade, fill: true,
                    borderWidth: 2, borderJoinStyle: 'round', borderCapStyle: 'round',
                    // smooth like the Sales Report, but monotone: the curve never dips below zero or swells past a day's value
                    cubicInterpolationMode: 'monotone',
                    pointRadius: 0, pointHitRadius: 12, pointHoverRadius: 5,
                    pointHoverBackgroundColor: bar, pointHoverBorderColor: card, pointHoverBorderWidth: 2
                }]
            },
            plugins: [marks],
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                layout: { padding: { top: 20, right: 52 } },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false, padding: 10, cornerRadius: 8, titleMarginBottom: 6,
                        callbacks: {
                            title: function (i) { return dayLong(dates[i[0].dataIndex]); },
                            label: function (i) {
                                var n = d.count[i.dataIndex], r = d.revenue[i.dataIndex];
                                if (!n) { return T.noSales; }
                                return [T.revenue + ': ' + money(r), T.trx + ': ' + Number(n).toLocaleString(), T.avgSale + ': ' + money(Math.round(r / n * 100) / 100)];
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false, drawBorder: false }, ticks: { color: muted, maxRotation: 0, autoSkip: true, autoSkipPadding: 12, maxTicksLimit: el.clientWidth < 520 ? 4 : 10, font: { size: 11 } } },
                    y: {
                        beginAtZero: true, grid: { color: grid, borderDash: [4, 4], drawBorder: false, drawTicks: false },
                        ticks: {
                            color: muted, padding: 8, maxTicksLimit: 5, precision: 0, font: { size: 11 },
                            // one style per axis: every tick compact once the axis reaches 10,000
                            callback: function (v) { return this.max >= 10000 ? compact(v) : Number(v).toLocaleString(); }
                        }
                    }
                }
            }
        });

        function apply() {
            var st = stats();
            titleEl.textContent = titleEl.getAttribute(metric === 'revenue' ? 'data-revenue' : 'data-count');
            el.setAttribute('aria-label', titleEl.textContent);
            totalEl.textContent = fmt(metric, st.sum);
            seg.forEach(function (b) { b.setAttribute('aria-checked', b.getAttribute('data-metric') === metric ? 'true' : 'false'); });
            chart.data.datasets[0].data = series().slice();
            chart.update();
        }
        seg.forEach(function (b) {
            b.addEventListener('click', function () {
                metric = b.getAttribute('data-metric');
                try { localStorage.setItem('rp_trend_metric', metric); } catch (e) { }
                apply();
            });
        });
        apply();
    }
});
</script>

{include file="sections/footer.tpl"}
