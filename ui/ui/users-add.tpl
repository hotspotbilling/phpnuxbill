{include file="sections/header.tpl"}
<!-- user-edit -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Add New Administrator')}</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}settings/users-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Username')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="username" name="username">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Full Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="fullname" name="fullname">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('User Type')}</label>
                        <div class="col-md-6">
                            <select name="user_type" id="user_type" class="form-control">
                                <option value="SuperAdmin">SuperAdministrator</option>
                                <option value="Admin">Administrator</option>
                                <option value="Report">Report Viewer</option>
                                <option value="Agent">Agent</option>
                                <option value="Sales">Sales</option>
                            </select>
                            <span class="help-block">{Lang::T('Choose User Type Sales to disable access to Settings')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Confirm Password')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="cpassword" name="cpassword">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light"
                                type="submit">{Lang::T('Save Changes')}</button>
                            Or <a href="{$_url}settings/users">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}