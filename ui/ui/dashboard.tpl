{include file="sections/header.tpl"}


<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h4><sup>{$_c['currency_code']}</sup>
                    {number_format($iday,0,$_c['dec_point'],$_c['thousands_sep'])}</h4>
                <p>{$_L['Income_Today']}</p>
            </div>
            <div class="icon">
                <i class="ion ion-bag"></i>
            </div>
            <a href="{$_url}reports/by-date" class="small-box-footer">{$_L['View_Reports']} <i
                    class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h4><sup>{$_c['currency_code']}</sup>
                    {number_format($imonth,0,$_c['dec_point'],$_c['thousands_sep'])}</h4>

                <p>{$_L['Income_This_Month']}</p>
            </div>
            <div class="icon">
                <i class="ion ion-stats-bars"></i>
            </div>
            <a href="{$_url}reports/by-period" class="small-box-footer">{$_L['View_Reports']} <i
                    class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h4>{$u_act}/{$u_all}</h4>

                <p>{$_L['Users_Active']}</p>
            </div>
            <div class="icon">
                <i class="ion ion-person"></i>
            </div>
            <a href="{$_url}prepaid/list" class="small-box-footer">{$_L['View_All']} <i
                    class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h4>{$c_all}</h4>

                <p>{$_L['Total_Users']}</p>
            </div>
            <div class="icon">
                <i class="fa fa-users"></i>
            </div>
            <a href="{$_url}customers/list" class="small-box-footer">{$_L['View_All']} <i
                    class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
<!-- solid sales graph -->
<div class="box box-solid ">
    <div class="box-header">
        <i class="fa fa-th"></i>

        <h3 class="box-title">{Lang::T('Monthly Registered Customers')}</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
            <button type="button" class="btn bg-teal btn-sm" data-widget="remove"><i class="fa fa-times"></i>
            </button>
        </div>
    </div>
    <div class="box-body border-radius-none">
        <canvas class="chart" id="chart" style="height: 250px;"></canvas>
    </div>
</div>
<!-- solid sales graph -->
<div class="box box-solid ">
    <div class="box-header">
        <i class="fa fa-inbox"></i>

        <h3 class="box-title">{Lang::T('Total Monthly Sales')}</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
            <button type="button" class="btn bg-teal btn-sm" data-widget="remove"><i class="fa fa-times"></i>
            </button>
        </div>
    </div>
    <div class="box-body border-radius-none">
        <canvas class="chart" id="salesChart" style="height: 250px;"></canvas>
    </div>
</div>
<div class="row">
    <div class="col-md-7">
        {if $_c['disable_voucher'] != 'yes' && $stocks['unused']>0 || $stocks['used']>0}
            <div class="panel panel-primary mb20 panel-hovered project-stats table-responsive">
                <div class="panel-heading">Vouchers Stock</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{$_L['Plan_Name']}</th>
                                <th>unused</th>
                                <th>used</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $plans as $stok}
                                <tr>
                                    <td>{$stok['name_plan']}</td>
                                    <td>{$stok['unused']}</td>
                                    <td>{$stok['used']}</td>
                                </tr>
                            </tbody>
                        {/foreach}
                        <tr>
                            <td>Total</td>
                            <td>{$stocks['unused']}</td>
                            <td>{$stocks['used']}</td>
                        </tr>
                    </table>
                </div>
            </div>
        {/if}
        <div class="panel panel-warning mb20 panel-hovered project-stats table-responsive">
            <div class="panel-heading">{$_L['User_Expired_Today']}</div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>{$_L['Username']}</th>
                            <th>{$_L['Created_On']}</th>
                            <th>{$_L['Expires_On']}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$no = 1}
                        {foreach $expire as $expired}
                            <tr>
                                <td>{$no++}</td>
                                <td><a href="{$_url}customers/viewu/{$expired['username']}">{$expired['username']}</a></td>
                                <td>{Lang::dateAndTimeFormat($expired['recharged_on'],$expired['recharged_time'])}
                                </td>
                                <td>{Lang::dateAndTimeFormat($expired['expiration'],$expired['time'])}
                                </td>
                            </tr>
                        </tbody>
                    {/foreach}
                </table>
            </div>
            &nbsp; {$paginator['contents']}
        </div>
    </div>


    <div class="col-md-5">
        <div class="panel panel-success panel-hovered mb20 activities">
            <div class="panel-heading">{Lang::T('Payment Gateway')}: {$_c['payment_gateway']}</div>
        </div>
        <div class="panel panel-info panel-hovered mb20 activities">
            <div class="panel-heading">{Lang::T('All Users Insights')}</div>
            <div class="panel-body">
                <canvas id="userRechargesChart"></canvas>
            </div>
        </div>
        <div class="panel panel-info panel-hovered mb20 activities">
            <div class="panel-heading"><a href="{$_url}logs">{$_L['Activity_Log']}</a></div>
            <div class="panel-body">
                <ul class="list-unstyled">
                    {foreach $dlog as $dlogs}
                        <li class="primary">
                            <span class="point"></span>
                            <span class="time small text-muted">{time_elapsed_string($dlogs['date'],true)}</span>
                            <p>{$dlogs['description']}</p>
                        </li>
                    {/foreach}
                </ul>
            </div>
        </div>
    </div>


