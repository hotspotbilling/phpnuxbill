{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-primary panel-hovered panel-stacked mb30">
					<div class="panel-heading">{Lang::T('Add Port Pool')}</div>
						<div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}pool/add-port-post" >
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Port Name')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="name" name="name" placeholder="Vpn Tunnel">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Public IP')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="public_ip" name="public_ip" placeholder="12.34.56.78">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Range Port')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="port_range" name="port_range" placeholder="	3000-8000">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label"><a href="{$_url}routers/add">{Lang::T('Routers')}</a></label>
						<div class="col-md-6">
							<select id="routers" name="routers" class="form-control select2">
                                {foreach $r as $rs}
									<option value="{$rs['name']}">{$rs['name']}</option>
                                {/foreach}
                            </select>
						</div>
                    </div>
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-primary" type="submit">{Lang::T('Save Changes')}</button>
							Or <a href="{$_url}pool/port">{Lang::T('Cancel')}</a>
						</div>
					</div>
                </form>

					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
