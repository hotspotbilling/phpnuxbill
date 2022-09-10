{include file="sections/header.tpl"}

					<div class="row">
						<div class="col-sm-12">
							<div class="panel panel-hovered mb20 panel-default">
								<div class="panel-heading">{$_L['Routers']}</div>
								<div class="panel-body">
									<div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
										<div class="col-md-8">

											<form id="site-search" method="post" action="{$_url}routers/list/">
											<div class="input-group">
												<div class="input-group-addon">
													<span class="fa fa-search"></span>
												</div>
												<input type="text" name="name" class="form-control" placeholder="{$_L['Search_by_Name']}...">
												<div class="input-group-btn">
													<button class="btn btn-success">{$_L['Search']}</button>
												</div>
											</div>
											</form>
										</div>
										<div class="col-md-4">
											<a href="{$_url}routers/add" class="btn btn-primary btn-block waves-effect"><i class="ion ion-android-add"> </i> {$_L['New_Router']}</a>
										</div>&nbsp;
									</div>
                                    <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{$_L['Router_Name']}</th>
                                                <th>{$_L['IP_Address']}</th>
                                                <th>{$_L['Username']}</th>
                                                <th>{$_L['Description']}</th>
                                                <th>{Lang::T('Status')}</th>
                                                <th>{$_L['Manage']}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        {foreach $d as $ds}
                                            <tr {if $ds['enabled'] != 1}class="danger" title="disabled"{/if}>
                                                <td>{$ds['name']}</td>
                                                <td>{$ds['ip_address']}</td>
                                                <td>{$ds['username']}</td>
                                                <td>{$ds['description']}</td>
                                                <td>{if $ds['enabled'] == 1}Enabled{else}Disabled{/if}</td>
                                                <td>
                                                    <a href="{$_url}routers/edit/{$ds['id']}" class="btn btn-info btn-sm btn-block">{$_L['Edit']}</a>
                                                </td>
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

{include file="sections/footer.tpl"}
