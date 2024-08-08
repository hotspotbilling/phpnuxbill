{include file="sections/user-header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}accounts/pppoe-settings-post">
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('PPPoE Settings')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('PPPoE Username')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="pppoe_username" name="pppoe_username" value="{$d['pppoe_username']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('PPPoE Password')}</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" id="pppoe_password" name="pppoe_password" value="">
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <button class="btn btn-primary" type="submit">{Lang::T('Save Changes')}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/user-footer.tpl"}
