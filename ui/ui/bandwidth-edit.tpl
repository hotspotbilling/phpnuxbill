{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Edit_Bandwidth']}</div>
						<div class="panel-body">
						
                <form class="form-horizontal" method="post" role="form" action="{$_url}bandwidth/edit-post" >
				<input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['BW_Name']}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="name" name="name" value="{$d['name_bw']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Rate_Download']}</label>
						<div class="col-md-4">
							<input type="text" class="form-control" id="rate_down" name="rate_down" value="{$d['rate_down']}">
						</div>
						<div class="col-md-2">
							<select class="form-control" id="rate_down_unit" name="rate_down_unit">
								<option value="Kbps" {if $d['rate_down_unit'] eq 'Kbps'}selected="selected" {/if}>Kbps</option>
                                <option value="Mbps" {if $d['rate_down_unit'] eq 'Mbps'}selected="selected" {/if}>Mbps</option>
							</select>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{$_L['Rate_Upload']}</label>
						<div class="col-md-4">
							<input type="text" class="form-control" id="rate_up" name="rate_up" value="{$d['rate_up']}">
						</div>
						<div class="col-md-2">
							<select class="form-control" id="rate_up_unit" name="rate_up_unit">
								<option value="Kbps" {if $d['rate_up_unit'] eq 'Kbps'}selected="selected" {/if}>Kbps</option>
                                <option value="Mbps" {if $d['rate_up_unit'] eq 'Mbps'}selected="selected" {/if}>Mbps</option>
							</select>
						</div>
                    </div>
					
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Submit']}</button>
							Or <a href="{$_url}bandwidth/list">{$_L['Cancel']}</a>
						</div>
					</div>
                </form>
				
						</div>
					</div>
				</div>
			</div>

{include file="sections/footer.tpl"}
