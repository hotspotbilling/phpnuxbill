{include file="sections/user-header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Edit_User']}</div>
						<div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}accounts/edit-profile-post">
				<input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Username']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="username" name="username" value="{$d['username']}" readonly>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Full_Name']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="fullname" name="fullname" value="{$d['fullname']}">
						</div>
                    </div>
					
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Address']}</label>
						<div class="col-md-6">
							<textarea name="address" id="address"  class="form-control">{$d['address']}</textarea>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Phone_Number']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="phonenumber" name="phonenumber" value="{$d['phonenumber']}">
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
