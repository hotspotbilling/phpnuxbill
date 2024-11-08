{include file="customer/header-public.tpl"}

<div class="hidden-xs" style="height:100px"></div>

<div class="row">
    <div class="col-md-2">
    </div>
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">{Lang::T('Registration Info')}</div>
            <div class="panel-body">
                {include file="$_path/../pages/Registration_Info.html"}
            </div>
        </div>
    </div>
    <form action="{$_url}register" method="post">
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">1. {Lang::T('Register as Member')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>
                            {Lang::T('Phone Number')}
                        </label>
                        <div class="input-group">
                            <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-phone-alt"></i></span>
                            <input type="text" class="form-control" name="phone_number"
                                placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Phone Number')}{/if}"inputmode="numeric" pattern="[0-9]*">
                        </div>
                    </div>
                    <div class="btn-group btn-group-justified mb15">
                        <div class="btn-group">
                            <a href="{$_url}login" class="btn btn-warning">{Lang::T('Cancel')}</a>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-success" type="submit">{Lang::T('Request OTP')}</button>
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
    </form>
</div>

{include file="customer/footer-public.tpl"}
