{include file="sections/header.tpl"}
<style>
    .isp-button {
        padding: 5px 12px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 6px !important;
        border: 1px solid transparent !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        outline: none !important;
        line-height: 1.3 !important;
    }
    .isp-button-day,
    .isp-button.active {
        background-color: #4299e1 !important;
        color: #ffffff !important;
        border-color: #4299e1 !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px rgba(66, 153, 225, 0.3) !important;
    }
    .dark-mode .isp-button-day,
    .dark-mode .isp-button.active,
    .dark .isp-button-day,
    .dark .isp-button.active {
        background-color: #4299e1 !important;
        color: #ffffff !important;
        border-color: #4299e1 !important;
        box-shadow: 0 1px 2px rgba(66, 153, 225, 0.35) !important;
    }
    .isp-button-secondary {
        background-color: #edf2f7 !important;
        color: #4a5568 !important;
        border: 1px solid #e2e8f0 !important;
    }
    .isp-button-secondary:hover {
        background-color: #e2e8f0 !important;
        color: #2d3748 !important;
    }
    .dark-mode .isp-button-secondary,
    .dark .isp-button-secondary {
        background-color: #2d3748 !important;
        color: #cbd5e0 !important;
        border: 1px solid #4a5568 !important;
    }
    .dark-mode .isp-button-secondary:hover,
    .dark .isp-button-secondary:hover {
        background-color: #4a5568 !important;
        color: #ffffff !important;
    }
    .isp-text-blue {
        color: #4299e1 !important;
        font-weight: 500 !important;
        cursor: pointer;
        transition: opacity 0.15s ease;
        text-decoration: none !important;
    }
    .isp-text-blue:hover {
        opacity: 0.8;
    }
    .dark-mode .isp-text-blue,
    .dark .isp-text-blue {
        color: #63b3ed !important;
    }
</style>

