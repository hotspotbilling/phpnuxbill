{include file="sections/header.tpl"}
<!-- reports-daily -->
<div class="row">
    <div class="col-lg-3">
        <form method="get" class="form">
            <div class="box box-primary box-solid">
                <div class="box-header" onclick="showFilter()" style=" cursor: pointer;">
                    <h3 class="box-title">{Lang::T('Filter')}</h3>
                </div>
                <div id="filter_box" class="box-body hidden-xs hidden-sm hidden-md">
                    <center>
                        <label>
                            <input type="checkbox" id="show_chart" onclick="return setShowChart()">
                            {Lang::T('Show chart')}
                        </label>
                    </center>
                    <hr style="margin: 1px;">
                    <input type="hidden" name="_route" value="reports">
                    <label>{Lang::T('Start Date')}</label>
                    <input type="date" class="form-control" name="sd" value="{$sd}">
                    <label>{Lang::T('Start time')}</label>
                    <input type="time" class="form-control" name="ts" value="{$ts}">
                    <label>{Lang::T('End Date')}</label>
                    <input type="date" class="form-control" name="ed" value="{$ed}">
                    <label>{Lang::T('End Time')}</label>
                    <input type="time" class="form-control" name="te" value="{$te}">
                    <label>{Lang::T('Type')}</label>
                    <select class="form-control select2" name="tps[]" multiple>
                        {foreach $types as $type}
                            <option value="{$type}" {if in_array($type, $tps)}selected{/if}>{$type}</option>
                        {/foreach}
                    </select>
                    <label>{Lang::T('Internet Plans')}</label>
                    <select class="form-control select2" name="plns[]" multiple>
                        {foreach $plans as $plan}
                            <option value="{$plan}" {if in_array($plan, $plns)}selected{/if}>{$plan}</option>
                        {/foreach}
                    </select>
                    <label>{Lang::T('Methods')}</label>
                    <select class="form-control select2" name="mts[]" multiple>
                        {foreach $methods as $method}
                            <option value="{$method}" {if in_array($method, $mts)}selected{/if}>{$method}</option>
                        {/foreach}
                    </select>
                    <label>{Lang::T('Routers')}</label>
                    <select class="form-control select2" name="rts[]" multiple>
                        {foreach $routers as $router}
                            <option value="{$router}" {if in_array($router, $rts)}selected{/if}>{Lang::T($router)}</option>
                        {/foreach}
                    </select>
                    <input type="submit" class="btn btn-success btn-block">
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-9">
        <span id="chart_area" class="hidden">
            <div class="box box-primary box-solid">
                <div class="box-body row">
                    <div class="col-md-3 col-xs-6">
                        <canvas id="cart_type"></canvas>
                    </div>
                    <div class="col-md-3 col-xs-6">
                        <canvas id="cart_plan"></canvas>
                    </div>
                    <div class="col-md-3 col-xs-6">
                        <canvas id="cart_method"></canvas>
                    </div>
                    <div class="col-md-3 col-xs-6">
                        <canvas id="cart_router"></canvas>
                    </div>
                </div>
            </div>
            <div class="box box-primary box-solid">
                <div class="box-header">
                    <h3 class="box-title">{Lang::dateFormat($sd)} - {Lang::dateFormat($ed)}
                        <sup>{Lang::T('Max 30 days')}</sup>
                    </h3>
                </div>
                <div class="box-body row" style="height: 300px;">
                    <canvas id="line_cart"></canvas>
                </div>
            </div>
        </span>
        <div class="ls">
        <div class="ls-card">
            <div class="ls-bar">
                <a href="{Text::url('export/print-by-date&')}{$filter}" class="ls-btn" target="_blank" title="{Lang::T('Print')}"><i class="fa fa-print" aria-hidden="true"></i> {Lang::T('Print')}</a>
                <a href="{Text::url('export/pdf-by-date&')}{$filter}" class="ls-btn" title="PDF"><i class="fa fa-file-pdf-o" aria-hidden="true"></i> PDF</a>
            </div>
            <div class="ls-scroll">
                <table class="ls-table">
                    <thead>
                        <tr>
                            <th>{Lang::T('Username')}</th>
                            <th>{Lang::T('Fullname')}</th>
                            <th>{Lang::T('Type')}</th>
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
                            <td data-label="{Lang::T('Username')}"><b class="ls-strong">{$ds['username']|escape}</b></td>
                            <td data-label="{Lang::T('Fullname')}">{$ds['fullname']|escape}</td>
                            <td data-label="{Lang::T('Type')}"><em class="ls-chip">{$ds['type']|escape}</em></td>
                            <td data-label="{Lang::T('Plan Name')}">{$ds['plan_name']|escape}</td>
                            <td data-label="{Lang::T('Plan Price')}" class="ls-num">{Lang::moneyFormat($ds['price'])}</td>
                            <td data-label="{Lang::T('Created On')}" class="ls-when text-success">{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                            <td data-label="{Lang::T('Expires On')}" class="ls-when text-danger">{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                            <td data-label="{Lang::T('Method')}" class="ls-dim">{$ds['method']|escape}</td>
                            <td data-label="{Lang::T('Routers')}" class="ls-dim">{$ds['routers']|escape}</td>
                        </tr>
                        {foreachelse}
                        <tr class="ls-none"><td colspan="9"><div class="ls-empty"><b>{Lang::T('No transactions found')}</b></div></td></tr>
                        {/foreach}
                        <tr>
                            <th>{Lang::T('Total')}</th>
                            <th class="ls-num" colspan="4">{Lang::moneyFormat($dr)}</th>
                            <td colspan="4"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-center small text-info" style="margin: 0; padding: 10px;">{Lang::T('All Transactions at Date')}:
                {Lang::dateAndTimeFormat($sd, $ts)} - {Lang::dateAndTimeFormat($ed, $te)}</p>
        </div>
        </div>
    </div>
</div>

{include file="pagination.tpl"}

<div class="bs-callout bs-callout-warning bg-gray">
    <h4>{Lang::T('Information')}</h4>
    <p>{Lang::T('Export and Print will show all data without pagination')}.</p>
</div>

<script src="{$app_url}/ui/ui/scripts/chart.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/chartjs-plugin-autocolors.min.js"></script>

{literal}
    <script>
        var isShow = false;

        function showFilter() {
            if (isShow) {
                $("#filter_box").addClass("hidden-xs");
                $("#filter_box").addClass("hidden-sm");
                $("#filter_box").addClass("hidden-md");
                isShow = false;
            } else {
                // remove class
                $("#filter_box").removeClass("hidden-xs");
                $("#filter_box").removeClass("hidden-sm");
                $("#filter_box").removeClass("hidden-md");
                isShow = true;
            }
        }
        document.addEventListener("DOMContentLoaded", function() {
        const autocolors = window['chartjs-plugin-autocolors'];
        Chart.register(autocolors);
        var options = {
        responsive: true,
        aspectRatio: 1,
        plugins: {
            autocolors: {
                mode: 'data'
            },
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 15
                }
            }
        }
        };

        function create_cart(field, labels, datas, options) {
        new Chart(document.getElementById(field), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: datas,
                    borderWidth: 1
                }]
            },
            options: options
        });
        }

        function showChart() {
        // get cart one by one
        $.getJSON("{/literal}{Text::url('reports/ajax/type&', $filter)}{literal}", function( data ) {
        create_cart('cart_type', data.labels, data.datas, options);
        $.getJSON("{/literal}{Text::url('reports/ajax/plan&', $filter)}{literal}", function( data ) {
        create_cart('cart_plan', data.labels, data.datas, options);
        $.getJSON("{/literal}{Text::url('reports/ajax/method&', $filter)}{literal}", function( data ) {
        create_cart('cart_method', data.labels, data.datas, options);
        $.getJSON("{/literal}{Text::url('reports/ajax/router&', $filter)}{literal}", function( data ) {
        create_cart('cart_router', data.labels, data.datas, options);
        getLineChartData();
        });
        });
        });
        });
        }

        if (getCookie('show_report_graph') != 'hide') {
            $("#chart_area").removeClass("hidden");
            document.getElementById('show_chart').checked = true;
            showChart();
        }

        });

        function setShowChart() {
            if (document.getElementById('show_chart').checked) {
                setCookie('show_report_graph', 'show', 30);
            } else {
                setCookie('show_report_graph', 'hide', 30);
            }
            location.reload();
        }

        function getLineChartData() {
            $.getJSON("{/literal}{Text::url('reports/ajax/line&', $filter)}{literal}", function( data ) {
            var linechart = new Chart(document.getElementById('line_cart'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datas,
                },
                options: {
                    maintainAspectRatio: false,
                    aspectRatio: 1,
                    plugins: {
                        autocolors: {
                            mode: 'data'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
        }
        // [{
        //     label: 'a',
        //     data: [8, 3, 9, 2, 7, 4, 2]
        // }, {
        //     label: 'b',
        //     data: [6, 4, 5, 5, 9, 6, 3]
        // }, {
        //     label: 'c',
        //     data: [5, 2, 3, 6, 4, 8, 6]
        // }]
    </script>
{/literal}

{include file="sections/footer.tpl"}