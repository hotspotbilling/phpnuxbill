{include file="sections/user-header.tpl"}
<!-- user-profile -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Edit User')}</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}accounts/edit-profile-post">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Username')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon1">+</span>
                                <input type="text" class="form-control" name="username" id="username" readonly
                                    value="{$d['username']}"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {Lang::T('Phone Number')}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Full Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="fullname" name="fullname"
                                value="{$d['fullname']}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Address')}</label>
                        <div class="col-md-6">
                            <textarea name="address" id="address" class="form-control">{$d['address']}</textarea>
                        </div>
                    </div>
                    {if $_c['allow_phone_otp'] != 'yes'}
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Phone Number')}</label>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-addon" id="basic-addon1">+</span>
                                    <input type="text" class="form-control" name="phonenumber" id="phonenumber"
                                        value="{$d['phonenumber']}"
                                        placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {Lang::T('Phone Number')}">
                                </div>
                            </div>
                        </div>
                    {else}
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Phone Number')}</label>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-addon" id="basic-addon1">+</span>
                                    <input type="text" class="form-control" name="phonenumber" id="phonenumber"
                                        value="{$d['phonenumber']}" readonly
                                        placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {Lang::T('Phone Number')}">
                                    <span class="input-group-btn">
                                        <a href="{$_url}accounts/phone-update" type="button"
                                            class="btn btn-info btn-flat">{Lang::T('Change')}</a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    {/if}
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Email')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="email" name="email" value="{$d['email']}">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success" type="submit">
                            {Lang::T('Save Changes')}</button>
                            Or <a href="{$_url}home">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{include file="sections/user-footer.tpl"}