{include file="sections/header.tpl"}
<!-- user-edit -->

<form class="form-horizontal" method="post" role="form" action="{$_url}settings/users-post">
    <div class="row">
        <div class="col-sm-6 col-md-6">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Profile')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Full Name')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="fullname" name="fullname">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Phone')}</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Email')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="city" name="city" placeholder="{Lang::T('City')}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="subdistrict" name="subdistrict" placeholder="{Lang::T('Sub District')}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="ward" name="ward" placeholder="{Lang::T('Ward')}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-6">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Credentials')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('User Type')}</label>
                        <div class="col-md-9">
                            <select name="user_type" id="user_type" class="form-control" onchange="checkUserType(this)">
                                {if $_admin['user_type'] eq 'Agent'}
                                    <option value="Sales">{Lang::T('Sales')}</option>
                                {/if}
                                {if $_admin['user_type'] eq 'Admin' || $_admin['user_type'] eq 'SuperAdmin'}
                                    <option value="Report">{Lang::T('Report Viewer')}</option>
                                    <option value="Agent">{Lang::T('Agent')}</option>
                                    <option value="Sales">{Lang::T('Sales')}</option>
                                {/if}
                                {if $_admin['user_type'] eq 'SuperAdmin'}
                                    <option value="Admin">{Lang::T('Administrator')}</option>
                                    <option value="SuperAdmin">{Lang::T('Super Administrator')}</option>
                                {/if}
                            </select>
                        </div>
                    </div>
                    <div class="form-group hidden" id="agentChooser">
                        <label class="col-md-3 control-label">{Lang::T('Agent')}</label>
                        <div class="col-md-9">
                            <select name="root" id="root" class="form-control">
                                {foreach $agents as $agent}
                                    <option value="{$agent['id']}">{$agent['username']} | {$agent['fullname']} | {$agent['phone']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Username')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="username" name="username">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" id="password" value="{rand(000000,999999)}" name="password"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-5 control-label">{Lang::T('Send Notification')}</label>
                        <div class="col-md-7">
                            <select name="send_notif" id="send_notif" class="form-control">
                                <option value="-">Don't Send</option>
                                <option value="sms">By SMS</option>
                                <option value="wa">By WhatsApp</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group text-center">
        <button class="btn btn-primary" type="submit">{Lang::T('Save Changes')}</button>
        Or <a href="{$_url}settings/users">{Lang::T('Cancel')}</a>
    </div>
</form>
{literal}
    <script>
        function checkUserType($field){
            if($field.value=='Sales'){
                $('#agentChooser').removeClass('hidden');
            }else{
                $('#agentChooser').addClass('hidden');
            }
        }
</script>
{/literal}

{include file="sections/footer.tpl"}