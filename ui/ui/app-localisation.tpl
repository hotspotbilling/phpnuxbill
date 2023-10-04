{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{$_L['Localisation']}</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}settings/localisation-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Timezone']}</label>
                        <div class="col-md-6">
                            <select name="tzone" id="tzone" class="form-control">
                                {foreach $tlist as $value => $label}
                                    <option value="{$value}" {if $_c['timezone'] eq $value}selected="selected" {/if}>
                                        {$label}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Date_Format']}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="date_format" id="date_format">
                                <option value="d/m/Y" {if $_c['date_format'] eq 'd/m/Y'} selected="selected" {/if}>
                                    {date('d/m/Y')}</option>
                                <option value="d.m.Y" {if $_c['date_format'] eq 'd.m.Y'} selected="selected" {/if}>
                                    {date('d.m.Y')}</option>
                                <option value="d-m-Y" {if $_c['date_format'] eq 'd-m-Y'} selected="selected" {/if}>
                                    {date('d-m-Y')}</option>
                                <option value="m/d/Y" {if $_c['date_format'] eq 'm/d/Y'} selected="selected" {/if}>
                                    {date('m/d/Y')}</option>
                                <option value="Y/m/d" {if $_c['date_format'] eq 'Y/m/d'} selected="selected" {/if}>
                                    {date('Y/m/d')}</option>
                                <option value="Y-m-d" {if $_c['date_format'] eq 'Y-m-d'} selected="selected" {/if}>
                                    {date('Y-m-d')}</option>
                                <option value="M d Y" {if $_c['date_format'] eq 'M d Y'} selected="selected" {/if}>
                                    {date('M d Y')}</option>
                                <option value="d M Y" {if $_c['date_format'] eq 'd M Y'} selected="selected" {/if}>
                                    {date('d M Y')}</option>
                                <option value="jS M y" {if $_c['date_format'] eq 'jS M y'} selected="selected" {/if}>
                                    {date('jS M y')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Default_Language']}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="lan" id="lan">
                                {foreach $lan as $lans}
                                    <option value="{$lans}" {if $_c['language'] eq $lans} selected="selected" {/if}>{$lans}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-md-4 help-block">
                            To add new Language, just add the folder, it will automatically detected
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Decimal_Point']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="dec_point" name="dec_point"
                                value="{$_c['dec_point']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Thousands_Separator']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="thousands_sep" name="thousands_sep"
                                value="{$_c['thousands_sep']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Currency_Code']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="currency_code" name="currency_code"
                                value="{$_c['currency_code']}">
                        </div>
                        <span class="help-block col-md-4">{$_L['currency_help']}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Country Code Phone')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon1">+</span>
                                <input type="text" class="form-control" id="country_code_phone" placeholder="62"
                                    name="country_code_phone" value="{$_c['country_code_phone']}">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Radius Plan</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="radius_plan" name="radius_plan"
                                value="{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('Change title in user Plan order')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Hotspot Plan</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="hotspot_plan" name="hotspot_plan"
                                value="{if $_c['hotspot_plan']==''}Hotspot Plan{else}{$_c['hotspot_plan']}{/if}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('Change title in user Plan order')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">PPPOE Plan</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="pppoe_plan" name="pppoe_plan"
                                value="{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('Change title in user Plan order')}</span>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light"
                                type="submit">{$_L['Save']}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}