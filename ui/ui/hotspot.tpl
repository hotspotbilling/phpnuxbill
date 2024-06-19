{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{$_url}services/sync/hotspot"
                        onclick="return confirm('This will sync/send hotspot plan to Mikrotik?')"><span
                            class="glyphicon glyphicon-refresh" aria-hidden="true"></span> sync</a>
                </div>{Lang::T('Hotspot Plans')}
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
                                    placeholder="{Lang::T('Search by Name')}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{$_url}services/add" class="btn btn-primary btn-block"><i class="ion ion-android-add">
                            </i> {Lang::T('New Service Plan')}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th></th>
                                <th colspan="5" class="text-center">{Lang::T('Internet Plan')}</th>
                                <th colspan="2" class="text-center" style="background-color: rgb(246, 244, 244);">Limit</th>
                                <th colspan="2"></th>
                                <th colspan="2" class="text-center" style="background-color: rgb(243, 241, 172);">{Lang::T('Expired')}</th>
                                <th colspan="3"></th>
                            </tr>
                            <tr>
                                <th>{Lang::T('Name')}</th>
                                <th>{Lang::T('Type')}</th>
                                <th>{Lang::T('Bandwidth')}</th>
                                <th>{Lang::T('Category')}</th>
                                <th>{Lang::T('Price')}</th>
                                <th>{Lang::T('Validity')}</th>
                                <th style="background-color: rgb(246, 244, 244);">{Lang::T('Time')}</th>
                                <th style="background-color: rgb(246, 244, 244);">{Lang::T('Data')}</th>
                                <th>{Lang::T('Routers')}</th>
                                <th>{Lang::T('Device')}</th>
                                <th style="background-color: rgb(243, 241, 172);">{Lang::T('Internet Plan')}</th>
                                <th style="background-color: rgb(243, 241, 172);">{Lang::T('Date')}</th>
                                <th>{Lang::T('ID')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr {if $ds['enabled'] !=1}class="danger" title="disabled" {elseif $ds['prepaid'] !='yes'
                                    }class="warning" title="Postpaid" {/if}>
                                    <td class="headcol">{$ds['name_plan']}</td>
                                    <td>{if $ds['prepaid'] == no}<b>Postpaid</b>{else}Prepaid{/if} {$ds['plan_type']}</td>
                                    <td>{$ds['name_bw']}</td>
                                    <td>{$ds['typebp']}</td>
                                    <td>{Lang::moneyFormat($ds['price'])}</td>
                                    <td>{$ds['validity']} {$ds['validity_unit']}</td>
                                    <td>{$ds['time_limit']} {$ds['time_unit']}</td>
                                    <td>{$ds['data_limit']} {$ds['data_unit']}</td>
                                    <td>
                                        {if $ds['is_radius']}
                                            <span class="label label-primary">RADIUS</span>
                                        {else}
                                            {if $ds['routers']!=''}
                                                <a href="{$_url}routers/edit/0&name={$ds['routers']}">{$ds['routers']}</a>
                                            {/if}
                                        {/if}
                                    </td>
                                    <td>{$ds['device']}</td>
                                    <td>{if $ds['plan_expired']}<a
                                            href="{$_url}services/edit/{$ds['plan_expired']}">Yes</a>{else}No
                                            {/if}</td>
                                    <td>{if $ds['prepaid'] == no}{$ds['expired_date']}{/if}</td>
                                    <td>{$ds['id']}</td>
                                    <td>
                                        <a href="{$_url}services/edit/{$ds['id']}"
                                            class="btn btn-info btn-xs">{Lang::T('Edit')}</a>
                                        <a href="{$_url}services/delete/{$ds['id']}" id="{$ds['id']}"
                                            onclick="return confirm('{Lang::T('Delete')}?')"
                                            class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-trash"></i></a>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
                <div class="bs-callout bs-callout-info" id="callout-navbar-role">
                    <h4>Create expired Internet Plan</h4>
                    <p>When customer expired, you can move it to Expired Internet Plan</p>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}