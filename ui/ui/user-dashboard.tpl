{include file="sections/user-header.tpl"}

<div class="columns">
    <div class="column">
        <div class="panel is-info">
            <div class="panel-heading">{$_L['Account_Information']}</div>
            <table class="table is-narrow is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                <tr>
                    <td class="small text-success text-uppercase text-normal">{$_L['Username']}</td>
                    <td class="small mb15">{$_bill['username']}</td>
                </tr>
                <tr>
                    <td class="small text-primary text-uppercase text-normal">{$_L['Plan_Name']}</td>
                    <td class="small mb15">{$_bill['namebp']}</td>
                </tr>
                <tr>
                    <td class="small text-info text-uppercase text-normal">{$_L['Created_On']}</td>
                    <td class="small mb15">
                        {if $_bill['time'] ne ''}{date($_c['date_format'], strtotime($_bill['recharged_on']))}
                        {$_bill['time']}{/if}&nbsp;</td>
                </tr>
                <tr>
                    <td class="small text-danger text-uppercase text-normal">{$_L['Expires_On']}</td>
                    <td class="small mb15">
                        {if $_bill['time'] ne ''}{date($_c['date_format'], strtotime($_bill['expiration']))}
                        {$_bill['time']}{/if}&nbsp;</td>
                </tr>
            </table>
        </div>
        <br>
        <div class="panel is-info is-hovered is-stacked mb30">
            <div class="panel-heading">{$_L['Voucher_Activation']}</div>
            <div class="p-3">
                <form method="post" role="form" action="{$_url}voucher/activation-post">
                    <div class="field has-addons is-expanded" align="center">
                        <p class="control">
                            <a class="button is-static is-fullwidth">
                                {$_L['Code_Voucher']}
                            </a>
                        </p>
                        <p class="control is-expanded">
                            <input class="input" type="text" id="code" name="code"
                                placeholder="{$_L['Enter_Voucher_Code']}">
                        </p>
                    </div>
                    <div class="form-group" align="center">
                            <button class="button is-success is-small" type="submit">{$_L['Recharge']}</button>
                    </div>
                </form>
            </div>
            <div class="panel-block columns">
                <div class="column">
                    <a class="button is-info is-fullwidth is-small" href="{$_url}order/voucher">
                        <span class="icon is-small"><i class="ion ion-ios-cart"></i></span>
                        <span>{$_L['Order_Voucher']} </span>
                    </a>
                </div>
                {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                    <div class="column">
                        <a href="{$_url}order/package" class="button is-link is-fullwidth is-small">
                            <span class="icon is-small"><i class="ion ion-ios-cart"></i></span>
                            <span>{Lang::T('Order Package')} </span>
                        </a>
                    </div>
                {/if}
            </div>
        </div>
    </div>
    <div class="column">
        {if $unpaid }
            <div class="panel is-danger">
                <div class="panel-heading">{Lang::T('Unpaid Order')}</div>
                <table class="table is-narrow is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                    <tbody>
                        <tr>
                            <td>{Lang::T('expired')}</td>
                            <td>{date({$_c['date_format']}, strtotime($unpaid['expired_date']))}
                                {date('H:i', strtotime($unpaid['expired_date']))} </td>
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
                <div class="panel-footer p-2">
                    <a class="button is-danger is-fullwidth is-small" href="{$_url}order/view/{$unpaid['id']}">
                        <span class="icon is-small"><i class="ion ion-card"></i></span>
                        <span>{Lang::T('PAY NOW')}</span>
                    </a>
                </div>
            </div>
        {/if}
        <div class="panel is-info">
            <div class="panel-heading">{$_L['Announcement']}</div>
            <div class="panel-block" style="max-height:296px;overflow:scroll;">
                {include file="$_path/../pages/Announcement.html"}
            </div>
        </div>
    </div>
</div>
{include file="sections/user-footer.tpl"}