{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-primary panel-hovered panel-stacked mb30">
					<div class="panel-heading">{Lang::T('Edit Service Plan')}</div>
						<div class="panel-body">
                        <form class="form-horizontal" method="post" role="form" action="{$_url}services/balance-edit-post">
                        <input type="hidden" name="id" value="{$d['id']}">
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
                                <label class="col-md-2 control-label">{Lang::T('Plan Name')}</label>
                                <div class="col-md-6">
                                    <input type="text" required class="form-control" id="name" value="{$d['name_plan']}" name="name" maxlength="40" placeholder="Topup 100">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-2 control-label">{Lang::T('Plan Price')}</label>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-addon">{$_c['currency_code']}</span>
                                        <input type="number" class="form-control" name="price" value="{$d['price']}" required>
                                    </div>
                                </div>
                                {if $_c['enable_tax'] == 'yes'}
                                {if $_c['tax_rate'] == 'custom'}
                                <p class="help-block col-md-4">{number_format($_c['custom_tax_rate'], 2)} % {Lang::T('Tax Rates
                                    will be added')}</p>
                                {else}
                                <p class="help-block col-md-4">{number_format($_c['tax_rate'] * 100, 2)} % {Lang::T('Tax Rates
                                    will be added')}</p>
                                {/if}
                                {/if}
        
                            </div>

                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <button class="btn btn-success" type="submit">{Lang::T('Save Changes')}</button>
                                    Or <a href="{$_url}services/balance">{Lang::T('Cancel')}</a>
                                </div>
                            </div>
                        </form>
					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
