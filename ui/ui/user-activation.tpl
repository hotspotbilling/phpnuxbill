{include file="sections/user-header.tpl"}
<!-- user-activation -->

<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary box-solid">
            <div class="box-header"><h3 class="box-title">{Lang::T('Order Voucher')}</h3></div>
            <div class="box-body">
                {include file="$_path/../pages/Order_Voucher.html"}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-success panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Voucher Activation')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}voucher/activation-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Code Voucher')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="code" name="code"
                                placeholder="{Lang::T('Enter voucher code here')}">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success"
                                type="submit">{Lang::T('Recharge')}</button>
                            Or <a href="{$_url}home">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/user-footer.tpl"}