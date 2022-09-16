{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Edit_User']}</div>
						<div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}settings/users-edit-post">
				<input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Username']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="username" name="username" value="{$d['username']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Full_Name']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="fullname" name="fullname" value="{$d['fullname']}">
						</div>
                    </div>
					{if ($_admin['id']) neq ($d['id'])}
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['User_Type']}</label>
						<div class="col-md-6">
							<select name="user_type" id="user_type" class="form-control">
                                <option value="Admin" {if $d['user_type'] eq 'Admin'}selected="selected" {/if}>Full Administrator</option>
                                <option value="Sales" {if $d['user_type'] eq 'Sales'}selected="selected" {/if}>Sales</option>
                            </select>
							<span class="help-block">{$_L['user_type_help']}</span>
						</div>
                    </div>
					{/if}
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Password']}</label>
						<div class="col-md-6">
							<input type="password" class="form-control" id="password" name="password">
							<span class="help-block">{$_L['password_change_help']}</span>
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
							<button class="btn btn-success waves-effect waves-light" type="submit">{$_L['Save']}</button>
							Or <a href="{$_url}settings/users">{$_L['Cancel']}</a>
						</div>
					</div>
                </form>
				
					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
