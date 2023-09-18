{include file="sections/user-header.tpl"}
<!-- user-dashboard -->

<div class="row">
    <div class="col col-md-6 col-md-push-6">
        {if $unpaid }
            <div class="box box-danger box-solid">
                <div class="box-header">
                    <h3 class="box-title">{Lang::T('Unpaid Order')}</h3>
                </div>
                <table class="table table-condensed table-bordered table-striped table-hover">
                    <tbody>
                        <tr>
                            <td>{Lang::T('expired')}</td>
                            <td>{Lang::dateTimeFormat($unpaid['expired_date'])} </td>
                        </tr>
                        <tr>
                            <td>{$_L['Plan_Name']}</td>
                            <td>{$unpaid['plan_name']}</td>
                        </tr>
                        <tr>
                            <td>{$_L['Plan_Price']}</td>
                            <td>{$unpaid['price']}</td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Routers')}</td>
                            <td>{$unpaid['routers']}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="box-footer p-2">
                    <div class="btn-group btn-group-justified mb15">
                        <div class="btn-group">
                            <a href="{$_url}order/view/{$unpaid['id']}/cancel" class="btn btn-danger btn-sm"
                                onclick="return confirm('{Lang::T('Cancel it?')}')">
                                <span class="glyphicon glyphicon-trash"></span>
                                {Lang::T('Cancel')}
                            </a>
                        </div>
                        <div class="btn-group">
                            <a class="btn btn-success btn-block btn-sm" href="{$_url}order/view/{$unpaid['id']}">
                                <span class="icon"><i class="ion ion-card"></i></span>
                                <span>{Lang::T('PAY NOW')}</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        {/if}
        <div class="box box-info box-solid">
            <div class="box-header">
                <h3 class="box-title">{$_L['Announcement']}</h3>
            </div>
            <div class="box-body">
                {include file="$_path/../pages/Announcement.html"}
            </div>
        </div>
    </div>
    <div class="col col-md-6 col-md-pull-6">
        <div class="box box-primary box-solid">
            <div class="box-header">
                <h3 class="box-title">{$_L['Account_Information']}</h3>
            </div>
            <table class="table table-bordered table-striped table-bordered table-hover">
                <tr>
                    <td class="small text-success text-uppercase text-normal">{$_L['Username']}</td>
                    <td class="small mb15">{$_user['username']}</td>
                </tr>
                <tr>
                    <td class="small text-success text-uppercase text-normal">{$_L['Password']}</td>
                    <td class="small mb15"><input type="password" value="{$_user['password']}"
                            style="width:100%; border: 0px;"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                            onclick="this.select()"></td>
                </tr>
                {if $_c['enable_balance'] == 'yes'}
                    <tr>
                        <td class="small text-warning text-uppercase text-normal">{Lang::T('Balance')}</td>
                        <td class="small mb15 text-bold">
                            {Lang::moneyFormat($_user['balance'])}
                            {if $_user['auto_renewal'] == 1}
                                <a class="label label-success pull-right" href="{$_url}home&renewal=0"
                                    onclick="return confirm('{Lang::T('Disable auto renewal?')}')">{Lang::T('Auto Renewal On')}</a>
                            {else}
                                <a class="label label-danger pull-right" href="{$_url}home&renewal=1"
                                    onclick="return confirm('{Lang::T('Enable auto renewal?')}')">{Lang::T('Auto Renewal Off')}</a>
                            {/if}
                        </td>
                    </tr>
                {/if}
                <tr>
                    <td class="small text-primary text-uppercase text-normal">{$_L['Plan_Name']}</td>
                    <td class="small mb15">{$_bill['namebp']}</td>
                </tr>
                <tr>
                    <td class="small text-info text-uppercase text-normal">{$_L['Created_On']}</td>
                    <td class="small mb15">
                        {if $_bill['time'] ne ''}{Lang::dateAndTimeFormat($_bill['recharged_on'],$_bill['recharged_time'])}
                        {/if}&nbsp;</td>
                </tr>
                <tr>
                    <td class="small text-danger text-uppercase text-normal">{$_L['Expires_On']}</td>
                    <td class="small mb15">
                        {if $_bill['time'] ne ''}{Lang::dateAndTimeFormat($_bill['expiration'],$_bill['time'])}{/if}&nbsp;
                    </td>
                </tr>
                {if $nux_ip}
                    <tr>
                        <td class="small text-primary text-uppercase text-normal">{Lang::T('Current IP')}</td>
                        <td class="small mb15">{$nux_ip}</td>
                    </tr>
                {/if}
                {if $nux_mac}
                    <tr>
                        <td class="small text-primary text-uppercase text-normal">{Lang::T('Current MAC')}</td>
                        <td class="small mb15">{$nux_mac}</td>
                    </tr>
                {/if}
                {if $_bill['type'] == 'Hotspot' && $_bill['status'] == 'on'}
                    <tr>
                        <td class="small text-primary text-uppercase text-normal">{Lang::T('Login Status')}</td>
                        <td class="small mb15" id="login_status">
                            Loading....
                        </td>
                    </tr>
                {/if}
            </table>
            {if $_c['disable_voucher'] == 'yes'}
                <div class="box-footer">
                    {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                        <a href="{$_url}order/package" class="btn btn-primary btn-block">
                            <i class="ion ion-ios-cart"></i>
                            {Lang::T('Order Package')}
                        </a>
                    {/if}
                </div>
            {/if}
        </div>
        {if $_bill['type'] == 'Hotspot' && $_bill['status'] == 'on'}
            <script>
                setTimeout(() => {
                    $.ajax({
                        url: "index.php?_route=autoload_user/isLogin",
                        cache: false,
                        success: function(msg) {
                            $("#login_status").html(msg);
                        }
                    });
                }, 2000);
            </script>
        {/if}
        {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes'}
            <div class="box box-primary box-solid mb30">
                <div class="box-header">
                    <h4 class="box-title">{Lang::T("Transfer Balance")}</h4>
                </div>
                <div class="box-body p-0">
                    <form method="post" onsubmit="return askConfirm()" role="form" action="{$_url}home">
                        <div class="form-group">
                            <div class="col-sm-5">
                                <input type="text" id="username" name="username" class="form-control" required
                                    placeholder="{$_L['Username']}">
                            </div>
                            <div class="col-sm-5">
                                <input type="number" id="balance" name="balance" autocomplete="off" class="form-control"
                                    required placeholder="{$_L['Balance']}">
                            </div>
                            <div class="form-group col-sm-2" align="center">
                                <button class="btn btn-success btn-block" id="sendBtn" type="submit" name="send"
                                    onclick="return confirm('{Lang::T("Are You Sure?")}')" value="balance"><i
                                        class="glyphicon glyphicon-send"></i></button>
                            </div>
                        </div>
                    </form>
                    <script>
                        function askConfirm() {
                            if(confirm('{Lang::T('Send your balance?')}')){
                            setTimeout(() => {
                                document.getElementById('sendBtn').setAttribute('disabled', '');
                            }, 1000);
                            return true;
                        }
                        return false;
                        }
                    </script>
                </div>
                <div class="box-header">
                    <h4 class="box-title">{Lang::T("Recharge a friend")}</h4>
                </div>
                <div class="box-body p-0">
                    <form method="post" role="form" action="{$_url}home">
                        <div class="form-group">
                            <div class="col-sm-10">
                                <input type="text" id="username" name="username" class="form-control" required
                                    placeholder="{$_L['Username']}">
                            </div>
                            <div class="form-group col-sm-2" align="center">
                                <button class="btn btn-success btn-block" id="sendBtn" type="submit" name="send"
                                    onclick="return confirm('{Lang::T("Are You Sure?")}')" value="plan"><i
                                        class="glyphicon glyphicon-send"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        {/if}
        <br>
        {if $_c['disable_voucher'] != 'yes'}
            <div class="box box-primary box-solid mb30">
                <div class="box-header">
                    <h3 class="box-title">{$_L['Voucher_Activation']}</h3>
                </div>
                <div class="box-body">
                    <form method="post" role="form" class="form-horizontal" action="{$_url}voucher/activation-post">
                        <div class="form-group">
                            <label class="col-sm-4 control-label text-center">{$_L['Code_Voucher']}</label>
                            <div class="col-sm-7">
                                <input type="text" id="code" name="code" class="form-control"
                                    placeholder="{$_L['Enter_Voucher_Code']}">
                            </div>
                        </div>
                        <div class="form-group" align="center">
                            <button class="btn btn-success" type="submit">{$_L['Recharge']}</button>
                        </div>
                    </form>
                </div>
                <div class="box-body">
                    <div class="btn-group btn-group-justified" role="group">
                        <a class="btn btn-warning" href="{$_url}voucher/activation">
                            <i class="ion ion-ios-cart"></i>
                            {$_L['Order_Voucher']}
                        </a>
                        {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                            <a href="{$_url}order/package" class="btn btn-primary">
                                <i class="ion ion-ios-cart"></i>
                                {Lang::T('Order Package')}
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        {/if}
    </div>
</div>
{include file="sections/user-footer.tpl"}