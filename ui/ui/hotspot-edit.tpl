{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Edit_Plan']}</div>
						<div class="panel-body">
						<form class="form-horizontal" method="post" role="form" action="{$_url}services/edit-post">
							<input type="hidden" name="id" value="{$d['id']}">
                            <div class="form-group">
                                <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                                <div class="col-md-10">
                                    <label class="radio-inline warning">
                                        <input type="radio" {if $d['enabled'] == 1}checked{/if} name="enabled" value="1"> Enable
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" {if $d['enabled'] == 0}checked{/if} name="enabled" value="0"> Disable
                                    </label>
                                </div>
                            </div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['Plan_Name']}</label>
								<div class="col-md-6">
									<input type="text" class="form-control" id="name" name="name" maxlength="40" value="{$d['name_plan']}" readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['Plan_Type']}</label>
								<div class="col-md-10">
									<input type="radio" id="Unlimited" name="typebp" value="Unlimited" {if $d['typebp'] eq 'Unlimited'} checked {/if}> {$_L['Unlimited']}
									<input type="radio" id="Limited" name="typebp" value="Limited" {if $d['typebp'] eq 'Limited'} checked {/if}> {$_L['Limited']}
								</div>
							</div>
							<div {if $d['typebp'] eq 'Unlimited'} style="display:none;" {/if} id="Type">
								<div class="form-group">
									<label class="col-md-2 control-label">{$_L['Limit_Type']}</label>
									<div class="col-md-10">
										<input type="radio" id="Time_Limit" name="limit_type" value="Time_Limit" {if $d['limit_type'] eq 'Time_Limit'} checked {/if}> {$_L['Time_Limit']}
										<input type="radio" id="Data_Limit" name="limit_type" value="Data_Limit" {if $d['limit_type'] eq 'Data_Limit'} checked {/if}> {$_L['Data_Limit']}
										<input type="radio" id="Both_Limit" name="limit_type" value="Both_Limit" {if $d['limit_type'] eq 'Both_Limit'} checked {/if}> {$_L['Both_Limit']}
									</div>
								</div>
							</div>
							<div {if $d['typebp'] eq 'Unlimited'} style="display:none;" {elseif ($d['time_limit']) eq '0'} style="display:none;" {/if} id="TimeLimit">
								<div class="form-group">
									<label class="col-md-2 control-label">{$_L['Time_Limit']}</label>
									<div class="col-md-4">
										<input type="text" class="form-control" id="time_limit" name="time_limit" value="{$d['time_limit']}">
									</div>
									<div class="col-md-2">
										<select class="form-control" id="time_unit" name="time_unit" >
											<option value="Hrs" {if $d['time_unit'] eq 'Hrs'} selected {/if}>{$_L['Hrs']}</option>
											<option value="Mins" {if $d['time_unit'] eq 'Mins'} selected {/if}>{$_L['Mins']}</option>
										</select>
									</div>
								</div>
							</div>
							<div {if $d['typebp'] eq 'Unlimited'} style="display:none;" {elseif ($d['data_limit']) eq '0'} style="display:none;" {/if} id="DataLimit">
								<div class="form-group">
									<label class="col-md-2 control-label">{$_L['Data_Limit']}</label>
									<div class="col-md-4">
										<input type="text" class="form-control" id="data_limit" name="data_limit" value="{$d['data_limit']}">
									</div>
									<div class="col-md-2">
										<select class="form-control" id="data_unit" name="data_unit">
											<option value="MB" {if $d['data_unit'] eq 'MB'} selected {/if}>MBs</option>
											<option value="GB" {if $d['data_unit'] eq 'GB'} selected {/if}>GBs</option>
										</select>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['BW_Name']}</label>
								<div class="col-md-6">
									<select id="id_bw" name="id_bw" class="form-control">
										{foreach $b as $bs}
											<option value="{$bs['id']}" {if $d['id_bw'] eq $bs['id']} selected {/if}>{$bs['name_bw']}</option>
										{/foreach}
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['Plan_Price']}</label>
								<div class="col-md-6">
									<input type="text" class="form-control" id="price" name="price" value="{$d['price']}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['Shared_Users']}</label>
								<div class="col-md-6">
									<input type="text" class="form-control" id="sharedusers" name="sharedusers" value="{$d['shared_users']}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['Plan_Validity']}</label>
								<div class="col-md-4">
									<input type="text" class="form-control" id="validity" name="validity" value="{$d['validity']}">
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
									<input type="text" class="form-control" id="routers" name="routers" value="{$d['routers']}" readonly>
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
