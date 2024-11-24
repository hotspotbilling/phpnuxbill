{include file="customer/header-public.tpl"}
<div class="hidden-xs" style="height:100px"></div>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-info">
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
                        <!-- Phone Number Field -->
                        <div class="form-group">
                            <label>{Lang::T('Phone Number')}</label>
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-phone-alt"></i></span>
                                <input type="text" class="form-control" name="phone_number" value="{$phone_number}"
                                    readonly
                                    placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Phone Number')}{/if}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('SMS Verification Code')}</label>
                            <input type="text" required class="form-control" id="otp_code" value=""
                                placeholder="{Lang::T('Verification Code')}" name="otp_code">
                        </div>
                        {if $_c['photo_register'] == 'yes'}
                            <div class="form-group">
                                <label>{Lang::T('Photo')}</label>
                                <input type="file" required class="form-control" id="photo" name="photo" accept="image/*">
                            </div>
                        {/if}
                        <div class="form-group">
                            <label>{Lang::T('Full Name')}</label>
                            <input type="text" required class="form-control" id="fullname" value="{$fullname}"
                                name="fullname">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Email')}</label>
                            <input type="text" class="form-control" placeholder="xxxxxx@xxx.xx" id="email"
                                value="{$email}" name="email">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Address')}</label>
                            <input type="text" name="address" id="address" value="{$address}" class="form-control">
                        </div>
                        {$customFields}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">2. {Lang::T('Username & Password')}</div>
                <div class="panel-body">
                    <div class="form-container">
                        <!-- Username Field -->
                        <div class="form-group">
                            <label>{Lang::T('Username')}</label>
                            <input type="text" required class="form-control" id="username" name="username"
                                placeholder="{Lang::T('Choose a Username')}">
                        </div>
                        <!-- Password Fields -->
                        <div class="form-group">
                            <label>{Lang::T('Password')}</label>
                            <input type="password" required class="form-control" id="password" name="password">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Confirm Password')}</label>
                            <input type="password" required class="form-control" id="cpassword" name="cpassword">
                        </div>
                        <div class="btn-group btn-group-justified mb15">
                            <div class="btn-group">
                                <a href="{$_url}register" class="btn btn-success">{Lang::T('Cancel')}</a>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary" type="submit">{Lang::T('Register')}</button>
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
{if $_c['tawkto'] != ''}
    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var Tawk_API = Tawk_API || {},
            Tawk_LoadStart = new Date();
        (function() {
            var s1 = document.createElement("script"),
                s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src='https://embed.tawk.to/{$_c['tawkto']}';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
    <!--End of Tawk.to Script-->
{/if}

{include file="customer/footer-public.tpl"}