{include file="sections/user-header.tpl"}
<!-- user-orderView -->
<div class="row">
    <div class="col-md-3"></div>
    <div class="col-md-6">
        <div class="box box-solid box-default">
            <div class="box-header">{$plan['name_plan']}</div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td>{Lang::T('Type')}</td>
                            <td>{$plan['type']}</td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Price')}</td>
                            <td>{Lang::moneyFormat($plan['price'])}</td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Validity')}</td>
                            <td>{$plan['validity']} {$plan['validity_unit']}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                <form method="post" onsubmit="return askConfirm()" role="form">
                    <div class="form-group">
                        <div class="col-sm-9">
                            <input type="text" id="username" name="username" class="form-control" required value="{$username}"
                                placeholder="{$_L['Username']}">
                        </div>
                        <div class="form-group col-sm-3" align="center">
                            <button class="btn btn-success btn-block" id="sendBtn" type="submit" name="send" onclick="return confirm('{Lang::T("Are You Sure?")}')"
                                value="plan"><i class="glyphicon glyphicon-send"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
{include file="sections/user-footer.tpl"}