<!-- Stats Cards (100% Real Database Values) -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-md-2 isp-grid-cols-lg-4 isp-gap-6 isp-margin-bottom-6">
    <!-- Card 1: Total Customers -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="total-customers-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Total Customers')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{$c_all}</p>
                <p class="isp-text-sm {if $c_percentage_change >= 0}isp-text-green{else}isp-text-red{/if} isp-margin-top-1 isp-flex isp-align-center">
                    <i class="fas {if $c_percentage_change >= 0}fa-arrow-up{else}fa-arrow-down{/if} isp-margin-right-1"></i>
                    {$c_percentage_change}% {Lang::T('from last month')}
                </p>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-blue-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-users isp-text-blue"></i>
            </div>
        </div>
    </div>

    <!-- Card 2: Active Connections -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="active-connections-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Active Connections')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{$u_act}</p>
                <p class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center">
                    <i class="fas fa-signal isp-margin-right-1"></i>
                    {$u_act_pct}% {Lang::T('Active')} {if $u_expired > 0}• {$u_expired} {Lang::T('Expired')}{/if}
                </p>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-green-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-wifi isp-text-green"></i>
            </div>
        </div>
    </div>

    <!-- Card 3: Monthly Revenue -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="monthly-revenue-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Monthly Revenue')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{Lang::moneyFormat($imonth)}</p>
                <p class="isp-text-sm {if $rev_percentage_change >= 0}isp-text-green{else}isp-text-red{/if} isp-margin-top-1 isp-flex isp-align-center">
                    <i class="fas {if $rev_percentage_change >= 0}fa-arrow-up{else}fa-arrow-down{/if} isp-margin-right-1"></i>
                    {$rev_percentage_change}% {Lang::T('from last month')}
                </p>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-purple-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-dollar-sign isp-text-purple"></i>
            </div>
        </div>
    </div>

    <!-- Card 4: Today Sales -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="today-sales-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Today Sales')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{Lang::moneyFormat($iday)}</p>
                <p class="isp-text-sm isp-text-blue isp-margin-top-1 isp-flex isp-align-center">
                    <i class="fas fa-receipt isp-margin-right-1"></i>
                    {$iday_count} {Lang::T('Transactions')}
                </p>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-yellow-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-coins isp-text-yellow"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Charts Row -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Sales Report Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="sales-report-title" class="isp-text-lg isp-font-semibold">{Lang::T('Sales Report')}</h2>
            <div class="isp-flex isp-space-x-2" id="sales-timeframe-btns">
                <button type="button" class="isp-button isp-button-day active" data-timeframe="day">{Lang::T('Day')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-timeframe="week">{Lang::T('Week')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-timeframe="month">{Lang::T('Month')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-timeframe="year">{Lang::T('Year')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Customer Growth Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="customer-growth-title" class="isp-text-lg isp-font-semibold">{Lang::T('Customer Growth')}</h2>
            <div class="isp-flex isp-space-x-2" id="customer-filter-btns">
                <button type="button" class="isp-button isp-button-day active" data-filter="all">{Lang::T('All')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-filter="new">{Lang::T('New')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-filter="churned">{Lang::T('Churned')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="customersChart"></canvas>
        </div>
    </div>
</div>

<!-- Second Charts Row -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Network Status Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="network-status-title" class="isp-text-lg isp-font-semibold">{Lang::T('Network Status')}</h2>
            <div class="isp-flex isp-align-center">
                <span class="isp-status-indicator {if $routers_offline == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                <span id="network-status-uptime" class="isp-text-xs">{$network_uptime}% {Lang::T('uptime')}</span>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="networkChart"></canvas>
        </div>
    </div>

    <!-- Transactions by Service (replaces dummy Support Tickets) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="services-trx-title" class="isp-text-lg isp-font-semibold">{Lang::T('Transactions by Service')}</h2>
            <div class="isp-flex isp-space-x-2" id="trx-metric-btns">
                <button type="button" class="isp-button isp-button-day active" data-metric="totals">{Lang::T('Revenue')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-metric="counts">{Lang::T('Count')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="ticketsChart"></canvas>
        </div>
    </div>
</div>

<!-- Third Row with smaller charts -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-md-2 isp-grid-cols-lg-3 isp-gap-6 isp-margin-bottom-6">
    <!-- Router Activity / Usage -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="hotspot-usage-title" class="isp-text-lg isp-font-semibold">{Lang::T('Router Activity')}</h2>
            <a href="{Text::url('routers')}" class="isp-text-xs isp-text-blue">{Lang::T('View All')}</a>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <canvas id="hotspotChart"></canvas>
        </div>
    </div>

    <!-- Day of Week Activity -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="portal-logins-title" class="isp-text-lg isp-font-semibold">{Lang::T('Day of Week Activity')}</h2>
            <a href="{Text::url('reports')}" class="isp-text-xs isp-text-blue">{Lang::T('View All')}</a>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <canvas id="portalChart"></canvas>
        </div>
    </div>

    <!-- Plan Distribution by Type -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="bandwidth-usage-title" class="isp-text-lg isp-font-semibold">{Lang::T('Plan Distribution')}</h2>
            <a href="{Text::url('plan/list')}" class="isp-text-xs isp-text-blue">{Lang::T('View All')}</a>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <canvas id="bandwidthChart"></canvas>
        </div>
    </div>
</div>

<!-- Fourth Row: Top Selling Plans & Hourly Activity -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6">
    <!-- Top Selling Plans -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="top-plans-title" class="isp-text-lg isp-font-semibold">{Lang::T('Top Selling Plans')}</h2>
            <div class="isp-flex isp-space-x-2" id="topplans-metric-btns">
                <button type="button" class="isp-button isp-button-day active" data-metric="counts">{Lang::T('Count')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-metric="totals">{Lang::T('Revenue')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="consumptionChart"></canvas>
        </div>
    </div>

    <!-- Hourly Activity (24 Hours) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="hourly-activity-title" class="isp-text-lg isp-font-semibold">{Lang::T('Hourly Activity (24h)')}</h2>
            <div class="isp-flex isp-align-center">
                <span class="isp-status-indicator isp-status-online"></span>
                <span id="hourly-peak-time" class="isp-text-xs"><i class="fas fa-clock isp-margin-right-1"></i>Peak: {$peak_hour_str}</span>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="latencyChart"></canvas>
        </div>
    </div>
</div>

<!-- Fifth Row: Peak Usage & System Inventory -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-top-6">
    <!-- Peak Usage Hours (3-Hour Blocks) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="peak-usage-title" class="isp-text-lg isp-font-semibold">{Lang::T('Peak Usage Hours')}</h2>
            <span class="isp-text-xs isp-text-blue">{Lang::T('Last 30 days')}</span>
        </div>
        <div class="isp-chart-container">
            <canvas id="peakUsageChart"></canvas>
        </div>
    </div>

    <!-- System Inventory -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="equipment-status-title" class="isp-text-lg isp-font-semibold">{Lang::T('System Inventory')}</h2>
            <div class="isp-flex isp-align-center isp-space-x-2">
                <span class="isp-flex isp-align-center isp-text-xs">
                    <span class="isp-status-indicator {if $routers_offline == 0}isp-status-online{else}isp-status-warning{/if} isp-margin-right-1"></span>
                    {$network_uptime}% {Lang::T('Online')}
                </span>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="equipmentChart"></canvas>
        </div>
    </div>
</div>

<!-- Real Database Data Payload -->
<script id="dashboard-raw-data" type="application/json">
{$dashboard_data_json}
</script>
<script>
    window.dashboardCurrencyCode = "{$_c['currency_code']}";
</script>

{literal}
<script>
    // Initialize chart variables
    let salesChart, customersChart, networkChart, ticketsChart, hotspotChart, portalChart, bandwidthChart;
    let consumptionChart, latencyChart, peakUsageChart, equipmentChart;

    // Load real data from payload
    let dData = {};
    try {
        const rawEl = document.getElementById('dashboard-raw-data');
        if (rawEl && rawEl.textContent) {
            dData = JSON.parse(rawEl.textContent);
        }
    } catch (e) {
        console.error('Failed to parse dashboard data payload:', e);
    }

    const curCode = window.dashboardCurrencyCode || '';

    function formatMoney(val) {
        const num = Number(val) || 0;
        return (curCode ? curCode + ' ' : '') + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getThemeColors() {
        const isDark = document.body.classList.contains('dark-mode') || document.body.classList.contains('dark');
        return {
            isDark: isDark,
            blue: '#4299e1',
            blueHover: '#3182ce',
            green: '#48bb78',
            red: '#f56565',
            orange: '#ed8936',
            purple: '#9f7aea',
            indigo: '#667eea',
            yellow: '#ecc94b',
            teal: '#38b2ac',
            gridColor: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)',
            textColor: isDark ? '#a0aec0' : '#718096'
        };
    }

    function initAllDashboardCharts() {
        const colors = getThemeColors();
        const isDark = colors.isDark;
        const gridColor = colors.gridColor;
        const textColor = colors.textColor;

        // 1. Sales Report Chart (Day, Week, Month, Year)
        const salesCanvas = document.getElementById('salesChart');
        if (salesCanvas && dData.sales && dData.sales.day) {
            const salesCtx = salesCanvas.getContext('2d');
            const salesGrad = salesCtx.createLinearGradient(0, 0, 0, 240);
            salesGrad.addColorStop(0, 'rgba(66, 153, 225, 0.40)');
            salesGrad.addColorStop(0.7, 'rgba(66, 153, 225, 0.08)');
            salesGrad.addColorStop(1, 'rgba(66, 153, 225, 0.00)');

            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: dData.sales.day.labels,
                    datasets: [{
                        label: 'Sales Revenue',
                        data: dData.sales.day.values,
                        borderColor: colors.blue,
                        backgroundColor: salesGrad,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: colors.blue,
                        pointHoverBorderColor: isDark ? '#1a202c' : '#ffffff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return 'Revenue: ' + formatMoney(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: {
                                color: textColor,
                                callback: function (val) {
                                    return (curCode ? curCode + ' ' : '') + Number(val).toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 2. Customer Growth Chart (All, New, Churned)
        const custCanvas = document.getElementById('customersChart');
        if (custCanvas && dData.customerGrowth) {
            const custCtx = custCanvas.getContext('2d');
            customersChart = new Chart(custCtx, {
                type: 'bar',
                data: {
                    labels: dData.customerGrowth.labels,
                    datasets: [
                        {
                            label: 'New Customers',
                            data: dData.customerGrowth.new,
                            backgroundColor: colors.green,
                            borderRadius: 6
                        },
                        {
                            label: 'Expired / Churned',
                            data: dData.customerGrowth.churned,
                            backgroundColor: colors.red,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 3. Network Status Chart (Online vs Offline Routers)
        const netCanvas = document.getElementById('networkChart');
        if (netCanvas && dData.network) {
            const netCtx = netCanvas.getContext('2d');
            networkChart = new Chart(netCtx, {
                type: 'doughnut',
                data: {
                    labels: dData.network.labels,
                    datasets: [{
                        data: dData.network.values,
                        backgroundColor: [
                            colors.green,
                            colors.red
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
                        }
                    }
                }
            });
        }

        // 4. Transactions by Service (formerly Tickets Chart)
        const trxCanvas = document.getElementById('ticketsChart');
        if (trxCanvas && dData.transactions) {
            const trxCtx = trxCanvas.getContext('2d');
            ticketsChart = new Chart(trxCtx, {
                type: 'bar',
                data: {
                    labels: dData.transactions.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: dData.transactions.totals,
                        backgroundColor: colors.blue,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return formatMoney(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: {
                                color: textColor,
                                callback: function (val) {
                                    return (curCode ? curCode + ' ' : '') + Number(val).toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 5. Router Activity Chart (formerly Hotspot Chart)
        const hsCanvas = document.getElementById('hotspotChart');
        if (hsCanvas && dData.routerUsage) {
            const hsCtx = hsCanvas.getContext('2d');
            const hsColors = [
                'rgba(66, 153, 225, 0.85)',
                'rgba(102, 126, 234, 0.85)',
                'rgba(159, 122, 234, 0.85)',
                'rgba(237, 137, 54, 0.85)',
                'rgba(72, 187, 120, 0.85)',
                'rgba(245, 101, 101, 0.85)'
            ];
            hotspotChart = new Chart(hsCtx, {
                type: 'bar',
                data: {
                    labels: dData.routerUsage.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: dData.routerUsage.values,
                        backgroundColor: hsColors.slice(0, dData.routerUsage.labels.length),
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 6. Day of Week Activity Radar Chart (formerly Portal Chart)
        const portalCanvas = document.getElementById('portalChart');
        if (portalCanvas && dData.portalLogins) {
            const portalCtx = portalCanvas.getContext('2d');
            portalChart = new Chart(portalCtx, {
                type: 'radar',
                data: {
                    labels: dData.portalLogins.labels,
                    datasets: [{
                        label: 'Activity',
                        data: dData.portalLogins.values,
                        backgroundColor: 'rgba(102, 126, 234, 0.25)',
                        borderColor: colors.indigo,
                        borderWidth: 2,
                        pointBackgroundColor: colors.indigo
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        r: {
                            angleLines: { color: gridColor },
                            grid: { color: gridColor },
                            ticks: { color: textColor, backdropColor: 'transparent', precision: 0 },
                            pointLabels: { color: textColor },
                            suggestedMin: 0
                        }
                    }
                }
            });
        }

        // 7. Plan Distribution Polar Chart (formerly Bandwidth Chart)
        const bwCanvas = document.getElementById('bandwidthChart');
        if (bwCanvas && dData.planTypes) {
            const bwCtx = bwCanvas.getContext('2d');
            const ptColors = [
                'rgba(66, 153, 225, 0.8)',
                'rgba(72, 187, 120, 0.8)',
                'rgba(159, 122, 234, 0.8)',
                'rgba(237, 137, 54, 0.8)',
                'rgba(245, 101, 101, 0.8)'
            ];
            bandwidthChart = new Chart(bwCtx, {
                type: 'polarArea',
                data: {
                    labels: dData.planTypes.labels,
                    datasets: [{
                        data: dData.planTypes.values,
                        backgroundColor: ptColors.slice(0, dData.planTypes.labels.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
                        }
                    },
                    scales: {
                        r: {
                            grid: { color: gridColor },
                            angleLines: { color: gridColor },
                            ticks: { color: textColor, backdropColor: 'transparent', precision: 0 }
                        }
                    }
                }
            });
        }

        // 8. Top Selling Plans (formerly Data Consumption Chart)
        const consCanvas = document.getElementById('consumptionChart');
        if (consCanvas && dData.topPlans) {
            const consCtx = consCanvas.getContext('2d');
            consumptionChart = new Chart(consCtx, {
                type: 'bar',
                data: {
                    labels: dData.topPlans.labels,
                    datasets: [{
                        label: 'Sold Count',
                        data: dData.topPlans.counts,
                        backgroundColor: colors.purple,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 9. Hourly Activity 24h (formerly Network Latency Chart)
        const latCanvas = document.getElementById('latencyChart');
        if (latCanvas && dData.hourly) {
            const latCtx = latCanvas.getContext('2d');
            const ltGrad = latCtx.createLinearGradient(0, 0, 0, 220);
            ltGrad.addColorStop(0, 'rgba(237, 137, 54, 0.35)');
            ltGrad.addColorStop(1, 'rgba(237, 137, 54, 0.00)');

            latencyChart = new Chart(latCtx, {
                type: 'line',
                data: {
                    labels: dData.hourly.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: dData.hourly.values,
                        borderColor: colors.orange,
                        backgroundColor: ltGrad,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: colors.orange,
                        pointHoverBorderColor: isDark ? '#1a202c' : '#ffffff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 10. Peak Usage Chart (3-Hour Blocks)
        const pkCanvas = document.getElementById('peakUsageChart');
        if (pkCanvas && dData.peakUsage) {
            const pkCtx = pkCanvas.getContext('2d');
            const pkColors = [
                'rgba(66, 153, 225, 0.85)',
                'rgba(66, 153, 225, 0.85)',
                'rgba(66, 153, 225, 0.85)',
                'rgba(102, 126, 234, 0.85)',
                'rgba(159, 122, 234, 0.85)',
                'rgba(237, 137, 54, 0.85)',
                'rgba(237, 137, 54, 0.85)',
                'rgba(102, 126, 234, 0.85)'
            ];
            peakUsageChart = new Chart(pkCtx, {
                type: 'bar',
                data: {
                    labels: dData.peakUsage.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: dData.peakUsage.values,
                        backgroundColor: pkColors,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 11. System Inventory Chart (formerly Equipment Chart)
        const eqCanvas = document.getElementById('equipmentChart');
        if (eqCanvas && dData.equipment) {
            const eqCtx = eqCanvas.getContext('2d');
            const eqColors = [
                '#4299e1',
                '#667eea',
                '#9f7aea',
                '#ed8936',
                '#48bb78',
                '#f56565'
            ];
            equipmentChart = new Chart(eqCtx, {
                type: 'doughnut',
                data: {
                    labels: dData.equipment.labels,
                    datasets: [{
                        data: dData.equipment.values,
                        backgroundColor: eqColors.slice(0, dData.equipment.labels.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
                        }
                    }
                }
            });
        }
    }

    function setupInteractiveButtons() {
        // 1. Sales Report Timeframe Toggle
        const salesBtns = document.getElementById('sales-timeframe-btns');
        if (salesBtns && dData.sales) {
            salesBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    salesBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const tf = this.getAttribute('data-timeframe');
                    if (salesChart && dData.sales[tf]) {
                        salesChart.data.labels = dData.sales[tf].labels;
                        salesChart.data.datasets[0].data = dData.sales[tf].values;
                        salesChart.update();
                    }
                });
            });
        }

        // 2. Customer Growth Filter Toggle
        const custBtns = document.getElementById('customer-filter-btns');
        if (custBtns && dData.customerGrowth) {
            custBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    custBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const filter = this.getAttribute('data-filter');
                    if (customersChart) {
                        if (filter === 'all') {
                            customersChart.data.datasets[0].hidden = false;
                            customersChart.data.datasets[1].hidden = false;
                        } else if (filter === 'new') {
                            customersChart.data.datasets[0].hidden = false;
                            customersChart.data.datasets[1].hidden = true;
                        } else if (filter === 'churned') {
                            customersChart.data.datasets[0].hidden = true;
                            customersChart.data.datasets[1].hidden = false;
                        }
                        customersChart.update();
                    }
                });
            });
        }

        // 3. Transactions by Service Metric Toggle
        const trxBtns = document.getElementById('trx-metric-btns');
        if (trxBtns && dData.transactions) {
            trxBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    trxBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const metric = this.getAttribute('data-metric');
                    if (ticketsChart) {
                        ticketsChart.data.datasets[0].label = metric === 'totals' ? 'Revenue' : 'Count';
                        ticketsChart.data.datasets[0].data = dData.transactions[metric];
                        ticketsChart.options.plugins.tooltip.callbacks.label = function (ctx) {
                            return metric === 'totals' ? formatMoney(ctx.parsed.y) : ctx.parsed.y + ' transactions';
                        };
                        ticketsChart.update();
                    }
                });
            });
        }

        // 4. Top Selling Plans Metric Toggle
        const topPlansBtns = document.getElementById('topplans-metric-btns');
        if (topPlansBtns && dData.topPlans) {
            topPlansBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    topPlansBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const metric = this.getAttribute('data-metric');
                    if (consumptionChart) {
                        consumptionChart.data.datasets[0].label = metric === 'totals' ? 'Revenue' : 'Sold Count';
                        consumptionChart.data.datasets[0].data = dData.topPlans[metric];
                        consumptionChart.options.plugins.tooltip.callbacks = {
                            label: function (ctx) {
                                return metric === 'totals' ? formatMoney(ctx.parsed.y) : ctx.parsed.y + ' plans';
                            }
                        };
                        consumptionChart.update();
                    }
                });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAllDashboardCharts();
        setupInteractiveButtons();
    });
</script>
{/literal}

{if $_c['new_version_notify'] != 'disable'}
<script>
    window.addEventListener('DOMContentLoaded', function () {
        $.getJSON("./version.json?" + Math.random(), function (data) {
            var localVersion = data.version;
            $('#version').html('Version: ' + localVersion);
            $.getJSON(
                "https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" + Math.random(),
                function (data) {
                    var latestVersion = data.version;
                    if (localVersion !== latestVersion) {
                        $('#version').html('Latest Version: ' + latestVersion);
                        if (getCookie(latestVersion) != 'done') {
                            Swal.fire({
                                icon: 'info',
                                title: "New Version Available\nVersion: " + latestVersion,
                                toast: true,
                                position: 'bottom-right',
                                showConfirmButton: true,
                                showCloseButton: true,
                                timer: 30000,
                                confirmButtonText: '<a href="{Text::url('community')}#latestVersion" style="color: white;">Update Now</a>',
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer);
                                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                                }
                            });
                            setCookie(latestVersion, 'done', 7);
                        }
                    }
                });
        });
    });
</script>
{/if}

{include file="sections/footer.tpl"}