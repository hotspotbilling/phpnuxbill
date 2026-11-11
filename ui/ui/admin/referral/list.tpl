{include file="sections/header.tpl"}

<div class="ls rf">
    <div class="ls-top">
        <p class="ls-sub">
            {if $_c['referral_enabled'] == 'no'}
            <span class="ls-pill ls-pill--inactive"><i class="fa fa-pause" aria-hidden="true"></i> {Lang::T('Program is off')}</span>
            {else}
            <span class="ls-pill ls-pill--active"><i class="fa fa-check" aria-hidden="true"></i> {Lang::T('Program is on')}</span>
            {/if}
        </p>
        <div class="ls-actions">
            <a href="{Text::url('referrals/settings')}" class="ls-btn"><i class="fa fa-cog" aria-hidden="true"></i> {Lang::T('Settings')}</a>
        </div>
    </div>

    <div class="rf-kpis">
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-user-plus" aria-hidden="true"></i></span>
            <small>{Lang::T('Referrals')}</small>
            <b>{$rewards_stats['referrals']|number_format}</b>
            <span class="rf-kpi__sub">{if $rewards_stats['new_referrals'] > 0}<em class="rf-up">+{$rewards_stats['new_referrals']|number_format}</em> {Lang::T('in the last 30 days')}{else}{Lang::T('None in the last 30 days')}{/if}</span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-gift" aria-hidden="true"></i></span>
            <small>{Lang::T('Rewarded')}</small>
            <b>{$rewards_stats['rewarded']|number_format}</b>
            <span class="rf-kpi__sub">{if $rewards_stats['referrals'] > 0}{($rewards_stats['rewarded'] / $rewards_stats['referrals'] * 100)|number_format:0}% {Lang::T('of referrals earned a reward')}{else}{Lang::T('No referrals yet')}{/if}</span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-money-bill" aria-hidden="true"></i></span>
            <small>{Lang::T('Paid out')}</small>
            <b>{Lang::moneyFormat($rewards_stats['paid'])}</b>
            <span class="rf-kpi__sub">{Lang::T('Referrers')} {Lang::moneyFormat($rewards_stats['paid_referrer'])} &middot; {Lang::T('New customers')} {Lang::moneyFormat($rewards_stats['paid_referred'])}</span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon rf-kpi__icon--warn"><i class="fa fa-clock" aria-hidden="true"></i></span>
            <small>{Lang::T('Pending')}</small>
            <b>{$rewards_stats['by_status']['pending']['count']|number_format}</b>
            <span class="rf-kpi__sub">{if $rewards_stats['by_status']['pending']['count'] > 0}{Lang::moneyFormat($rewards_stats['by_status']['pending']['amount'])} {Lang::T('waiting for a purchase')}{else}{Lang::T('Nothing waiting')}{/if}</span>
        </div>
    </div>

    <div class="rf-grid">
        <section class="ls-card rf-panel">
            <header class="rf-panel__head">
                <div>
                    <h3>{Lang::T('Rewards paid')}</h3>
                    <small>{Lang::T('Last 30 days')}</small>
                </div>
                <b class="rf-panel__total">{Lang::moneyFormat($rewards_trend['total'])}</b>
            </header>
            <div class="rf-chart">
                {if $rewards_trend['total'] > 0}
                <canvas id="rfTrend" role="img" aria-label="{Lang::T('Rewards paid per day, last 30 days')}"></canvas>
                <table class="sr-only">
                    <caption>{Lang::T('Rewards paid per day, last 30 days')}</caption>
                    <thead><tr><th scope="col">{Lang::T('Date')}</th><th scope="col">{Lang::T('Paid')}</th><th scope="col">{Lang::T('Rewards')}</th></tr></thead>
                    <tbody>
                        {foreach $rewards_trend['labels'] as $i => $day}
                        <tr><th scope="row">{Lang::dateFormat($day)}</th><td>{Lang::moneyFormat($rewards_trend['data'][$i])}</td><td>{$rewards_trend['counts'][$i]}</td></tr>
                        {/foreach}
                    </tbody>
                </table>
                {else}
                <div class="ls-empty"><i class="fa fa-bar-chart rf-empty-icon" aria-hidden="true"></i><b>{Lang::T('No rewards paid in the last 30 days')}</b>{Lang::T('Paid rewards appear here day by day.')}</div>
                {/if}
            </div>
        </section>

        <section class="ls-card rf-panel">
            <header class="rf-panel__head">
                <div>
                    <h3>{Lang::T('Top referrers')}</h3>
                    <small>{Lang::T('By customers brought in')}</small>
                </div>
            </header>
            {if $top_referrers}
            <ol class="rf-top">
                {foreach $top_referrers as $t}
                <li>
                    <span class="rf-top__rank">{$t@iteration}</span>
                    <div class="rf-top__body">
                        <div class="rf-top__line">
                            <a href="{Text::url('customers/view/', $t['referrer_id'])}" class="rf-top__name" title="{$t['username']|escape}">{if $t['fullname']}{$t['fullname']|escape}{else}{$t['username']|escape}{/if}</a>
                            <span class="rf-top__n">{$t['referrals']|number_format} <small>{if $t['referrals'] == 1}{Lang::T('referral')}{else}{Lang::T('referrals')}{/if}</small></span>
                        </div>
                        <div class="rf-top__bar"><span style="width: {if $top_max > 0}{($t['referrals'] / $top_max * 100)|string_format:'%.1f'}{else}0{/if}%"></span></div>
                        <small class="rf-top__earned">{Lang::T('Earned')} {Lang::moneyFormat($t['earned'])}</small>
                    </div>
                </li>
                {/foreach}
            </ol>
            {else}
            <div class="ls-empty"><i class="fa fa-users rf-empty-icon" aria-hidden="true"></i><b>{Lang::T('No referrers yet')}</b>{Lang::T('Customers who share their link show up here.')}</div>
            {/if}
        </section>
    </div>

    <div class="ls-card">
        <form class="ls-bar" method="post" action="{Text::url('referrals/list')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" name="q" autocomplete="off" value="{$search|escape}"
                    placeholder="{Lang::T('Search by customer or code')}..." aria-label="{Lang::T('Search')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            {if $search}<a class="ls-btn" href="{Text::url('referrals/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Clear')}</a>{/if}
        </form>
        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Date')}</th>
                        <th>{Lang::T('Referrer')}</th>
                        <th aria-hidden="true"></th>
                        <th>{Lang::T('New customer')}</th>
                        <th>{Lang::T('Code')}</th>
                        <th class="ls-num">{Lang::T('Rewards')}</th>
                        <th class="ls-num">{Lang::T('Paid')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $referrals as $r}
                    <tr>
                        <td data-label="{Lang::T('Date')}" class="ls-dim ls-when" title="{$r['created_at']|escape}">{Lang::dateTimeFormat($r['created_at'])}</td>
                        <td data-label="{Lang::T('Referrer')}">
                            <a class="ls-strong" href="{Text::url('customers/view/', $r['referrer_id'])}">{if $r['referrer_name']}{$r['referrer_name']|escape}{else}{$r['referrer_username']|escape}{/if}</a>
                            {if $r['referrer_status'] && $r['referrer_status'] != 'Active'}<span class="ls-pill ls-pill--{$r['referrer_status']|lower|escape}">{Lang::T($r['referrer_status'])|escape}</span>{/if}
                            <small class="ls-dim">{$r['referrer_username']|escape}</small>
                        </td>
                        <td class="rf-arrow" aria-hidden="true"><i class="fa fa-long-arrow-right"></i></td>
                        <td data-label="{Lang::T('New customer')}">
                            <a class="ls-strong" href="{Text::url('customers/view/', $r['referred_id'])}">{if $r['referred_name']}{$r['referred_name']|escape}{else}{$r['referred_username']|escape}{/if}</a>
                            {if $r['referred_status'] && $r['referred_status'] != 'Active'}<span class="ls-pill ls-pill--{$r['referred_status']|lower|escape}">{Lang::T($r['referred_status'])|escape}</span>{/if}
                            <small class="ls-dim">{$r['referred_username']|escape}</small>
                        </td>
                        <td data-label="{Lang::T('Code')}"><code class="rf-code">{$r['referral_code']|escape}</code></td>
                        <td data-label="{Lang::T('Rewards')}" class="ls-num">
                            {if $r['reward_count'] > 0}{$r['reward_count']}{else}<span class="ls-dim">0</span>{/if}
                            {if $r['pending_count'] > 0}<span class="ls-pill ls-pill--inactive" title="{Lang::T('Waiting for a purchase')}">{$r['pending_count']} {Lang::T('pending')}</span>{/if}
                        </td>
                        <td data-label="{Lang::T('Paid')}" class="ls-num">
                            {if $r['total_earned'] > 0}
                            <b class="ls-strong">{Lang::moneyFormat($r['total_earned'])}</b>
                            <small class="ls-dim" title="{Lang::T('Referrer')} / {Lang::T('New customer')}">{Lang::moneyFormat($r['referrer_earned'])} / {Lang::moneyFormat($r['referred_earned'])}</small>
                            {else}<span class="ls-dim">&ndash;</span>{/if}
                        </td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="7"><div class="ls-empty">
                        {if $search}
                        <b>{Lang::T('No referrals match your search')}</b>
                        {else}
                        <i class="fa fa-share-alt rf-empty-icon" aria-hidden="true"></i>
                        <b>{Lang::T('No referrals yet')}</b>{Lang::T('When a customer signs up with a referral link, it shows up here.')}
                        {/if}
                    </div></td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>
