{include file="sections/header.tpl"}
<!-- port -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{Text::url('')}pool/sync"
                        onclick="return ask(this, 'This will sync/send IP port to Mikrotik?')"><span
                            class="glyphicon glyphicon-refresh" aria-hidden="true"></span> sync</a>
                </div>
                {Lang::T('Port Pool')} - VPN Tunnels
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{Text::url('')}pool/port/">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="name" class="form-control"
                                    placeholder="{Lang::T('Search by Name')}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{Text::url('')}pool/add-port" class="btn btn-primary btn-block"><i
                                class="ion ion-android-add"> </i> {Lang::T('New port')}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{Lang::T('Port Name')}</th>
                                <th>{Lang::T('Public IP')}</th>
                                <th>{Lang::T('Range Port')}</th>
                                <th>{Lang::T('Routers')}</th>
                                <th>{Lang::T('Manage')}</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr>
                                    <td>{$ds['port_name']}</td>
                                    <td>{$ds['public_ip']}</td>
                                    <td>{$ds['range_port']}</td>
                                    <td>{$ds['routers']}</td>
                                    <td align="center">
                                        <a href="{Text::url('')}pool/edit-port/{$ds['id']}" class="btn btn-info btn-xs">{Lang::T('Edit')}</a>
                                        <a href="{Text::url('')}pool/delete-port/{$ds['id']}" id="{$ds['id']}"
                                            onclick="return ask(this, '{Lang::T('Delete')}?')"
                                            class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-trash"></i></a>
                                    </td>
                                    <td>{$ds['id']}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
				<div class="bs-callout bs-callout-info" id="callout-navbar-role">
					<h4>{Lang::T('Create expired Internet Plan')}</h4>
					<p>{Lang::T('When customer expired, you can move it to Expired Internet Plan')}</p>
				</div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
