{include file="sections/header.tpl"}

					<div class="row">
						<div class="col-sm-12">
							<div class="panel panel-hovered mb20 panel-default">
								<div class="panel-heading">{$_L['Bandwidth_Plans']}</div>
								<div class="panel-body">
									<div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
										<div class="col-md-8">
											<form id="site-search" method="post" action="{$_url}bandwidth/list/">
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
											<a href="{$_url}bandwidth/add" class="btn btn-primary btn-block waves-effect"><i class="ion ion-android-add"> </i> {$_L['New_Bandwidth']}</a>
										</div>&nbsp;
									</div>
									<div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>{$_L['BW_Name']}</th>
                                                    <th>{$_L['Rate_Download']}</th>
                                                    <th>{$_L['Rate_Upload']}</th>
                                                    <th>{$_L['Manage']}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            {foreach $d as $ds}
                                                <tr>
                                                    <td>{$ds['name_bw']}</td>
                                                    <td>{$ds['rate_down']} {$ds['rate_down_unit']}</td>
                                                    <td>{$ds['rate_up']} {$ds['rate_up_unit']}</td>
                                                    <td>
                                                        <a href="{$_url}bandwidth/edit/{$ds['id']}" class="btn btn-sm btn-warning">{$_L['Edit']}</a>
                                                        <a href="{$_url}bandwidth/delete/{$ds['id']}" id="{$ds['id']}" class="btn btn-danger btn-sm cdelete">{$_L['Delete']}</a>
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
			</div>

{include file="sections/footer.tpl"}