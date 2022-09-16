{include file="sections/user-header.tpl"}

					<div class="row">
						<div class="col-sm-12">
							<div class="panel mb20 panel-hovered panel-default">
								<div class="panel-heading">{$_L['List_Activated_Voucher']}</div>
								<div class="panel-body">
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>{$_L['Username']}</th>
                                                    <th>{$_L['Plan_Name']}</th>
                                                    <th>{$_L['Plan_Price']}</th>
                                                    <th>{$_L['Type']}</th>
                                                    <th>{$_L['Created_On']}</th>
                                                    <th>{$_L['Expires_On']}</th>
                                                    <th>{$_L['Method']}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            {foreach $d as $ds}
                                                <tr>
                                                    <td>{$ds['username']}</td>
                                                    <td>{$ds['plan_name']}</td>
                                                    <td>{$_c['currency_code']} {number_format($ds['price'],2,$_c['dec_point'],$_c['thousands_sep'])}</td>
                                                    <td>{$ds['type']}</td>
                                                    <td class="text-success">{date($_c['date_format'], strtotime($ds['recharged_on']))} {$ds['time']}</td>
                                                    <td class="text-danger">{date($_c['date_format'], strtotime($ds['expiration']))} {$ds['time']}</td>
                                                    <td>{$ds['method']}</td>
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
