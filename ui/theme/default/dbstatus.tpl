{include file="sections/header.tpl"}

<div class="row">
	<div class="col-sm-12">
		<div class="panel mb20 panel-default">
			<div class="panel-heading">{$_L['Database_Status']}</div>
			<div class="panel-body">
			
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
						<div class="col-md-9">{$_L['Total_Database_Size']}: {$dbsize}  MB </div>
						<div class="col-md-3 text-right">
							<a href="{$_url}settings/dbbackup/" class="btn btn-primary btn-xs"><i class="fa fa-download"></i> {$_L['Download_Database_Backup']}</a>
						</div>&nbsp;
						<div class="table-responsive">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th width="50%">{$_L['Table_Name']}</th>
										<th>{$_L['Rows']}</th>
										<th>{$_L['Size']}</th>
									</tr>
								</thead>
								<tbody>
								{foreach $tables as $tbl}
									<tr>
										<td>{$tbl['name']}</td>
										<td>{$tbl['rows']}</td>
										<td>{$tbl['size']} Kb</td>
									</tr>
								{/foreach}
								</tbody>
							</table>
						</div>
                    </div>
                </div>
				
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}