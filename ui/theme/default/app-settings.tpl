{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['General_Settings']}</div>
						<div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}settings/app-post" >            
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['App_Name']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="company" name="company" value="{$_c['CompanyName']}">
							<span class="help-block">{$_L['App_Name_Help_Text']}</span>
						</div>
                    </div>
					<div class="form-group">
						<label class="col-md-2 control-label">{$_L['Address']}</label>
						<div class="col-md-6">
							<textarea class="form-control" id="address" name="address" rows="3">{$_c['address']}</textarea>
							<span class="help-block">{$_L['You_can_use_html_tag']}</span>
						</div>
                    </div>
					<div class="form-group">
						<label class="col-md-2 control-label">{$_L['Phone_Number']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="phone" name="phone" value="{$_c['phone']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">Theme</label>
						<div class="col-md-6">
							<select name="theme" id="theme" class="form-control">
								<option value="default" {if $_c['theme'] eq 'default'}selected="selected" {/if}>Default</option>
								<option value="blue" {if $_c['theme'] eq 'blue'}selected="selected" {/if}>Blue</option>
							</select>
						</div>
                    </div>
					<div class="form-group">
						<label class="col-md-2 control-label">Note Invoice</label>
						<div class="col-md-6">
							<textarea class="form-control" id="note" name="note" rows="3">{$_c['note']}</textarea>
							<span class="help-block">{$_L['You_can_use_html_tag']}</span>
						</div>
                    </div>
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
						</div>
					</div>
                </form>
				
					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
