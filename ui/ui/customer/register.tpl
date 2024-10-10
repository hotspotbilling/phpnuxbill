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
    <form class="form-horizontal" action="{$_url}register/post" method="post">
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">1. {Lang::T('Register as Member')}</div>
                <div class="panel-body">
                    <div class="form-container">
                        <div class="md-input-container">
                            <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                    <span class="input-group-addon" id="basic-addon1"><i
                                            class="glyphicon glyphicon-phone-alt"></i></span>
                                {else}
                                    <span class="input-group-addon" id="basic-addon1"><i
                                            class="glyphicon glyphicon-user"></i></span>
                                {/if}
                                <input type="text" class="form-control" name="username"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                            </div>
                        </div>
                        <div class="md-input-container md-float-label">
                            <label>{Lang::T('Full Name')}</label>
                            <input type="text" required class="form-control" id="fullname" value="{$fullname}"
                                name="fullname">
                        </div>
                        <div class="md-input-container md-float-label">
                            <label>{Lang::T('Email')}</label>
                            <input type="text" class="form-control" id="email" placeholder="xxxxxxx@xxxx.xx"
                                value="{$email}" name="email">
                        </div>
                        <div class="md-input-container md-float-label">
                            <label>{Lang::T('Address')}</label>
                            <input type="text" name="address" id="address" value="{$address}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">2. {Lang::T('Password')}</div>
                <div class="panel-body">
                    <div class="form-container">
                        <div class="md-input-container md-float-label">
                            <label>{Lang::T('Password')}</label>
                            <input type="password" required class="form-control" id="password" name="password">
                        </div>
                        <div class="md-input-container md-float-label">
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