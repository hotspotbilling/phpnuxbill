{include file="sections/header.tpl"}

<div class="row">
	<div class="col-sm-12 col-md-12">
		<div class="panel panel-primary panel-hovered panel-stacked mb30">
			<div class="panel-heading">{Lang::T('Edit Bandwidth')}</div>
			<div class="panel-body">

				<form class="form-horizontal" method="post" role="form" action="{$_url}bandwidth/edit-post">
					<input type="hidden" name="id" value="{$d['id']}">
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Bandwidth Name')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="name" name="name" value="{$d['name_bw']}">
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Rate Download')}</label>
						<div class="col-md-4">
							<input type="text" class="form-control" id="rate_down" name="rate_down"
								value="{$d['rate_down']}">
						</div>
						<div class="col-md-2">
							<select class="form-control" id="rate_down_unit" name="rate_down_unit">
								<option value="Kbps" {if $d['rate_down_unit'] eq 'Kbps'}selected="selected" {/if}>Kbps
								</option>
								<option value="Mbps" {if $d['rate_down_unit'] eq 'Mbps'}selected="selected" {/if}>Mbps
								</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Rate Upload')}</label>
						<div class="col-md-4">
							<input type="text" class="form-control" id="rate_up" name="rate_up" value="{$d['rate_up']}">
						</div>
						<div class="col-md-2">
							<select class="form-control" id="rate_up_unit" name="rate_up_unit">
								<option value="Kbps" {if $d['rate_up_unit'] eq 'Kbps'}selected="selected" {/if}>Kbps
								</option>
								<option value="Mbps" {if $d['rate_up_unit'] eq 'Mbps'}selected="selected" {/if}>Mbps
								</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">Burst Limit</label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="burst[]" placeholder="[Burst/Limit]" value="{$burst[0]}">
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">Burst Threshold</label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="burst[]" placeholder="[Burst/Threshold]" value="{$burst[1]}">
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">Burst Time</label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="burst[]" placeholder="[Burst/Time]" value="{$burst[2]}">
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">Priority</label>
						<div class="col-md-6">
							<input type="number" class="form-control" name="burst[]" placeholder="[Priority]" value="{$burst[3]}">
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">Limit At</label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="burst[]" placeholder="[Limit/At]" value="{$burst[4]}">
						</div>
					</div>
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
					<small>{Lang::T('Editing Bandwidth will not automatically update the plan, you need to edit the plan then save again')}</small>
						</div>
					</div>
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-primary"
								type="submit">{Lang::T('Submit')}</button>
							Or <a href="{$_url}bandwidth/list">{Lang::T('Cancel')}</a>
						</div>
					</div>
				</form>

			</div>
		</div>
	</div>
</div>

{include file="sections/footer.tpl"}