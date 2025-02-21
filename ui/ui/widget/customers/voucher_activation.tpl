
{if $_c['disable_voucher'] != 'yes'}
    <div class="box box-primary box-solid mb30">
        <div class="box-header">
            <h3 class="box-title">{Lang::T('Voucher Activation')}</h3>
        </div>
        <div class="box-body">
            <form method="post" role="form" class="form-horizontal" action="{Text::url('voucher/activation-post')}">
                <div class="input-group">
                    <span class="input-group-btn">
                        <a class="btn btn-default"
                            href="{$app_url}/scan/?back={urlencode(Text::url('home&code='))}"><i
                                class="glyphicon glyphicon-qrcode"></i></a>
                    </span>
                    <input type="text" id="code" name="code" class="form-control"
                        placeholder="{Lang::T('Enter voucher code here')}" value="{$code}">
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit">{Lang::T('Recharge')}</button>
                    </span>
                </div>
            </form>
        </div>
        <div class="box-body">
            <div class="btn-group btn-group-justified" role="group">
                <a class="btn btn-default" href="{Text::url('voucher/activation')}">
                    <i class="ion ion-ios-cart"></i>
                    {Lang::T('Order Voucher')}
                </a>
                {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                    <a href="{Text::url('order/package')}" class="btn btn-default">
                        <i class="ion ion-ios-cart"></i>
                        {Lang::T('Order Package')}
                    </a>
                {/if}
            </div>
        </div>
    </div>
{/if}