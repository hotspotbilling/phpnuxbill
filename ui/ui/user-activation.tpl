{include file="sections/user-header.tpl"}
<!-- user-activation -->

<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary box-solid">
            <div class="box-header"><h3 class="box-title">{$_L['Order_Voucher']}</h3></div>
            <div class="box-body">
                {include file="$_path/../pages/Order_Voucher.html"}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-success panel-hovered panel-stacked mb30">
            <div class="panel-heading">{$_L['Voucher_Activation']}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}voucher/activation-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Code_Voucher']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="code" name="code"
                                placeholder="{$_L['Enter_Voucher_Code']}">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success waves-effect waves-light"
                                type="submit">{$_L['Recharge']}</button>
                            Or <a href="{$_url}home">{$_L['Cancel']}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/user-footer.tpl"}