{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-default panel-hovered panel-stacked mb30">
					<div class="panel-heading">{$_L['Add_Router']}</div>
						<div class="panel-body">

                    <form class="form-horizontal" method="post" role="form" action="{$_url}routers/add-post" >
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                            <div class="col-md-10">
                                <label class="radio-inline warning">
                                    <input type="radio" checked name="enabled" value="1"> Enable
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="enabled" value="0"> Disable
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Router_Name']}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="name" name="name" maxlength="32">
                                <p class="help-block">{Lang::T('Name of Area that router operated')}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['IP_Address']}</label>
                            <div class="col-md-6">
                                <input type="text" placeholder="192.168.88.1:8728" class="form-control" id="ip_address" name="ip_address">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Username']}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="username" name="username">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Router_Secret']}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Description']}</label>
                            <div class="col-md-6">
                                <textarea class="form-control" id="description" name="description"></textarea>
                                <p class="help-block">{Lang::T('Explain Coverage of router')}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-offset-2 col-lg-10">
                                <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                                Or <a href="{$_url}routers/list">{$_L['Cancel']}</a>
                            </div>
                        </div>
                    </form>

					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
