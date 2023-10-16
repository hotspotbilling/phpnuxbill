{include file="sections/header.tpl"}
<!-- pool -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{$_url}pool/sync"
                        onclick="return confirm('This will sync/send IP Pool to Mikrotik?')"><span
                            class="glyphicon glyphicon-refresh" aria-hidden="true"></span> sync</a>
                </div>
                {$_L['Pool']}
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{$_url}pool/list/">
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
                        <a href="{$_url}pool/add" class="btn btn-primary btn-block waves-effect"><i
                                class="ion ion-android-add"> </i> {$_L['New_Pool']}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{$_L['Pool_Name']}</th>
                                <th>{$_L['Range_IP']}</th>
                                <th>{$_L['Routers']}</th>
                                <th>{$_L['Manage']}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$no = 1}
                            {foreach $d as $ds}
                                <tr>
                                    <td align="center">{$no++}</td>
                                    <td>{$ds['pool_name']}</td>
                                    <td>{$ds['range_ip']}</td>
                                    <td>{$ds['routers']}</td>
                                    <td align="center">
                                        <a href="{$_url}pool/edit/{$ds['id']}" class="btn btn-info btn-xs">{$_L['Edit']}</a>
                                        <a href="{$_url}pool/delete/{$ds['id']}" id="{$ds['id']}"
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