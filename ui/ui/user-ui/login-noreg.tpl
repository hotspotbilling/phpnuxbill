{include file="user-ui/header-public.tpl"}
<div class="hidden-xs" style="height:100px"></div>
<div class="row">
    <div class="col-sm-8">
        <div class="panel panel-info">
            <div class="panel-heading">{Lang::T('Announcement')}</div>
            <div class="panel-body">
                {include file="$_path/../pages/Announcement.html"}
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="panel panel-primary">
            <div class="panel-heading">{Lang::T('Login / Activate Voucher')}</div>
            <div class="panel-body">
                <form action="{$_url}login/activation" method="post">
                    <div class="form-group">
                        <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                        <div class="input-group">
                            {if $_c['country_code_phone']!= ''}
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-phone-alt"></i></span>
                            {else}
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-user"></i></span>
                            {/if}
                            <input type="text" class="form-control" name="username" required
                                placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{Lang::T('Enter voucher code here')}</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="voucher" name="voucher" required value="{$code}"
                                placeholder="{Lang::T('Enter voucher code here')}">
                            <span class="input-group-btn">
                                <a class="btn btn-default"
                                    href="{APP_URL}/scan/?back={urlencode($_url)}{urlencode("login&code=")}"><i
                                        class="glyphicon glyphicon-qrcode"></i></a>
                            </span>
                        </div>
                    </div>
                    <div class="btn-group btn-group-justified mb15">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">{Lang::T('Login / Activate Voucher')}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="panel panel-primary">
            <div class="panel-heading">{Lang::T('Activate Voucher')}</div>
            <div class="panel-body">
                <form action="{$_url}login/activation" method="post">
                    <div class="form-group">
                        <label>{Lang::T('Enter voucher code here')}</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="voucher_only" name="voucher_only" required
                                value="{$code}" placeholder="{Lang::T('Enter voucher code here')}">
                            <span class="input-group-btn">
                                <a class="btn btn-default"
                                    href="{APP_URL}/scan/?back={urlencode($_url)}{urlencode("login&code=")}"><i
                                        class="glyphicon glyphicon-qrcode"></i></a>
                            </span>
                        </div>
                    </div>
                    <div class="btn-group btn-group-justified mb15">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">{Lang::T('Activate Voucher')}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <br>
        <center>
            <a href="./pages/Privacy_Policy.html" target="_blank">Privacy</a>
            &bull;
            <a href="./pages/Terms_of_Conditions.html" target="_blank">ToC</a>
        </center>
    </div>
</div>
{include file="user-ui/footer-public.tpl"}