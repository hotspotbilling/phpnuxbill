{include file="customer/header-public.tpl"}
<div class="hidden-xs" style="height:100px"></div>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">{Lang::T('Registration Info')}</div>
            <div class="panel-body">
                {include file="$_path/../pages/Registration_Info.html"}
            </div>
        </div>
    </div>
    <form enctype="multipart/form-data" action="{$_url}register/post" method="post">
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">1. {Lang::T('Register as Member')}</div>
                <div class="panel-body">
                    <div class="form-container">
                        <div class="form-group">
                            <label>
                                {if $_c['registration_username'] == 'phone'}
                                    {Lang::T('Phone Number')}
                                {elseif $_c['registration_username'] == 'email'}
                                    {Lang::T('Email')}
                                {else}
                                    {Lang::T('Usernames')}
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
                                    placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Usernames')}{/if}">
                            </div>
                        </div>
                        {if $_c['photo_register'] == 'yes'}
                            <div class="form-group">
                                <label>{Lang::T('Photo')}</label>
                                <input type="file" required class="form-control" id="photo" name="photo" accept="image/*">
                            </div>
                        {/if}
                        <div class="form-group">
                            <label>{Lang::T('Full Name')}</label>
                            <input type="text" {if $_c['man_fields_fname'] neq 'no'}required{/if} class="form-control"
                                id="fullname" value="{$fullname}" name="fullname">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Email')}</label>
                            <input type="text" {if $_c['man_fields_email'] neq 'no'}required{/if} class="form-control"
                                id="email" placeholder="xxxxxxx@xxxx.xx" value="{$email}" name="email">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Home Address')}</label>
                            <input type="text" {if $_c['man_fields_address'] neq 'no'}required{/if} name="address"
                                id="address" value="{$address}" class="form-control">
                        </div>
                        {$customFields}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">2. {Lang::T('Password')}</div>
                <div class="panel-body">
                    <div class="form-container">
                        <div class="form-group">
                            <label>{Lang::T('Password')}</label>
                            <input type="password" required class="form-control" id="password" name="password">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Confirm Password')}</label>
                            <input type="password" required class="form-control" id="cpassword" name="cpassword">
                        </div>
                        <br>
                        <div class="btn-group btn-group-justified mb15">
                            <div class="btn-group">
                                <a href="{$_url}login" class="btn btn-warning">{Lang::T('Cancel')}</a>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-success" type="submit">{Lang::T('Register')}</button>
                            </div>
                        </div>
                        <br>
                        <center>
                            <a href="javascript:showPrivacy()">Privacy</a>
                            &bull;
                            <a href="javascript:showTaC()">T &amp; C</a>
                        </center>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
{include file="customer/footer-public.tpl"}