{include file="user-ui/header-public.tpl"}

<div class="hidden-xs" style="height:100px"></div>
<form action="{$_url}forgot&step={$step+1}" method="post">
    <div class="row">
        <div class="col-sm-4 col-sm-offset-4">
            {if $step == 1}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Verification Code')}</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                {else}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                {/if}
                                <input type="text" readonly class="form-control" name="username" value="{$username}"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-asterisk"></i></span>
                                <input type="text" required class="form-control" id="otp_code"
                                    placeholder="{Lang::T('Verification Code')}" name="otp_code">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-block btn-primary">{Lang::T('Validate')}</button>
                        <a href="{$_url}forgot&step=-1" class="btn btn-block btn-link">{Lang::T('Cancel')}</a>
                    </div>
                </div>
            {elseif $step == 2}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Success')}</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                {else}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                {/if}
                                <input type="text" readonly class="form-control" name="username" value="{$username}"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                            </div>
                        </div>
                        <label>{Lang::T('Your Password has been change to')}</label>
                        <input type="text" readonly class="form-control" value="{$passsword}" onclick="this.select()">
                        <p class="help-block">
                            {Lang::T('Use the password to login, and change the password from password change page')}</p>
                    </div>
                    <div class="panel-footer">
                        <a href="{$_url}login" class="btn btn-block btn-primary">{Lang::T('Back')}</a>
                    </div>
                </div>
            {elseif $step == 6}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Forgot Username')}</div>
                    <div class="panel-body">
                        <label>{Lang::T('Please input your Email or Phone number')}</label>
                        <input type="text" name="find" class="form-control" required value="">
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-block btn-primary">{Lang::T('Validate')}</button>
                        <a href="{$_url}forgot" class="btn btn-block btn-link">{Lang::T('Back')}</a>
                    </div>
                </div>
            {else}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Forgot Password')}</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                {else}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                {/if}
                                <input type="text" class="form-control" name="username" required
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-block btn-primary">{Lang::T('Validate')}</button>
                        <a href="{$_url}forgot&step=6" class="btn btn-block btn-link">{Lang::T('Forgot Username')}</a>
                        <a href="{$_url}login" class="btn btn-block btn-link">{Lang::T('Back')}</a>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</form>
{include file="user-ui/footer-public.tpl"}