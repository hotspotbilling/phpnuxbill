{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-primary panel-hovered panel-stacked mb30">
					<div class="panel-heading">{Lang::T('Edit Port')}</div>
						<div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}pool/edit-port-post" >
				<input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Port Name')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="name" name="name" value="{$d['port_name']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Public IP')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="public_ip" name="public_ip" value="{$d['public_ip']}" placeholder="12.34.56.78">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Range Port')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="range_port" name="range_port" value="{$d['range_port']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Routers')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="routers" name="routers" value="{$d['routers']}" readonly>
						</div>
                    </div>

					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-success" onclick="return confirm('Continue the Port change process?')" type="submit">{Lang::T('Save Changes')}</button>
							Or <a href="{$_url}pool/port">{Lang::T('Cancel')}</a>
						</div>
					</div>
                </form>

					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
