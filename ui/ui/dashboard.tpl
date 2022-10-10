{include file="sections/header.tpl"}

{if ($_admin['user_type']) eq 'Admin' || ($_admin['user_type']) eq 'Sales'}
    <div class="row hidden">
        <div class="col-md-12">
            <div class="dash-head clearfix mt15 mb20">
                <div class="left">
                    <h4 class="mb5 text-light">Dashboard</h4>
                    <p class="small"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-default mb20 mini-box panel-hovered">
                <div class="panel-body">
                    <div class="clearfix">
                        <div class="info left">
                            <h4 class="mt0 text-primary text-bold">{$_c['currency_code']}
                                {number_format($iday,0,$_c['dec_point'],$_c['thousands_sep'])}</h4>
                            <h5 class="text-light mb0">{$_L['Income_Today']}</h5>
                        </div>
                        <div class="right ion ion-ios-pricetags-outline icon"></div>
                    </div>
                </div>
                <div class="panel-footer clearfix panel-footer-sm panel-footer-primary">
                    <p class="mt0 mb0 right"><a class="text-putih" href="{$_url}reports/by-date">{$_L['View_Reports']}</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="panel panel-default mb20 mini-box panel-hovered">
                <div class="panel-body">
                    <div class="clearfix">
                        <div class="info left">
                            <h4 class="mt0 text-success text-bold">{$_c['currency_code']}
                                {number_format($imonth,0,$_c['dec_point'],$_c['thousands_sep'])}</h4>
                            <h5 class="text-light mb0">{$_L['Income_This_Month']}</h5>
                        </div>
                        <div class="right ion ion-social-usd icon"></div>
                    </div>
                </div>
                <div class="panel-footer clearfix panel-footer-sm panel-footer-success">
                    <p class="mt0 mb0 right"><a class="text-putih" href="{$_url}reports/by-period">{$_L['View_Reports']}</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="panel panel-default mb20 mini-box panel-hovered">
                <div class="panel-body">
                    <div class="clearfix">
                        <div class="info left">
                            <h4 class="mt0 text-info text-bold">{$u_act}</h4>
                            <h5 class="text-light mb0">{$_L['Users_Active']}</h5>
                        </div>
                        <div class="right ion ion-android-contact icon"></div>
                    </div>
                </div>
                <div class="panel-footer clearfix panel-footer-sm panel-footer-info">
                    <p class="mt0 mb0 right"><a class="text-putih" href="{$_url}prepaid/list">{$_L['View_All']}</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="panel panel-default mb20 mini-box panel-hovered">
                <div class="panel-body">
                    <div class="clearfix">
                        <div class="info left">
                            <h4 class="mt0 text-pink text-bold">{$u_all}</h4>
                            <h5 class="text-light mb0">{$_L['Total_Users']}</h5>
                        </div>
                        <div class="right ion ion-android-contacts icon"></div>
                    </div>
                </div>
                <div class="panel-footer clearfix panel-footer-sm panel-footer-pink">
                    <p class="mt0 mb0 right"><a class="text-putih" href="{$_url}customers/list">{$_L['View_All']}</a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-default mb20 panel-hovered project-stats table-responsive">
                <div class="panel-heading">Vouchers Stock</div>
                <div class="panel-body">
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
            </div>
            <div class="panel panel-default mb20 panel-hovered project-stats table-responsive">
                <div class="panel-heading">{$_L['User_Expired_Today']}</div>
                <div class="panel-body">
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
                                        <td>{$expired['username']}</td>
                                        <td>{date($_c['date_format'], strtotime($expired['recharged_on']))} {$expired['time']}
                                        </td>
                                        <td>{date($_c['date_format'], strtotime($expired['expiration']))} {$expired['time']}
                                        </td>
                                    </tr>
                                </tbody>
                            {/foreach}
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="panel panel-default panel-hovered mb20 activities">
                <div class="panel-heading">{Lang::T('Payment Gateway')}: {$_c['payment_gateway']}</div>
            </div>
            <div class="panel panel-default panel-hovered mb20 activities">
                <div class="panel-heading">{$_L['Activity_Log']}</div>
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
                <div class="panel-heading" onclick="location.href = '?_route=community#latestVersion';" id="version">Version: </div>
            </div>
        </div>

    </div>
{else}
    <div class="row">
        <div class="col-md-12">
            <div class="dash-head clearfix mt15 mb20">
                <div class="left">
                    <h4 class="mb5 text-light">{$_L['Welcome']}, {$_user['fullname']}</h4>
                    <p>{$_L['Welcome_Text_User']}</p>
                    <ul>
                        <li> {$_L['Account_Information']}</li>
                        <li> <a href="{$_url}voucher/activation">{$_L['Voucher_Activation']}</a></li>
                        <li> <a href="{$_url}voucher/list-activated">{$_L['List_Activated_Voucher']}</a></li>
                        <li> <a href="{$_url}accounts/change-password">{$_L['Change_Password']}</a></li>
                        <li> {$_L['Order_Voucher']}</li>
                        <li> {$_L['Private_Message']}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="panel mb20 panel-primary panel-hovered">
                <div class="panel-heading">{$_L['Account_Information']}</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="small text-success text-uppercase text-normal">{$_L['Username']}</p>
                            <p class="small mb15">{$_bill['username']}</p>
                        </div>
                        <div class="col-sm-3">
                            <p class="small text-primary text-uppercase text-normal">{$_L['Plan_Name']}</p>
                            <p class="small mb15">{$_bill['namebp']}</p>
                        </div>
                        <div class="col-sm-3">
                            <p class="small text-info text-uppercase text-normal">{$_L['Created_On']}</p>
                            <p class="small mb15">{date($_c['date_format'], strtotime($_bill['recharged_on']))}
                                {$_bill['time']}</p>
                        </div>
                        <div class="col-sm-3">
                            <p class="small text-danger text-uppercase text-normal">{$_L['Expires_On']}</p>
                            <p class="small mb15">{date($_c['date_format'], strtotime($_bill['expiration']))}
                                {$_bill['time']}</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
{/if}

<script>
    window.addEventListener('DOMContentLoaded', function() {
        $.getJSON( "./version.json?"+Math.random(), function( data ) {
            var localVersion = data.version;
            $('#version').html('Version: '+localVersion);
            $.getJSON( "https://raw.githubusercontent.com/ibnux/phpmixbill/master/version.json?"+Math.random(), function( data ) {
                var latestVersion = data.version;
                if(localVersion !== latestVersion){
                    $('#version').html('Latest Version: '+latestVersion);
                }
            });
        });

    });
</script>

{include file="sections/footer.tpl"}