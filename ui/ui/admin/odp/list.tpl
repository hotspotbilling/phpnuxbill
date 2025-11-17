{include file="sections/header.tpl"}
<!-- odp -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {Lang::T('Optical Distribution Point List')}
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{Text::url('')}odp/list/">
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
                        <a href="{Text::url('')}odp/add" class="btn btn-primary btn-block"><i
                                class="ion ion-android-add"> </i> {Lang::T('New odp')}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{Lang::T('Name')}</th>
                                <th>{Lang::T('Port Amount')}</th>
                                <th>{Lang::T('Attenuation')}</th>
                                <th>{Lang::T('Address')}</th>
                                <th>{Lang::T('Covarage')}</th>
                                <th>{Lang::T('Coordinates')}</th>
                                <th>{Lang::T('Action')}</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr>
                                    <td>{$ds['name']}</td>
                                    <td>{$ds['port_amount']}</td>
                                    <td>{$ds['attenuation']}</td>
                                    <td>{$ds['address']}</td>
                                    <td>{$ds['coverage']} m</td>
                                    <td align="center">
                                        {if $ds['coordinates']}
                                        <a href="https://www.google.com/maps/dir//{$ds['coordinates']}/" target="_blank"
                                            class="btn btn-default btn-xs" title="{$ds['coordinates']}"><i
                                                class="glyphicon glyphicon-map-marker"></i></a>
                                        {/if}
                                    </td>
                                    <td align="center">
                                        <a href="{Text::url('')}odp/edit/{$ds['id']}" class="btn btn-primary btn-xs"
                                            title="{Lang::T('Edit')}"><i class="glyphicon glyphicon-edit"></i></a>
                                        <a href="{Text::url('')}odp/delete/{$ds['id']}" class="btn btn-danger btn-xs"
                                            onclick="return ask(this, '{Lang::T('Are you sure you want to delete this ODP?')}')" title="{Lang::T('Delete')}"><i
                                                class="glyphicon glyphicon-trash"></i></a>
                                    </td>
                                    <td>{$ds['id']}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}