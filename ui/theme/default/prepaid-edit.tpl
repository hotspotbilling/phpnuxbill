{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">{$_L['Recharge_Account']}</h3></div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}prepaid/edit-post">
				<input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Select_Account']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="username" name="username" value="{$d['username']}" readonly>
						</div>
                    </div>

                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Service_Plan']}</label>
						<div class="col-md-6">
							<select id="id_plan" name="id_plan" class="form-control">
                                {foreach $p as $ps}
									<option value="{$ps['id']}" {if $d['plan_id'] eq $ps['id']} selected {/if}>{$ps['name_plan']}</option>
                                {/foreach}
                            </select>
						</div>
                    </div>
					
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Created_On']}</label>
						<div class="col-md-6">
							<div class="input-group date" id="datepicker1">
								<input type="text" class="form-control" id="recharged_on" name="recharged_on" value="{$d['recharged_on']}">
								<span class="input-group-addon ion ion-calendar"></span>
							</div>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Expires_On']}</label>
						<div class="col-md-6">
							<div class="input-group date" id="datepicker2">
								<input type="text" class="form-control" id="expiration" name="expiration" value="{$d['expiration']}">
								<span class="input-group-addon ion ion-calendar"></span>
							</div>
						</div>
                    </div>
					
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-success waves-effect waves-light" type="submit">{$_L['Edit']}</button> 
							Or <a href="{$_url}prepaid/list">{$_L['Cancel']}</a>
						</div>
					</div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