</div>

{if $rewards_trend['total'] > 0}
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('rfTrend');
    if (!el || !window.Chart) { return; }
    var trend = {$rewards_trend|json_encode};
    var T_PAID = {Lang::T('Paid')|json_encode};
    var T_REWARDS = {Lang::T('rewards')|json_encode};
    var T_REWARD = {Lang::T('reward')|json_encode};
    var cur = {$_c['currency_code']|json_encode};

    // colours come from the page's tokens, so the chart follows light/dark mode and the theme
    var css = getComputedStyle(el.closest('.rf'));
    var ink = css.getPropertyValue('--ls-muted').trim() || '#64748b';
    var line = css.getPropertyValue('--ls-line').trim() || '#e2e8f0';
    var fill = css.getPropertyValue('--rf-bar').trim() || '#4f46e5';
    var dates = trend.labels.map(function (d) { return new Date(d + 'T00:00:00'); });
    var short = function (d) { return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }); };
    var money = function (v) { return cur + ' ' + Number(v).toLocaleString(undefined, { maximumFractionDigits: 2 }); };

    new Chart(el, {
        type: 'bar',
        data: {
            labels: dates.map(short),
            datasets: [{ data: trend.data, backgroundColor: fill, hoverBackgroundColor: fill, borderRadius: 4, borderSkipped: 'bottom', maxBarThickness: 18, categoryPercentage: .8, barPercentage: .85 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    displayColors: false, padding: 10, cornerRadius: 8,
                    callbacks: {
                        title: function (i) { return dates[i[0].dataIndex].toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }); },
                        label: function (i) {
                            var n = trend.counts[i.dataIndex];
                            return [T_PAID + ': ' + money(i.raw), n + ' ' + (n === 1 ? T_REWARD : T_REWARDS)];
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: ink, maxRotation: 0, autoSkip: true, maxTicksLimit: 8, font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: line, drawBorder: false }, ticks: { color: ink, maxTicksLimit: 5, font: { size: 11 }, callback: function (v) { return Number(v).toLocaleString(); } } }
            }
        }
    });
});
</script>
{/if}

{include file="sections/footer.tpl"}
