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
        </div>
    </div>

    <div class="col-md-5">
        <div class="panel panel-success panel-hovered mb20 activities">
            <div class="panel-heading">{Lang::T('Payment Gateway')}: {$_c['payment_gateway']}</div>
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

<script>
    window.addEventListener('DOMContentLoaded', function() {
        $.getJSON("./version.json?" + Math.random(), function(data) {
            var localVersion = data.version;
            $('#version').html('Version: ' + localVersion);
            $.getJSON("https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" + Math
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