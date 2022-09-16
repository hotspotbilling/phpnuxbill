{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Localisation']}</div>
						<div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}settings/localisation-post" >
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Timezone']}</label>
						<div class="col-md-6">
							<select name="tzone" id="tzone" class="form-control">
								{foreach $tlist as $value => $label}
									<option value="{$value}" {if $_c['timezone'] eq $value}selected="selected" {/if}>{$label}</option>
								{/foreach}
							</select>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Date_Format']}</label>
						<div class="col-md-6">
							<select class="form-control" name="date_format" id="date_format">
								<option value="d/m/Y" {if $_c['date_format'] eq 'd/m/Y'} selected="selected" {/if}>{date('d/m/Y')}</option>
								<option value="d.m.Y" {if $_c['date_format'] eq 'd.m.Y'} selected="selected" {/if}>{date('d.m.Y')}</option>
								<option value="d-m-Y" {if $_c['date_format'] eq 'd-m-Y'} selected="selected" {/if}>{date('d-m-Y')}</option>
								<option value="m/d/Y" {if $_c['date_format'] eq 'm/d/Y'} selected="selected" {/if}>{date('m/d/Y')}</option>
								<option value="Y/m/d" {if $_c['date_format'] eq 'Y/m/d'} selected="selected" {/if}>{date('Y/m/d')}</option>
								<option value="Y-m-d" {if $_c['date_format'] eq 'Y-m-d'} selected="selected" {/if}>{date('Y-m-d')}</option>
								<option value="M d Y" {if $_c['date_format'] eq 'M d Y'} selected="selected" {/if}>{date('M d Y')}</option>
								<option value="d M Y" {if $_c['date_format'] eq 'd M Y'} selected="selected" {/if}>{date('d M Y')}</option>
								<option value="jS M y" {if $_c['date_format'] eq 'jS M y'} selected="selected" {/if}>{date('jS M y')}</option>
							</select>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Default_Language']}</label>
						<div class="col-md-4">
							<select class="form-control" name="lan" id="lan">
								{foreach $lan as $lans}
									<option value="{$lans['folder']}" {if $_c['language'] eq $lans['folder']} selected="selected" {/if}>{$lans['name']}</option>
                                {/foreach}
							</select>
						</div>
						<div class="col-md-2">
							<a href="{$_url}settings/language" type="button" class="btn btn-line-success btn-icon-inline"><i class="ion ion-android-add"></i>{$_L['Add_Language']}</a>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Decimal_Point']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="dec_point" name="dec_point" value="{$_c['dec_point']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Thousands_Separator']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="thousands_sep" name="thousands_sep" value="{$_c['thousands_sep']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Currency_Code']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="currency_code" name="currency_code" value="{$_c['currency_code']}">
							<span class="help-block">{$_L['currency_help']}</span>
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
