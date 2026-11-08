{include file="sections/header.tpl"}
<style>
    /* Stat Cards Styling */
    .stat-card {
        position: relative;
        overflow: hidden;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .stat-card .panel-body {
        padding: 20px;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .stat-value {
        font-size: 32px;
        font-weight: bold;
        line-height: 1;
        margin-bottom: 8px;
    }

    .stat-card.panel-success .stat-value {
        color: #27ae60;
    }

    .stat-card.panel-warning .stat-value {
        color: #f39c12;
    }

    .stat-card.panel-info .stat-value {
        color: #3498db;
    }

    .stat-card.panel-primary .stat-value {
        color: #2980b9;
    }

    .stat-label {
        font-size: 13px;
        color: #7f8c8d;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 40px;
        opacity: 0.08;
        z-index: 1;
    }

    .stat-card.panel-success .panel-heading,
    .stat-card.panel-warning .panel-heading,
    .stat-card.panel-info .panel-heading,
    .stat-card.panel-primary .panel-heading {
        display: none;
    }

    .stat-card.panel-success {
        border-left: 4px solid #27ae60;
    }

    .stat-card.panel-warning {
        border-left: 4px solid #f39c12;
    }

    .stat-card.panel-info {
        border-left: 4px solid #3498db;
    }

    .stat-card.panel-primary {
        border-left: 4px solid #2980b9;
    }

    /* Every card on this page: the same transparent surface, neutral border and radius as the dashboard cards, in light and dark mode */
    .ref-page .panel, .ref-page .stat-card {
        background: transparent;
        border: 1px solid rgba(127, 127, 127, 0.28);
        border-radius: 12px;
        box-shadow: none;
    }
    .ref-page .stat-card.panel-success { border-left: 4px solid #27ae60; }
    .ref-page .stat-card.panel-warning { border-left: 4px solid #f39c12; }
    .ref-page .stat-card.panel-info { border-left: 4px solid #3498db; }
    .ref-page .stat-card.panel-primary { border-left: 4px solid #2980b9; }
    .ref-page .stat-card:hover { box-shadow: none; border-top-color: rgba(127, 127, 127, 0.5); border-right-color: rgba(127, 127, 127, 0.5); border-bottom-color: rgba(127, 127, 127, 0.5); }
    .ref-page .panel-heading { background: transparent; border-bottom: 1px solid rgba(127, 127, 127, 0.22); border-radius: 12px 12px 0 0; }
    .ref-page .stat-label { color: inherit; opacity: 0.7; }

    /* Chart Panels */
    .panel-heading {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .panel-heading i {
        font-size: 16px;
    }
</style>

<!-- Rewards Overview Section -->
<div class="row ref-page">
    <div class="col-md-12">
        <!-- Stats Cards -->
        <div class="row mb20">
            <div class="col-sm-3">
                <div class="panel panel-hovered panel-success stat-card">
                    <div class="panel-body">
                        <div class="stat-value"><sup>{$_c['currency_code']}</sup> {$rewards_stats['total_rewards']|default:0}</div>
                        <div class="stat-label">{Lang::T('Total Rewards Earned')}</div>
                    </div>
                    <div class="stat-icon">
                        <i class="ion ion-social-bitcoin-outline"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="panel panel-hovered panel-warning stat-card">
                    <div class="panel-body">
                        <div class="stat-value">{$rewards_stats['pending_count']|default:0}</div>
                        <div class="stat-label">{Lang::T('Pending')}</div>
                    </div>
                    <div class="stat-icon">
                        <i class="ion ion-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="panel panel-hovered panel-info stat-card">
                    <div class="panel-body">
                        <div class="stat-value">{$rewards_stats['processed_count']|default:0}</div>
                        <div class="stat-label">{Lang::T('Processed')}</div>
                    </div>
                    <div class="stat-icon">
                        <i class="ion ion-checkmark"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="panel panel-hovered panel-primary stat-card">
                    <div class="panel-body">
                        <div class="stat-value">{$rewards_stats['claimed_count']|default:0}</div>
                        <div class="stat-label">{Lang::T('Claimed')}</div>
                    </div>
                    <div class="stat-icon">
                        <i class="ion ion-checkmark-circled"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb20">
            <div class="col-md-6">
                <div class="panel panel-hovered panel-primary">
                    <div class="panel-heading">
                        <i class="ion ion-pie-graph"></i> {Lang::T('Reward Status Distribution')}
                    </div>
                    <div class="panel-body">
                        <canvas id="rewardStatusChart" style="height: 250px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-hovered panel-primary">
                    <div class="panel-heading">
                        <i class="ion ion-stats-bars"></i> {Lang::T('Reward Type Distribution')}
                    </div>
                    <div class="panel-body">
                        <canvas id="rewardTypeChart" style="height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Rewards Trend -->
        <div class="row mb20">
            <div class="col-md-12">
                <div class="panel panel-hovered panel-primary">
                    <div class="panel-heading">
                        <i class="ion ion-trending-up"></i> {Lang::T('Rewards Distribution Trend (Last 30 Days)')}
                    </div>
                    <div class="panel-body">
                        <canvas id="rewardsTrendChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reward Amount by Type -->
        <div class="row mb20">
            <div class="col-md-12">
                <div class="panel panel-hovered panel-primary">
                    <div class="panel-heading">
                        <i class="ion ion-cash"></i> {Lang::T('Total Reward Amount by Type')}
                    </div>
                    <div class="panel-body">
                        <canvas id="rewardAmountChart" style="height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Referrers Section -->
        <div class="row mb20">
            <div class="col-md-6">
                <div class="panel panel-hovered panel-primary">
                    <div class="panel-heading">
                        <i class="ion ion-person-stalking"></i> {Lang::T('Top 10 Referrers')}
                    </div>
                    <div class="panel-body">
                        {if $top_referrers && count($top_referrers) > 0}
                        <div class="table-responsive">
                            <div class="ls"><div class="ls-scroll"><table class="ls-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{Lang::T('Name')}</th>
                                        <th class="ls-num">{Lang::T('Rewards')}</th>
                                        <th class="ls-num">{Lang::T('Amount')} ({$_c['currency_code']})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $top_referrers as $idx => $ref}
                                    <tr>
                                        <td class="ls-dim">{$idx + 1}</td>
                                        <td data-label="{Lang::T('Name')}"><b class="ls-strong">{$ref['fullname']|escape}</b><small class="ls-dim">{$ref['username']|escape}</small></td>
                                        <td data-label="{Lang::T('Rewards')}" class="ls-num">{$ref['reward_count']}</td>
                                        <td data-label="{Lang::T('Amount')}" class="ls-num"><b class="ls-strong">{$ref['total_earned']|number_format:2}</b></td>
                                    </tr>
                                    {/foreach}
                                </tbody>
                            </table></div></div>
                        </div>
                        {else}
                        <div class="text-center text-muted" style="padding: 30px;">
                            <p>{Lang::T('No referrer data available')}</p>
                        </div>
                        {/if}
                    </div>
                </div>
            </div>

            <!-- Amount Earned Summary -->
            <div class="col-md-6">
                <div class="panel panel-hovered panel-primary">
                    <div class="panel-heading">
                        <i class="ion ion-cash"></i> {Lang::T('Amount Earned Summary')}
                    </div>
                    <div class="panel-body">
                        <div style="padding: 20px;">
                            <!-- Total Rewards -->
                            <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
                                <div style="font-size: 13px; color: #7f8c8d; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">
                                    {Lang::T('Total Rewards Distributed')}
                                </div>
                                <div style="font-size: 32px; font-weight: bold; color: #2980b9;">
                                    {$_c['currency_code']} {$rewards_stats['total_rewards']|number_format:2}
                                </div>
                            </div>

                            <!-- Referrers Earned -->
                            <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
                                <div style="font-size: 13px; color: #7f8c8d; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">
                                    {Lang::T('Amount Earned by Referrers')}
                                </div>
                                <div style="font-size: 32px; font-weight: bold; color: #27ae60;">
                                    {$_c['currency_code']} {$rewards_stats['total_referrer_earned']|number_format:2}
                                </div>
                            </div>

                            <!-- Referred Customers Earned -->
                            <div>
                                <div style="font-size: 13px; color: #7f8c8d; text-transform: uppercase; font-weight: 500; margin-bottom: 8px;">
                                    {Lang::T('Amount Earned by Referred Customers')}
                                </div>
                                <div style="font-size: 32px; font-weight: bold; color: #3498db;">
                                    {$_c['currency_code']} {$rewards_stats['total_referred_earned']|number_format:2}
                                </div>
                            </div>

                            <!-- Percentage Breakdown -->
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span>{Lang::T('Referrers Share')}</span>
                                    <strong>{if $rewards_stats['total_rewards'] > 0}{($rewards_stats['total_referrer_earned'] / $rewards_stats['total_rewards'] * 100)|number_format:1}%{else}0%{/if}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>{Lang::T('Referred Share')}</span>
                                    <strong>{if $rewards_stats['total_rewards'] > 0}{($rewards_stats['total_referred_earned'] / $rewards_stats['total_rewards'] * 100)|number_format:1}%{else}0%{/if}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-12">
        <div class="ls">
            <div class="ls-top">
                <div></div>
                <div class="ls-actions">
                    <a href="{getUrl('referrals/settings')}" class="ls-btn"><i class="fa fa-cog" aria-hidden="true"></i> {Lang::T('Settings')}</a>
                </div>
            </div>
            <div class="ls-card">
                <div class="ls-scroll">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>{Lang::T('Date')}</th>
                                <th>{Lang::T('Referrer')}</th>
                                <th>{Lang::T('Referrer Status')}</th>
                                <th>{Lang::T('Referred')}</th>
                                <th>{Lang::T('Referred Status')}</th>
                                <th>{Lang::T('Referral Code')}</th>
                                <th class="ls-num">{Lang::T('Rewards')}</th>
                                <th class="ls-num">{Lang::T('Earned')} ({$_c['currency_code']})</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $referrals as $r}
                            <tr>
                                <td data-label="{Lang::T('Date')}" class="ls-dim ls-when">{$r['created_at']|escape}</td>
                                <td data-label="{Lang::T('Referrer')}">
                                    <b class="ls-strong">{$r['referrer_name']|escape}</b>
                                    <small class="ls-dim">{$r['referrer_username']|escape}{if $r['referrer_email']} &middot; {$r['referrer_email']|escape}{/if}</small>
                                </td>
                                <td data-label="{Lang::T('Referrer Status')}">{if $r['referrer_status'] == 'Active'}<span class="ls-pill ls-pill--active">{Lang::T('Active')|escape}</span>{elseif $r['referrer_status'] == 'Inactive'}<span class="ls-pill ls-pill--inactive">{Lang::T('Inactive')|escape}</span>{else}<span class="ls-pill">{$r['referrer_status']|escape}</span>{/if}</td>
                                <td data-label="{Lang::T('Referred')}">
                                    <b class="ls-strong">{$r['referred_name']|escape}</b>
                                    <small class="ls-dim">{$r['referred_username']|escape}{if $r['referred_email']} &middot; {$r['referred_email']|escape}{/if}</small>
                                </td>
                                <td data-label="{Lang::T('Referred Status')}">{if $r['referred_status'] == 'Active'}<span class="ls-pill ls-pill--active">{Lang::T('Active')|escape}</span>{elseif $r['referred_status'] == 'Inactive'}<span class="ls-pill ls-pill--inactive">{Lang::T('Inactive')|escape}</span>{else}<span class="ls-pill">{$r['referred_status']|escape}</span>{/if}</td>
                                <td data-label="{Lang::T('Referral Code')}"><em class="ls-chip">{$r['referral_code']|escape}</em></td>
                                <td data-label="{Lang::T('Rewards')}" class="ls-num"><span title="{Lang::T('Referrer')|escape}: {$r['referrer_reward_count']|escape}, {Lang::T('Referred')|escape}: {$r['referred_reward_count']|escape}">{$r['reward_count']|default:0}</span></td>
                                <td data-label="{Lang::T('Earned')}" class="ls-num">
                                    {if $r['total_earned'] > 0}
                                    <b class="ls-strong">{$_c['currency_code']} {$r['total_earned']|number_format:2}</b>
                                    <small class="ls-dim" style="display:block">{$r['referrer_earned']|number_format:2} &middot; {$r['referred_earned']|number_format:2}</small>
                                    {else}<span class="ls-dim">-</span>{/if}
                                </td>
                            </tr>
                            {foreachelse}
                            <tr class="ls-none"><td colspan="8"><div class="ls-empty"><b>{Lang::T('No referral relationships found')}</b></div></td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
            </div>
        </div>
    </div>
</div>

<!-- Store data in data attributes for JavaScript access -->
<script>
    // Store Smarty data in window object for chart initialization
    var p = {$rewards_stats['pending_count']|default:0};
    var pc = {$rewards_stats['processed_count']|default:0};
    var c = {$rewards_stats['claimed_count']|default:0};
    var bt = {$rewards_by_type|json_encode|default:'{}'};
    var tr = {$rewards_trend|json_encode|default:'{}'};
    var ab = {$rewards_amount_by_type|json_encode|default:'{}'};
    const currencyCode = {$_c['currency_code']|json_encode};
    
    // Secure language labels - properly escaped strings
    const secureLabels = {
        pending: {Lang::T('Pending')|json_encode},
        processed: {Lang::T('Processed')|json_encode},
        claimed: {Lang::T('Claimed')|json_encode},
        balance: {Lang::T('Balance')|json_encode},
        discount: {Lang::T('Discount')|json_encode},
        planExt: {Lang::T('Plan Extension')|json_encode},
        count: {Lang::T('Count')|json_encode},
        total: {Lang::T('Total Amount')|json_encode},
        rewards: {Lang::T('Rewards Distributed')|json_encode},
        noData: {Lang::T('No data available for the last 30 days')|json_encode}
    };
    
    window.rewardsData = {
        stats: {
            pending: parseInt(p) || 0,
            processed: parseInt(pc) || 0,
            claimed: parseInt(c) || 0
        },
        byType: (typeof bt === 'string') ? JSON.parse(bt) : bt,
        trend: (typeof tr === 'string') ? JSON.parse(tr) : tr,
        amountByType: (typeof ab === 'string') ? JSON.parse(ab) : ab
    };

    document.addEventListener('DOMContentLoaded', function () {
        const data = window.rewardsData;
        const labels = secureLabels; // Use secured labels from above

        // Reward Status Chart (Pie/Doughnut)
        const statusCtx = document.getElementById('rewardStatusChart');
        if (statusCtx && data && data.stats) {
            try {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [labels.pending, labels.processed, labels.claimed],
                        datasets: [{
                            data: [data.stats.pending || 0, data.stats.processed || 0, data.stats.claimed || 0],
                            backgroundColor: ['#f39c12', '#3498db', '#27ae60'],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            } catch (e) {
                console.error('Status chart error:', e);
            }
        }

        // Reward Type Chart (Bar)
        const typeCtx = document.getElementById('rewardTypeChart');
        if (typeCtx && data && data.byType) {
            try {
                new Chart(typeCtx, {
                    type: 'bar',
                    data: {
                        labels: [labels.balance, labels.discount, labels.planExt],
                        datasets: [{
                            label: labels.count,
                            data: [
                                data.byType.balance || 0,
                                data.byType.discount || 0,
                                data.byType.plan_extension || 0
                            ],
                            backgroundColor: ['#e74c3c', '#f39c12', '#9b59b6'],
                            borderRadius: 5,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });
            } catch (e) {
                console.error('Type chart error:', e);
            }
        }

        // Rewards Trend Chart (Line)
        const trendCtx = document.getElementById('rewardsTrendChart');
        if (trendCtx && data && data.trend) {
            
            // If trend data is empty, show message in canvas
            if (!data.trend.labels || data.trend.labels.length === 0) {
                trendCtx.style.display = 'none';
                const msg = document.createElement('div');
                msg.style.cssText = 'text-align: center; padding: 120px 20px; color: #999; font-size: 14px;';
                msg.textContent = labels.noData;
                trendCtx.parentNode.appendChild(msg);
            } else {
                try {
                    new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: data.trend.labels,
                            datasets: [{
                                label: labels.rewards,
                                data: data.trend.data,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'top' } },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { callback: function (v) { return currencyCode + parseFloat(v).toFixed(2); } }
                                }
                            }
                        }
                    });
                } catch (e) {
                    console.error('Trend chart error:', e);
                }
            }
        }

        // Reward Amount by Type Chart
        const amountCtx = document.getElementById('rewardAmountChart');
        if (amountCtx && data && data.amountByType) {
            try {
                new Chart(amountCtx, {
                    type: 'bar',
                    data: {
                        labels: [labels.balance, labels.discount, labels.planExt],
                        datasets: [{
                            label: labels.total,
                            data: [
                                data.amountByType.balance || 0,
                                data.amountByType.discount || 0,
                                data.amountByType.plan_extension || 0
                            ],
                            backgroundColor: ['#2ecc71', '#3498db', '#e67e22'],
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { callback: function (v) { return currencyCode + parseFloat(v).toFixed(2); } }
                            }
                        }
                    }
                });
            } catch (e) {
                console.error('Amount chart error:', e);
            }
        }
    });
</script>
{include file="sections/footer.tpl"}