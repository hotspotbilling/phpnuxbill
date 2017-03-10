{include file="sections/user-header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Change_Password']}</div>
						<div class="panel-body">
						<form class="form-horizontal" method="post" role="form" action="{$_url}accounts/change-password-post">
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['Current_Password']}</label>
								<div class="col-md-6">
									<input type="password" class="form-control" id="password" name="password">
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['New_Password']}</label>
								<div class="col-md-6">
									<input type="password" class="form-control" id="npass" name="npass">
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{$_L['Confirm_New_Password']}</label>
								<div class="col-md-6">
									<input type="password" class="form-control" id="cnpass" name="cnpass">
								</div>
							</div>
							
							<div class="form-group">
								<div class="col-lg-offset-2 col-lg-10">
									<button class="btn btn-success waves-effect waves-light" type="submit">{$_L['Save']}</button>
									Or <a href="{$_url}home">{$_L['Cancel']}</a>
								</div>
							</div>
						</form>
				
					</div>
				</div>
			</div>
		</div>

{include file="sections/user-footer.tpl"}
