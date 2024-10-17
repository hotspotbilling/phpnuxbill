{include file="customer/header-public.tpl"}

<div class="hidden-xs" style="height:100px"></div>
<div class="row">
    <div class="col-sm-6 col-sm-offset-1">
        <div class="panel panel-info">
            <div class="panel-heading">{Lang::T('Announcement')}</div>
            <div class="panel-body">
                {$Announcement = "{$PAGES_PATH}/Announcement.html"}
                {if file_exists($Announcement)}
                    {include file=$Announcement}
                {/if}
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-primary">
            <div class="panel-heading">{Lang::T('Log in to Member Panel')}</div>
            <div class="panel-body">
                <form action="{$_url}login/post" method="post">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="form-group">
                        <label>
                            {if $_c['registration_username'] == 'phone'}
                                {Lang::T('Phone Number')}
                            {elseif $_c['registration_username'] == 'email'}
                                {Lang::T('Email')}
                            {else}
                                {Lang::T('Username')}
                            {/if}
                        </label>
                        <div class="input-group">
                            {if $_c['registration_username'] == 'phone'}
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-phone-alt"></i></span>
                            {elseif $_c['registration_username'] == 'email'}
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-envelope"></i></span>
                            {else}
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-user"></i></span>
                            {/if}
                            <input type="text" class="form-control" name="username"
                                placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{Lang::T('Password')}</label>
                        <div class="input-group">
                            <span class="input-group-addon" id="basic-addon2"><i
                                    class="glyphicon glyphicon-lock"></i></span>
                            <input type="password" class="form-control" name="password"
                                placeholder="{Lang::T('Password')}">
                        </div>
                    </div>

                    <div class="clearfix hidden">
                        <div class="ui-checkbox ui-checkbox-primary right">
                            <label>
                                <input type="checkbox">
                                <span>Remember me</span>
                            </label>
                        </div>
                    </div>
                    <div class="btn-group btn-group-justified mb15">
                        {if $_c['disable_registration'] != 'noreg'}
                            <div class="btn-group">
                                <a href="{$_url}register" class="btn btn-success">{Lang::T('Register')}</a>
                            </div>
                        {/if}
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">{Lang::T('Login')}</button>
                        </div>
                    </div>
                    <br>
                    <center>
                        <a href="{$_url}forgot" class="btn btn-link">{Lang::T('Forgot Password')}</a>
                        <br>
                        <a href="javascript:showPrivacy()">Privacy</a>
                        &bull;
                        <a href="javascript:showTaC()">T &amp; C</a>
                    </center>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="HTMLModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="HTMLModal_konten"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">&times;</button>
            </div>
        </div>
    </div>
</div>

{include file="customer/footer-public.tpl"}