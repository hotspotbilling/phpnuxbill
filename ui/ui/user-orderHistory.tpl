{include file="sections/user-header.tpl"}

					<div class="row">
						<div class="col-sm-12">
							<div class="panel mb20 panel-hovered panel-default">
								<div class="panel-heading">{$_L['Order_History']}</div>
								<div class="panel-body">
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>{$_L['Plan_Name']}</th>
                                                    <th>{Lang::T('Gateway')}</th>
                                                    <th>{Lang::T('Routers')}</th>
                                                    <th>{$_L['Type']}</th>
                                                    <th>{$_L['Plan_Price']}</th>
                                                    <th>{$_L['Created_On']}</th>
                                                    <th>{$_L['Expires_On']}</th>
                                                    <th>{Lang::T('Date Done')}</th>
                                                    <th>{$_L['Method']}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            {foreach $d as $ds}
                                                <tr>
                                                    <td><a href="{$_url}order/view/{$ds['id']}">{$ds['plan_name']}</a></td>
                                                    <td>{$ds['gateway']}</td>
                                                    <td>{$ds['routers']}</td>
                                                    <td>{$ds['payment_channel']}</td>
                                                    <td>{number_format($ds['price'],2,$_c['dec_point'],$_c['thousands_sep'])}</td>
                                                    <td class="text-primary">{date("{$_c['date_format']} H:i", strtotime($ds['created_date']))}</td>
                                                    <td class="text-danger">{date("{$_c['date_format']} H:i", strtotime($ds['expired_date']))}</td>
                                                    <td class="text-success">{if $ds['status']!=1}{date("{$_c['date_format']} H:i", strtotime($ds['paid_date']))}{/if}</td>
                                                    <td>{if $ds['status']==1}{$_L['UNPAID']}
                                                    {elseif $ds['status']==2}{$_L['PAID']}
                                                    {elseif $ds['status']==3}{$_L['FAILED']}
                                                    {elseif $ds['status']==4}{$_L['CANCELED']}
                                                    {elseif $ds['status']==5}{$_L['UNKNOWN']}{/if}</td>
                                                </tr>
                                            {/foreach}
                                            </tbody>
                                        </table>
                                    </div>
									{$paginator['contents']}
								</div>
							</div>
						</div>
					</div>


{include file="sections/user-footer.tpl"}
