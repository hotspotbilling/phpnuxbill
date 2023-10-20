{include file="sections/header.tpl"}
<!-- pool -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                Activity Log
            </div>
            <div class="panel-body">
                <div class="text-center" style="padding: 15px">
                    <div class="col-md-4">
                        <form id="site-search" method="post" action="{$_url}reports/activation">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="q" class="form-control" value="{$q}"
                                    placeholder="{$_L['Invoice']}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{$_L['Search']}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8">

                    </div>&nbsp;
                </div>
                <br>
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{$_L['Invoice']}</th>
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
                            {foreach $activation as $ds}
                                <tr>
                                    <td onclick="window.location.href = '{$_url}prepaid/view/{$ds['id']}'"
                                    style="cursor:pointer;">{$ds['invoice']}</td>
                                    <td onclick="window.location.href = '{$_url}customers/viewu/{$ds['username']}'"
                                    style="cursor:pointer;">{$ds['username']}</td>
                                    <td>{$ds['plan_name']}</td>
                                    <td>{Lang::moneyFormat($ds['price'])}</td>
                                    <td>{$ds['type']}</td>
                                    <td class="text-success">
                                        {Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}
                                    </td>
                                    <td class="text-danger">{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
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

{include file="sections/footer.tpl"}