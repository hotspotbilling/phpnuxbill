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
                        {if $add_cost>0}
                            {foreach $bills as $k => $v}
                                <tr>
                                    <td>{$k}</td>
                                    <td>{Lang::moneyFormat($v)}</td>
                                </tr>
                            {/foreach}
                            <tr>
                                <td>{Lang::T('Additional Cost')}</td>
                                <td>{Lang::moneyFormat($add_cost)}</td>
                            </tr>
                        {/if}
                        <tr>
                            <td>{Lang::T('Price')}{if $add_cost>0}<small> + {Lang::T('Additional Cost')}{/if}</td>
                            <td style="font-size: large; font-weight:bolder; font-family: 'Courier New', Courier, monospace; ">{Lang::moneyFormat($plan['price'])}</td>
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
                                placeholder="{Lang::T('Username')}">
                        </div>
                        <div class="form-group col-sm-3" align="center">
                            <button class="btn btn-success btn-block" id="sendBtn" type="submit" name="send" onclick="return confirm('{Lang::T("Are You Sure?")}')"
                                value="plan"><i class="glyphicon glyphicon-send"></i></button>
                        </div>
                    </div>
                    <p class="help-block text-center">{Lang::T('If your friend have Additional Cost, you will pay for that too')}</p>
                </form>
            </div>
        </div>
    </div>
</div>
{include file="sections/user-footer.tpl"}