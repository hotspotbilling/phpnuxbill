{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Add_New_Administrator']}</div>
						<div class="panel-body">
						
                <form class="form-horizontal" method="post" role="form" action="{$_url}settings/users-post" >            
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Username']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="username" name="username">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Full_Name']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="fullname" name="fullname">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['User_Type']}</label>
						<div class="col-md-6">
							<select name="user_type" id="user_type" class="form-control">
								<option value="Admin">{$_L['Full_Administrator']}</option>
								<option value="Sales">{$_L['Sales']}</option>
							</select>
							<span class="help-block">{$_L['user_type_help']}</span>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Password']}</label>
						<div class="col-md-6">
							<input type="password" class="form-control" id="password" name="password">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Confirm_Password']}</label>
						<div class="col-md-6">
							<input type="password" class="form-control" id="cpassword" name="cpassword">
						</div>
                    </div>

					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
							Or <a href="{$_url}settings/users">{$_L['Cancel']}</a>
						</div>
					</div>
                </form>
				
					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
