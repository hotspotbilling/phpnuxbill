{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Add_Plan']}</div>
						<div class="panel-body">
                        <form class="form-horizontal" method="post" role="form" action="{$_url}services/add-post" >
                            <div class="form-group">
                                <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                                <div class="col-md-10">
                                    <label class="radio-inline warning">
                                        <input type="radio" checked name="enabled" value="1"> Enable
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="enabled" value="0"> Disable
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{$_L['Plan_Name']}</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="name" name="name" maxlength="40">
                                        <p class="help-block">{Lang::T('Cannot be change after saved')}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{$_L['Plan_Type']}</label>
                                <div class="col-md-10">
                                    <input type="radio" id="Unlimited" name="typebp" value="Unlimited" checked> {$_L['Unlimited']}
                                    <input type="radio" id="Limited" name="typebp" value="Limited"> {$_L['Limited']}
                                </div>
                            </div>
                            <div style="display:none;" id="Type">
                                <div class="form-group">
                                    <label class="col-md-2 control-label">{$_L['Limit_Type']}</label>
                                    <div class="col-md-10">
                                        <input type="radio" id="Time_Limit" name="limit_type" value="Time_Limit" checked> {$_L['Time_Limit']}
                                        <input type="radio" id="Data_Limit" name="limit_type" value="Data_Limit"> {$_L['Data_Limit']}
                                        <input type="radio" id="Both_Limit" name="limit_type" value="Both_Limit"> {$_L['Both_Limit']}
                                    </div>
                                </div>
                            </div>
                            <div style="display:none;" id="TimeLimit">
                                <div class="form-group">
                                    <label class="col-md-2 control-label">{$_L['Time_Limit']}</label>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="time_limit" name="time_limit" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control" id="time_unit" name="time_unit">
                                            <option value="Hrs">{$_L['Hrs']}</option>
                                            <option value="Mins">{$_L['Mins']}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div style="display:none;" id="DataLimit">
                                <div class="form-group">
                                    <label class="col-md-2 control-label">{$_L['Data_Limit']}</label>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="data_limit" name="data_limit" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control" id="data_unit" name="data_unit">
                                            <option value="MB">MBs</option>
                                            <option value="GB">GBs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{$_L['BW_Name']}</label>
                                <div class="col-md-6">
                                    <select id="id_bw" name="id_bw" class="form-control">
                                        <option value="">{$_L['Select_BW']}...</option>
                                        {foreach $d as $ds}
                                            <option value="{$ds['id']}">{$ds['name_bw']}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{$_L['Plan_Price']}</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="pricebp" name="pricebp">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{$_L['Shared_Users']}</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="sharedusers" name="sharedusers" value="1">
                                    <p class="help-block">{Lang::T('1 user can be used for many devices?')}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{$_L['Plan_Validity']}</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="validity" name="validity">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control" id="validity_unit" name="validity_unit">
                                        <option value="Mins" {if $d['validity_unit'] eq 'Mins'} selected {/if}>{$_L['Mins']}</option>
                                        <option value="Hrs" {if $d['validity_unit'] eq 'Hrs'} selected {/if}>{$_L['Hrs']}</option>
                                        <option value="Days" {if $d['validity_unit'] eq 'Days'} selected {/if}>{$_L['Days']}</option>
                                        <option value="Months" {if $d['validity_unit'] eq 'Months'} selected {/if}>{$_L['Months']}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{$_L['Router_Name']}</label>
                                <div class="col-md-6">
                                    <select id="routers" name="routers" class="form-control">
                                        {foreach $r as $rs}
                                            <option value="{$rs['name']}">{$rs['name']}</option>
                                        {/foreach}
                                    </select>
                                        <p class="help-block">{Lang::T('Cannot be change after saved')}</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <button class="btn btn-success waves-effect waves-light" type="submit">{$_L['Save']}</button>
                                    Or <a href="{$_url}services/hotspot">{$_L['Cancel']}</a>
                                </div>
                            </div>
                        </form>
					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