</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
{literal}
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var counts = JSON.parse('{/literal}{$counts|json_encode}{literal}');

            var monthNames = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];

            var labels = [];
            var data = [];

            for (var i = 1; i <= 12; i++) {
                var month = counts.find(count => count.date === i);
                labels.push(month ? monthNames[i - 1] : monthNames[i - 1].substring(0, 3));
                data.push(month ? month.count : 0);
            }

            var ctx = document.getElementById('chart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Registered Members',
                        data: data,
                        backgroundColor: 'rgba(0, 0, 255, 0.5)',
                        borderColor: 'rgba(0, 0, 255, 0.7)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
        });
    </script>

    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var monthlySales = JSON.parse('{/literal}{$monthlySales|json_encode}{literal}');

            var monthNames = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];

            var labels = [];
            var data = [];

            for (var i = 1; i <= 12; i++) {
                var month = findMonthData(monthlySales, i);
                labels.push(month ? monthNames[i - 1] : monthNames[i - 1].substring(0, 3));
                data.push(month ? month.totalSales : 0);
            }

            var ctx = document.getElementById('salesChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Monthly Sales',
                        data: data,
                        backgroundColor: 'rgba(2, 10, 242)', // Customize the background color
                        borderColor: 'rgba(255, 99, 132, 1)', // Customize the border color
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
        });

        function findMonthData(monthlySales, month) {
            for (var i = 0; i < monthlySales.length; i++) {
                if (monthlySales[i].month === month) {
                    return monthlySales[i];
                }
            }
            return null;
        }


        document.addEventListener("DOMContentLoaded", function() {
            // Get the data from PHP and assign it to JavaScript variables
            var u_act = '{/literal}{$u_act}{literal}';
            var c_all = '{/literal}{$c_all}{literal}';
            var u_all = '{/literal}{$u_all}{literal}';
            //lets calculate the inactive users as reported
            var expired = u_all - u_act;
            var inactive = c_all - u_all;
            // Create the chart data
            var data = {
                labels: ['Active Users', 'Expired Users', 'Inactive Users'],
                datasets: [{
                    label: 'User Recharges',
                    data: [parseInt(u_act), parseInt(expired), parseInt(inactive)],
                    backgroundColor: ['rgba(4, 191, 13)', 'rgba(191, 35, 4)', 'rgba(0, 0, 255, 0.5'],
                    borderColor: ['rgba(0, 255, 0, 1)', 'rgba(255, 99, 132, 1)', 'rgba(0, 0, 255, 0.7'],
                    borderWidth: 1
                }]
            };

            // Create chart options
            var options = {
                responsive: true,
                aspectRatio: 1,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 15
                        }
                    }
                }
            };

            // Get the canvas element and create the chart
            var ctx = document.getElementById('userRechargesChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'pie',
                data: data,
                options: options
            });
        });
    </script>
{/literal}
<script>
    window.addEventListener('DOMContentLoaded', function() {
        $.getJSON("./version.json?" + Math.random(), function(data) {
            var localVersion = data.version;
            $('#version').html('Version: ' + localVersion);
            $.getJSON(
                "https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" +
                Math
                .random(),
                function(data) {
                    var latestVersion = data.version;
                    if (localVersion !== latestVersion) {
                        $('#version').html('Latest Version: ' + latestVersion);
                    }
                });
        });

    });
</script>

{include file="sections/footer.tpl"}