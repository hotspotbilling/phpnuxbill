{include file="sections/header.tpl"}
<!-- Stats Cards -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-md-2 isp-grid-cols-lg-4 isp-gap-6 isp-margin-bottom-6">
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div>
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1 isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p
                    class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center isp-skeleton isp-skeleton-stat-small isp-hidden">
                    <i class="fas fa-arrow-up isp-margin-right-1"></i> Loading...
                </p>
                <div id="total-customers-content">
                    <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Total Customers')}</p>
                    <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{$c_all}</p>
                    <p class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center">
                        <i class="fas fa-arrow-up isp-margin-right-1"></i> {$percentage_change}% {Lang::T('from last
                        month')}
                    </p>
                </div>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-blue-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-users isp-text-blue"></i>
            </div>
        </div>
    </div>

    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div>
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1 isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p
                    class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center isp-skeleton isp-skeleton-stat-small isp-hidden">
                    <i class="fas fa-arrow-up isp-margin-right-1"></i> Loading...
                </p>
                <div id="active-connections-content">
                    <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Active Connections')}</p>
                    <p class="isp-text-2xl isp-font-bold isp-margin-top-1">1,932</p>
                    <p class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center">
                        <i class="fas fa-arrow-up isp-margin-right-1"></i> 8.3% from yesterday
                    </p>
                </div>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-green-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-wifi isp-text-green"></i>
            </div>
        </div>
    </div>

    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div>
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1 isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p
                    class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center isp-skeleton isp-skeleton-stat-small isp-hidden">
                    <i class="fas fa-arrow-up isp-margin-right-1"></i> Loading...
                </p>
                <div id="monthly-revenue-content">
                    <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Monthly Revenue')}</p>
                    <p class="isp-text-2xl isp-font-bold isp-margin-top-1">$48,759</p>
                    <p class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center">
                        <i class="fas fa-arrow-up isp-margin-right-1"></i> 5.7% from last month
                    </p>
                </div>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-purple-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-dollar-sign isp-text-purple"></i>
            </div>
        </div>
    </div>

    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div>
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1 isp-skeleton isp-skeleton-stat isp-hidden">
                    Loading...</p>
                <p
                    class="isp-text-sm isp-text-red isp-margin-top-1 isp-flex isp-align-center isp-skeleton isp-skeleton-stat-small isp-hidden">
                    <i class="fas fa-arrow-up isp-margin-right-1"></i> Loading...
                </p>
                <div id="open-tickets-content">
                    <p class="isp-text-sm isp-font-medium isp-text-gray-dark">Open Tickets</p>
                    <p class="isp-text-2xl isp-font-bold isp-margin-top-1">47</p>
                    <p class="isp-text-sm isp-text-red isp-margin-top-1 isp-flex isp-align-center">
                        <i class="fas fa-arrow-up isp-margin-right-1"></i> 3 new today
                    </p>
                </div>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-yellow-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-ticket-alt isp-text-yellow"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Charts Row -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Sales Report Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="sales-report-title" class="isp-text-lg isp-font-semibold">Sales Report</h2>
            <div class="isp-flex isp-space-x-2">
                <button class="isp-button isp-button-day">Day</button>
                <button class="isp-button isp-button-secondary">Week</button>
                <button class="isp-button isp-button-secondary">Month</button>
                <button class="isp-button isp-button-secondary">Year</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <div id="sales-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="salesChart" class="isp-hidden"></canvas>
        </div>
    </div>

    <!-- Customer Growth Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden"> Loading...</h2>
            <h2 id="customer-growth-title" class="isp-text-lg isp-font-semibold">Customer Growth</h2>
            <div class="isp-flex isp-space-x-2">
                <button class="isp-button isp-button-day">All</button>
                <button class="isp-button isp-button-secondary">New</button>
                <button class="isp-button isp-button-secondary">Churned</button>
            </div>  
        </div>
        <div class="isp-chart-container">
            <div id="customers-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="customersChart" class="isp-hidden"></canvas>
        </div>
    </div>
</div>

<!-- Second Charts Row -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Network Status Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="network-status-title" class="isp-text-lg isp-font-semibold">Network Status</h2>
            <div class="isp-flex isp-align-center">
                <span class="isp-status-indicator isp-status-online"></span>
                <span class="isp-text-xs isp-skeleton isp-skeleton-stat-small isp-hidden">Loading...</span>
                <span id="network-status-uptime" class="isp-text-xs">98.7% uptime</span>
            </div>
        </div>
        <div class="isp-chart-container">
            <div id="network-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="networkChart" class="isp-hidden"></canvas>
        </div>
    </div>

    <!-- Support Tickets Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="support-tickets-title" class="isp-text-lg isp-font-semibold">Support Tickets</h2>
            <div class="isp-flex isp-space-x-2">
                <button class="isp-button isp-button-day">Open</button>
                <button class="isp-button isp-button-secondary">Closed</button>
                <button class="isp-button isp-button-secondary">All</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <div id="tickets-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="ticketsChart" class="isp-hidden"></canvas>
        </div>
    </div>
</div>

<!-- Third Row with smaller charts -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-md-2 isp-grid-cols-lg-3 isp-gap-6 isp-margin-bottom-6">
    <!-- Hotspot Usage -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="hotspot-usage-title" class="isp-text-lg isp-font-semibold">Hotspot Usage</h2>
            <span class="isp-text-xs isp-text-blue">View All</span>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <div id="hotspot-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="hotspotChart" class="isp-hidden"></canvas>
        </div>
    </div>

    <!-- Captive Portal Logins -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="portal-logins-title" class="isp-text-lg isp-font-semibold">Portal Logins</h2>
            <span class="isp-text-xs isp-text-blue">View All</span>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <div id="portal-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="portalChart" class="isp-hidden"></canvas>
        </div>
    </div>

    <!-- Bandwidth Usage -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">
                Loading...</h2>
            <h2 id="bandwidth-usage-title" class="isp-text-lg isp-font-semibold">Bandwidth Usage</h2>
            <span class="isp-text-xs isp-text-blue">View All</span>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <div id="bandwidth-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="bandwidthChart" class="isp-hidden"></canvas>
        </div>
    </div>
</div>

<!-- Fourth Row with additional ISP-specific charts -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6">
    <!-- Data Consumption by Plan -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="data-consumption-title" class="isp-text-lg isp-font-semibold">Data Consumption by
                Plan</h2>
            <div class="isp-flex isp-space-x-2">
                <button class="isp-button isp-button-day">Day</button>
                <button class="isp-button isp-button-secondary">Week</button>
                <button class="isp-button isp-button-secondary">Month</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <div id="consumption-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="consumptionChart" class="isp-hidden"></canvas>
        </div>
    </div>

    <!-- Network Latency -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="network-latency-title" class="isp-text-lg isp-font-semibold">Network Latency</h2>
            <div class="isp-flex isp-align-center">
                <span class="isp-status-indicator isp-status-online"></span>
                <span class="isp-text-xs isp-skeleton isp-skeleton-stat-small isp-hidden">Loading...</span>
                <span id="network-latency-uptime" class="isp-text-xs">Avg: 28ms</span>
            </div>
        </div>
        <div class="isp-chart-container">
            <div id="latency-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="latencyChart" class="isp-hidden"></canvas>
        </div>
    </div>
</div>

<!-- Fifth Row with additional charts -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-top-6">
    <!-- Peak Usage Hours -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">Loading...</h2>
            <h2 id="peak-usage-title" class="isp-text-lg isp-font-semibold">Peak Usage Hours</h2>
            <span class="isp-text-xs isp-text-blue">Last 7 days</span>
        </div>
        <div class="isp-chart-container">
            <div id="peak-usage-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="peakUsageChart" class="isp-hidden"></canvas>
        </div>
    </div>

    <!-- Equipment Status -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 class="isp-text-lg isp-font-semibold isp-skeleton isp-skeleton-stat isp-hidden">
                Loading...</h2>
            <h2 id="equipment-status-title" class="isp-text-lg isp-font-semibold">Equipment Status</h2>
            <div class="isp-flex isp-align-center isp-space-x-2">
                <span class="isp-flex isp-align-center isp-text-xs"><span
                        class="isp-status-indicator isp-status-online isp-margin-right-1"></span>92%</span>
                <span class="isp-flex isp-align-center isp-text-xs"><span
                        class="isp-status-indicator isp-status-warning isp-margin-right-1"></span>5%</span>
                <span class="isp-flex isp-align-center isp-text-xs"><span
                        class="isp-status-indicator isp-status-offline isp-margin-right-1"></span>3%</span>
            </div>
        </div>
        <div class="isp-chart-container">
            <div id="equipment-chart-skeleton" class="isp-skeleton isp-skeleton-chart"></div>
            <canvas id="equipmentChart" class="isp-hidden"></canvas>
        </div>
    </div>
</div>
{literal}
<script>

    // Initialize charts
    let salesChart, customersChart, networkChart, ticketsChart, hotspotChart, portalChart, bandwidthChart;
    let consumptionChart, latencyChart, peakUsageChart, equipmentChart;

    function updateCharts() {
        const gridColor = getComputedStyle(document.body).getPropertyValue('--chart-grid');
        const textColor = getComputedStyle(document.body).getPropertyValue('--text-primary');

        // Update all charts with new colors
        const charts = [salesChart, customersChart, networkChart, ticketsChart, hotspotChart,
            portalChart, bandwidthChart, consumptionChart, latencyChart,
            peakUsageChart, equipmentChart];

        charts.forEach(chart => {
            if (chart) {
                chart.options.scales = chart.options.scales || {};

                // Update grid colors for all axes
                if (chart.options.scales.x) {
                    chart.options.scales.x.grid = chart.options.scales.x.grid || {};
                    chart.options.scales.x.grid.color = gridColor;
                }

                if (chart.options.scales.y) {
                    chart.options.scales.y.grid = chart.options.scales.y.grid || {};
                    chart.options.scales.y.grid.color = gridColor;
                }

                if (chart.options.scales.r) {
                    chart.options.scales.r.grid = chart.options.scales.r.grid || {};
                    chart.options.scales.r.grid.color = gridColor;
                    chart.options.scales.r.angleLines = chart.options.scales.r.angleLines || {};
                    chart.options.scales.r.angleLines.color = gridColor;
                }

                // Update legend colors
                if (chart.options.plugins && chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                    chart.options.plugins.legend.labels.color = textColor;
                }

                chart.update();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Show skeleton loaders
        document.querySelectorAll('.isp-skeleton').forEach(skel => {
            skel.classList.remove('isp-hidden');
        });
        document.querySelectorAll('.isp-chart-container canvas').forEach(canvas => {
            canvas.classList.add('isp-hidden');
        });
        document.querySelectorAll('#total-customers-content, #active-connections-content, #monthly-revenue-content, #open-tickets-content').forEach(content => {
            content.classList.add('isp-hidden');
        });
        document.querySelectorAll('#sales-report-title, #customer-growth-title, #network-status-title, #support-tickets-title, #hotspot-usage-title, #portal-logins-title, #bandwidth-usage-title, #data-consumption-title, #network-latency-title, #peak-usage-title, #equipment-status-title').forEach(title => {
            title.classList.add('isp-hidden');
        });
        document.querySelectorAll('#network-status-uptime, #network-latency-uptime').forEach(uptime => {
            uptime.classList.add('isp-hidden');
        });

        // Simulate data loading
        setTimeout(() => {
            const gridColor = getComputedStyle(document.body).getPropertyValue('--chart-grid');
            const textColor = getComputedStyle(document.body).getPropertyValue('--text-primary');

            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Revenue',
                        data: [12000, 19000, 15000, 22000, 21000, 25000, 28000],
                        borderColor: '#4299e1',
                        backgroundColor: 'rgba(66, 153, 225, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Customers Chart
            const customersCtx = document.getElementById('customersChart').getContext('2d');
            customersChart = new Chart(customersCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [
                        {
                            label: 'New Customers',
                            data: [120, 190, 150, 220, 210, 250, 280],
                            backgroundColor: '#48bb78',
                            borderRadius: 4
                        },
                        {
                            label: 'Churned Customers',
                            data: [20, 30, 25, 40, 35, 45, 50],
                            backgroundColor: '#f56565',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Network Chart
            const networkCtx = document.getElementById('networkChart').getContext('2d');
            networkChart = new Chart(networkCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Online', 'Offline', 'Maintenance'],
                    datasets: [{
                        data: [92, 5, 3],
                        backgroundColor: [
                            '#48bb78',
                            '#f56565',
                            '#ed8936'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor
                            }
                        }
                    }
                }
            });

            // Tickets Chart
            const ticketsCtx = document.getElementById('ticketsChart').getContext('2d');
            ticketsChart = new Chart(ticketsCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Opened',
                            data: [12, 19, 15, 22, 21, 25, 28],
                            borderColor: '#4299e1',
                            backgroundColor: 'rgba(66, 153, 225, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Closed',
                            data: [8, 15, 12, 18, 17, 20, 22],
                            borderColor: '#48bb78',
                            backgroundColor: 'rgba(72, 187, 120, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Hotspot Chart
            const hotspotCtx = document.getElementById('hotspotChart').getContext('2d');
            hotspotChart = new Chart(hotspotCtx, {
                type: 'bar',
                data: {
                    labels: ['Downtown', 'Airport', 'Mall', 'University', 'Park'],
                    datasets: [{
                        label: 'Connections',
                        data: [350, 420, 280, 510, 390],
                        backgroundColor: [
                            'rgba(66, 153, 225, 0.7)',
                            'rgba(102, 126, 234, 0.7)',
                            'rgba(159, 122, 234, 0.7)',
                            'rgba(237, 137, 54, 0.7)',
                            'rgba(72, 187, 120, 0.7)'
                        ],
                        borderColor: [
                            'rgba(66, 153, 225, 1)',
                            'rgba(102, 126, 234, 1)',
                            'rgba(159, 122, 234, 1)',
                            'rgba(237, 137, 54, 1)',
                            'rgba(72, 187, 120, 1)'
                        ],
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Portal Chart
            const portalCtx = document.getElementById('portalChart').getContext('2d');
            portalChart = new Chart(portalCtx, {
                type: 'radar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Logins',
                        data: [120, 190, 150, 220, 210, 180, 250],
                        backgroundColor: 'rgba(159, 122, 234, 0.2)',
                        borderColor: 'rgba(159, 122, 234, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(159, 122, 234, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                color: gridColor
                            },
                            grid: {
                                color: gridColor
                            },
                            suggestedMin: 0,
                            suggestedMax: 300
                        }
                    }
                }
            });

            // Bandwidth Chart
            const bandwidthCtx = document.getElementById('bandwidthChart').getContext('2d');
            bandwidthChart = new Chart(bandwidthCtx, {
                type: 'polarArea',
                data: {
                    labels: ['HTTP', 'HTTPS', 'FTP', 'Video', 'VoIP'],
                    datasets: [{
                        data: [35, 25, 15, 20, 5],
                        backgroundColor: [
                            'rgba(66, 153, 225, 0.7)',
                            'rgba(102, 126, 234, 0.7)',
                            'rgba(159, 122, 234, 0.7)',
                            'rgba(237, 137, 54, 0.7)',
                            'rgba(72, 187, 120, 0.7)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor
                            }
                        }
                    }
                }
            });

            // Data Consumption Chart
            const consumptionCtx = document.getElementById('consumptionChart').getContext('2d');
            consumptionChart = new Chart(consumptionCtx, {
                type: 'bar',
                data: {
                    labels: ['Basic (10Mbps)', 'Standard (50Mbps)', 'Premium (100Mbps)', 'Business (500Mbps)'],
                    datasets: [
                        {
                            label: 'Download (TB)',
                            data: [12, 45, 28, 62],
                            backgroundColor: 'rgba(66, 153, 225, 0.7)',
                            borderRadius: 4
                        },
                        {
                            label: 'Upload (TB)',
                            data: [4, 15, 9, 22],
                            backgroundColor: 'rgba(102, 126, 234, 0.7)',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Latency Chart
            const latencyCtx = document.getElementById('latencyChart').getContext('2d');
            latencyChart = new Chart(latencyCtx, {
                type: 'line',
                data: {
                    labels: Array.from({ length: 24 }, (_, i) => `${i}:00`),
                    datasets: [{
                        label: 'Latency (ms)',
                        data: [32, 28, 25, 22, 20, 18, 16, 15, 18, 22, 28, 32,
                            35, 38, 42, 45, 48, 50, 45, 40, 38, 35, 32, 30],
                        borderColor: '#ed8936',
                        backgroundColor: 'rgba(237, 137, 54, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 10,
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Peak Usage Chart
            const peakUsageCtx = document.getElementById('peakUsageChart').getContext('2d');
            peakUsageChart = new Chart(peakUsageCtx, {
                type: 'bar',
                data: {
                    labels: ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM'],
                    datasets: [{
                        label: 'Bandwidth Usage (%)',
                        data: [15, 10, 20, 45, 65, 80, 75, 50],
                        backgroundColor: [
                            'rgba(66, 153, 225, 0.7)',
                            'rgba(66, 153, 225, 0.7)',
                            'rgba(66, 153, 225, 0.7)',
                            'rgba(102, 126, 234, 0.7)',
                            'rgba(159, 122, 234, 0.7)',
                            'rgba(237, 137, 54, 0.7)',
                            'rgba(237, 137, 54, 0.7)',
                            'rgba(102, 126, 234, 0.7)'
                        ],
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Equipment Chart
            const equipmentCtx = document.getElementById('equipmentChart').getContext('2d');
            equipmentChart = new Chart(equipmentCtx, {
                type: 'pie',
                data: {
                    labels: ['Routers', 'Switches', 'OLTs', 'ONUs', 'Servers', 'Other'],
                    datasets: [{
                        data: [25, 20, 15, 30, 5, 5],
                        backgroundColor: [
                            'rgba(66, 153, 225, 0.7)',
                            'rgba(102, 126, 234, 0.7)',
                            'rgba(159, 122, 234, 0.7)',
                            'rgba(237, 137, 54, 0.7)',
                            'rgba(72, 187, 120, 0.7)',
                            'rgba(245, 101, 101, 0.7)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor
                            }
                        }
                    }
                }
            });

            // Hide skeleton loaders and show content
            document.querySelectorAll('.isp-skeleton').forEach(skel => {
                skel.classList.add('isp-hidden');
            });
            document.querySelectorAll('.isp-chart-container canvas').forEach(canvas => {
                canvas.classList.remove('isp-hidden');
            });
            document.querySelectorAll('#total-customers-content, #active-connections-content, #monthly-revenue-content, #open-tickets-content').forEach(content => {
                content.classList.remove('isp-hidden');
            });
            document.querySelectorAll('#sales-report-title, #customer-growth-title, #network-status-title, #support-tickets-title, #hotspot-usage-title, #portal-logins-title, #bandwidth-usage-title, #data-consumption-title, #network-latency-title, #peak-usage-title, #equipment-status-title').forEach(title => {
                title.classList.remove('isp-hidden');
            });
            document.querySelectorAll('#network-status-uptime, #network-latency-uptime').forEach(uptime => {
                uptime.classList.remove('isp-hidden');
            });
        }, 2000); // Simulate 2-second loading time
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
                "https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" +
                Math
                    .random(),
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
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal
                                        .resumeTimer)
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