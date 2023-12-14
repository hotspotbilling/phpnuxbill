{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{$_L['Add_Contact']}</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}customers/add-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Username']}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                    <span class="input-group-addon" id="basic-addon1">+</span>
                                {else}
                                    <span class="input-group-addon" id="basic-addon1"><i
                                            class="glyphicon glyphicon-phone-alt"></i></span>
                                {/if}
                                <input type="text" class="form-control" name="username" required
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {$_L['Phone_Number']}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Full_Name']}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="fullname" name="fullname">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Email']}</label>
                        <div class="col-md-6">
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Phone_Number']}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                    <span class="input-group-addon" id="basic-addon1">+</span>
                                {else}
                                    <span class="input-group-addon" id="basic-addon1"><i
                                            class="glyphicon glyphicon-phone-alt"></i></span>
                                {/if}
                                <input type="text" class="form-control" name="phonenumber"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {$_L['Phone_Number']}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Password']}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" autocomplete="off" required id="password"
                                name="password" onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('PPPOE Password')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="pppoe_password" name="pppoe_password"
                                value="{$d['pppoe_password']}" onmouseleave="this.type = 'password'"
                                onmouseenter="this.type = 'text'">
                            <span
                                class="help-block">{Lang::T('User Cannot change this, only admin. if it Empty it will use user password')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Address']}</label>
                        <div class="col-md-6">
                            <textarea name="address" id="address" class="form-control"></textarea>
                        </div>
                    </div>
					<div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Service Type')}</label>
                        <div class="col-md-6">
						<select class="form-control" id="service_type" name="service_type">
						<option value="Hotspot" {if $d['service_type'] eq 'Hotspot'}selected{/if}>Hotspot</option>
						<option value="PPPoE" {if $d['service_type'] eq 'PPPoE'}selected{/if}>PPPoE</option>
						<option value="Others" {if $d['service_type'] eq 'Others'}selected{/if}>Others</option>
						</select>
						</div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light"
                                type="submit">{$_L['Save']}</button>
                            Or <a href="{$_url}customers/list">{$_L['Cancel']}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>


{include file="sections/footer.tpl"}