<div class="box box-solid ">
    <div class="box-header">
        <i class="fa fa-th"></i>

        <h3 class="box-title">{Lang::T('Monthly Registered Customers')}</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
            <a href="{Text::url('dashboard&refresh')}" class="btn bg-teal btn-sm"><i class="fa fa-refresh"></i>
            </a>
        </div>
    </div>
    <div class="box-body border-radius-none">
        <canvas class="chart" id="chart" style="height: 150px;"></canvas>
    </div>
</div>

<script type="text/javascript">
    {literal}
        document.addEventListener("DOMContentLoaded", function() {
            var counts = JSON.parse('{/literal}{$monthlyRegistered|json_encode}{literal}');

            var monthNames = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];

            var labels = [];
            var data = [];

            for (var i = 1; i <= 12; i++) {
                var month = counts.find(count => count.date === i);
                labels.push(month ? monthNames[i - 1] : monthNames[i - 1]);
                data.push(month ? month.count : 0);
            }

            var ctx = document.getElementById('chart').getContext('2d');

            // Create gradient for bars (light mode)
            var gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, '#4e73df');
            gradient.addColorStop(1, '#1cc88a');

            // Create gradient for bars (dark mode)
            var darkGradient = ctx.createLinearGradient(0, 0, 0, 200);
            darkGradient.addColorStop(0, '#5a7bff');
            darkGradient.addColorStop(1, '#2bc4a0');

            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Registered Members',
                        data: data,
                        backgroundColor: document.body.classList.contains('dark-mode') ? darkGradient : gradient,
                        borderColor: document.body.classList.contains('dark-mode') ? '#5a7bff' : '#4e73df',
                        borderWidth: 1,
                        hoverBackgroundColor: document.body.classList.contains('dark-mode') ? '#3b50b3' : '#2e59d9',
                        hoverBorderColor: document.body.classList.contains('dark-mode') ? '#3b50b3' : '#2e59d9'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                font: {
                                    family: "'Roboto', sans-serif",
                                    size: 14
                                },
                                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333'
                            }
                        },
                        tooltip: {
                            backgroundColor: document.body.classList.contains('dark-mode') ? 'rgba(0, 0, 0, 0.9)' : 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                family: "'Roboto', sans-serif",
                                size: 14
                            },
                            bodyFont: {
                                family: "'Roboto', sans-serif",
                                size: 12
                            },
                            padding: 10,
                            cornerRadius: 5
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Roboto', sans-serif",
                                    size: 12
                                },
                                color: document.body.classList.contains('dark-mode') ? '#b0b0b0' : '#555'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                font: {
                                    family: "'Roboto', sans-serif",
                                    size: 12
                                },
                                color: document.body.classList.contains('dark-mode') ? '#b0b0b0' : '#555'
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });

            // Update chart colors when dark mode is toggled
            const observer = new MutationObserver(() => {
                chart.data.datasets[0].backgroundColor = document.body.classList.contains('dark-mode') ? darkGradient : gradient;
                chart.data.datasets[0].borderColor = document.body.classList.contains('dark-mode') ? '#5a7bff' : '#4e73df';
                chart.data.datasets[0].hoverBackgroundColor = document.body.classList.contains('dark-mode') ? '#3b50b3' : '#2e59d9';
                chart.data.datasets[0].hoverBorderColor = document.body.classList.contains('dark-mode') ? '#3b50b3' : '#2e59d9';
                chart.options.plugins.legend.labels.color = document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333';
                chart.options.plugins.tooltip.backgroundColor = document.body.classList.contains('dark-mode') ? 'rgba(0, 0, 0, 0.9)' : 'rgba(0, 0, 0, 0.8)';
                chart.options.scales.x.ticks.color = document.body.classList.contains('dark-mode') ? '#b0b0b0' : '#555';
                chart.options.scales.y.ticks.color = document.body.classList.contains('dark-mode') ? '#b0b0b0' : '#555';
                chart.options.scales.y.grid.color = document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
                chart.update();
            });
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        });
    {/literal}
</script>