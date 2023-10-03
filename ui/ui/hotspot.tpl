{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{$_url}services/sync/hotspot"
                        onclick="return confirm('This will sync/send hotspot plan to Mikrotik?')"><span
                            class="glyphicon glyphicon-refresh" aria-hidden="true"></span> sync</a>
                </div>{$_L['Hotspot_Plans']}
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{$_url}services/hotspot/">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="name" class="form-control"
                                    placeholder="{$_L['Search_by_Name']}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{$_L['Search']}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{$_url}services/add" class="btn btn-primary btn-block waves-effect"><i
                                class="ion ion-android-add"> </i> {$_L['New_Plan']}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{$_L['Plan_Name']}</th>
                                <th>{$_L['Plan_Type']}</th>
                                <th>{$_L['Bandwidth_Plans']}</th>
                                <th>{$_L['Plan_Price']}</th>
                                <th>{$_L['Time_Limit']}</th>
                                <th>{$_L['Data_Limit']}</th>
                                <th>{$_L['Plan_Validity']}</th>
                                <th>{$_L['Routers']}</th>
                                <th>{Lang::T('Expired IP Pool')}</th>
                                <th>{$_L['Manage']}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr {if $ds['enabled'] != 1}class="danger" title="disabled" {/if}>
                                    <td>{$ds['name_plan']}</td>
                                    <td>{$ds['typebp']}</td>
                                    <td>{$ds['name_bw']}</td>
                                    <td>{Lang::moneyFormat($ds['price'])}</td>
                                    <td>{$ds['time_limit']} {$ds['time_unit']}</td>
                                    <td>{$ds['data_limit']} {$ds['data_unit']}</td>
                                    <td>{$ds['validity']} {$ds['validity_unit']}</td>
                                    <td>
                                        {if $ds['is_radius']}
                                            <span class="label label-primary">RADIUS</span>
                                        {else}
                                            {if $ds['routers']!=''}
                                                <a href="{$_url}routers/edit/0&name={$ds['routers']}">{$ds['routers']}</a>
                                            {/if}
                                        {/if}
                                    </td>
                                    <td>{$ds['pool_expired']}</td>
                                    <td>
                                        <a href="{$_url}services/edit/{$ds['id']}"
                                            class="btn btn-info btn-xs">{$_L['Edit']}</a>
                                        <a href="{$_url}services/delete/{$ds['id']}" id="{$ds['id']}"
                                            onclick="return confirm('{$_L['Delete']}?')"
                                            class="btn btn-danger btn-xs">{$_L['Delete']}</a>
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