{include file="sections/user-header.tpl"}
<!-- user-dashboard -->

<div class="row">
    <div class="col col-md-6 col-md-push-6">
        {if $unpaid }
            <div class="box box-danger box-solid">
                <div class="box-header">
                    <h3 class="box-title">{Lang::T('Unpaid Order')}</h3>
                </div>
                <table class="table table-condensed table-bordered table-striped table-hover" style="margin-bottom: 0px;">
                    <tbody>
                        <tr>
                            <td>{Lang::T('expired')}</td>
                            <td>{Lang::dateTimeFormat($unpaid['expired_date'])} </td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Plan Name')}</td>
                            <td>{$unpaid['plan_name']}</td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Plan Price')}</td>
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
                <h3 class="box-title">{Lang::T('Announcement')}</h3>
            </div>
            <div class="box-body">
                {$Announcement_Customer = "{$PAGES_PATH}/Announcement_Customer.html"}
                {if file_exists($Announcement_Customer)}
                    {include file=$Announcement_Customer}
                {/if}
            </div>
        </div>
    </div>
    <div class="col col-md-6 col-md-pull-6">
        <div class="box box-primary box-solid">
            <div class="box-header">
                <h3 class="box-title">{Lang::T('Your Account Information')}</h3>
            </div>
            <table class="table table-bordered table-striped table-bordered table-hover mb-0"
                style="margin-bottom: 0px;">
                <tr>
                    <td class="small text-success text-uppercase text-normal">{Lang::T('Username')}</td>
                    <td class="small mb15">{$_user['username']}</td>
                </tr>
                <tr>
                    <td class="small text-success text-uppercase text-normal">{Lang::T('Password')}</td>
                    <td class="small mb15"><input type="password" value="{$_user['password']}"
                            style="width:100%; border: 0px;" onmouseleave="this.type = 'password'"
                            onmouseenter="this.type = 'text'" onclick="this.select()"></td>
                </tr>
                <tr>
                    <td class="small text-success text-uppercase text-normal">{Lang::T('Service Type')}</td>
                    <td class="small mb15">
                        {if $_user.service_type == 'Hotspot'}
                            Hotspot
                        {elseif $_user.service_type == 'PPPoE'}
                            PPPoE
                        {elseif $_user.service_type == 'Others' || $_user.service_type == null}
                            Others
                        {/if}
                    </td>
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
            </table>
        </div>
        {if $_bills}
            <div class="box box-primary box-solid">
                {foreach $_bills as $_bill}
                    {if $_bill['routers'] != 'radius'}
                        <div class="box-header">
                            <h3 class="box-title">{$_bill['routers']}</h3>
                            <div class="btn-group pull-right">
                                {if $_bill['type'] == 'Hotspot'}
                                    {if $_c['hotspot_plan']==''}Hotspot Plan{else}{$_c['hotspot_plan']}{/if}
                                {else}
                                    {if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}
                                {/if}
                            </div>
                        </div>
                    {else}
                        <div class="box-header">
                            <h3 class="box-title">{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}</h3>
                        </div>
                    {/if}
                    <table class="table table-bordered table-striped table-bordered table-hover" style="margin-bottom: 0px;">
                        <tr>
                            <td class="small text-primary text-uppercase text-normal">{Lang::T('Plan Name')}</td>
                            <td class="small mb15">
                                {$_bill['namebp']}
                                {if $_bill['status'] != 'on'}
                                    <a class="label label-danger pull-right" href="{$_url}order/package">{Lang::T('expired')}</a>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <td class="small text-info text-uppercase text-normal">{Lang::T('Created On')}</td>
                            <td class="small mb15">
                                {if $_bill['time'] ne ''}{Lang::dateAndTimeFormat($_bill['recharged_on'],$_bill['recharged_time'])}
                                {/if}&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="small text-danger text-uppercase text-normal">{Lang::T('Expires On')}</td>
                            <td class="small mb15 text-danger">
                                {if $_bill['time'] ne ''}{Lang::dateAndTimeFormat($_bill['expiration'],$_bill['time'])}{/if}&nbsp;
                            </td>
                        </tr>
                        <tr>
                            <td class="small text-success text-uppercase text-normal">{Lang::T('Type')}</td>
                            <td class="small mb15 text-success">
                                <b>{if $_bill['prepaid'] eq yes}Prepaid{else}Postpaid{/if}</b>
                                {Lang::T($_bill['plan_type'])}
                            </td>
                        </tr>
                        {if $nux_ip neq ''}
                            <tr>
                                <td class="small text-primary text-uppercase text-normal">{Lang::T('Current IP')}</td>
                                <td class="small mb15">{$nux_ip}</td>
                            </tr>
                        {/if}
                        {if $nux_mac neq ''}
                            <tr>
                                <td class="small text-primary text-uppercase text-normal">{Lang::T('Current MAC')}</td>
                                <td class="small mb15">{$nux_mac}</td>
                            </tr>
                        {/if}
                        {if $_bill['type'] == 'Hotspot' && $_bill['status'] == 'on' && $_bill['routers'] != 'radius'}
                            <tr>
                                <td class="small text-primary text-uppercase text-normal">{Lang::T('Login Status')}</td>
                                <td class="small mb15" id="login_status_{$_bill['id']}">
                                    <img src="ui/ui/images/loading.gif">
                                </td>
                            </tr>
                        {/if}
                        <tr>
                            <td class="small text-primary text-uppercase text-normal">
                                {if $_bill['status'] == 'on'}
                                    <a href="{$_url}home&deactivate={$_bill['id']}"
                                        onclick="return confirm('{Lang::T('Deactivate')}?')" class="btn btn-danger btn-xs"><i
                                            class="glyphicon glyphicon-trash"></i></a>
                                {/if}
                            </td>
                            <td class="small row">
                                {if $_bill['status'] != 'on' && $_bill['prepaid'] != 'yes' && $_c['extend_expired']}
                                    <a class="btn btn-warning text-black btn-sm"
                                        href="{$_url}home&extend={$_bill['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Text::toHex($_c['extend_confirmation'])}')">{Lang::T('Extend')}</a>
                                {/if}
                                <a class="btn btn-primary pull-right  btn-sm"
                                    href="{$_url}home&recharge={$_bill['id']}&stoken={App::getToken()}"
                                    onclick="return confirm('{Lang::T('Recharge')}?')">{Lang::T('Recharge')}</a>
                            </td>
                        </tr>
                    </table>
                {/foreach}
            </div>
        {/if}
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
        {if $_bills}
            {foreach $_bills as $_bill}
                {if $_bill['type'] == 'Hotspot' && $_bill['status'] == 'on'}
                    <script>
                        setTimeout(() => {
                            $.ajax({
                                url: "index.php?_route=autoload_user/isLogin/{$_bill['id']}",
                                cache: false,
                                success: function(msg) {
                                    $("#login_status_{$_bill['id']}").html(msg);
                                }
                            });
                        }, 2000);
                    </script>
                {/if}
            {/foreach}
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
                                    placeholder="{Lang::T('Username')}">
                            </div>
                            <div class="col-sm-5">
                                <input type="number" id="balance" name="balance" autocomplete="off" class="form-control"
                                    required placeholder="{Lang::T('Balance')}">
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
                                    placeholder="{Lang::T('Username')}">
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
                    <h3 class="box-title">{Lang::T('Voucher Activation')}</h3>
                </div>
                <div class="box-body">
                    <form method="post" role="form" class="form-horizontal" action="{$_url}voucher/activation-post">
                        <div class="form-group">
                            <label class="col-sm-4 control-label text-center">{Lang::T('Code Voucher')}</label>
                            <div class="col-sm-7">
                                <input type="text" id="code" name="code" class="form-control"
                                    placeholder="{Lang::T('Enter voucher code here')}">
                            </div>
                        </div>
                        <div class="form-group" align="center">
                            <button class="btn btn-success" type="submit">{Lang::T('Recharge')}</button>
                        </div>
                    </form>
                </div>
                <div class="box-body">
                    <div class="btn-group btn-group-justified" role="group">
                        <a class="btn btn-warning" href="{$_url}voucher/activation">
                            <i class="ion ion-ios-cart"></i>
                            {Lang::T('Order Voucher')}
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