{include file="sections/header.tpl"}
<!-- pool -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                Radius
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{$_url}radius/nas-list">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="name" class="form-control" value="{$name}"
                                    placeholder="{$_L['Search_by_Name']}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{$_L['Search']}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{$_url}radius/nas-add" class="btn btn-primary btn-block waves-effect"><i
                                class="ion ion-android-add"> </i> New NAS</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>IP</th>
                                <th>Type</th>
                                <th>Port</th>
                                <th>Server</th>
                                <th>Community</th>
                                <th>Routers</th>
                                <th>{$_L['Manage']}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $nas as $ds}
                                <tr>
                                    <td align="center">{$ds['id']}</td>
                                    <td>{$ds['shortname']}</td>
                                    <td>{$ds['nasname']}</td>
                                    <td>{$ds['type']}</td>
                                    <td>{$ds['ports']}</td>
                                    <td>{$ds['server']}</td>
                                    <td>{$ds['community']}</td>
                                    <td>{$ds['routers']}</td>
                                    <td align="center">
                                        <a href="{$_url}radius/nas-edit/{$ds['id']}" class="btn btn-info btn-xs">{$_L['Edit']}</a>
                                        <a href="{$_url}radius/nas-delete/{$ds['id']}" id="{$ds['id']}"
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