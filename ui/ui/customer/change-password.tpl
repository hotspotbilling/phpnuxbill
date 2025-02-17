{include file="customer/header.tpl"}
<!-- user-change-password -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Change Password')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form"
                    action="{Text::url('accounts/change-password-post')}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Current Password')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('New Password')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="npass" name="npass">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Confirm New Password')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="cnpass" name="cnpass">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success" type="submit">{Lang::T('Save Changes')}</button>
                            Or <a href="{Text::url('home')}">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="customer/footer.tpl"}
