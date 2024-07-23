{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <div class="btn-group pull-right">
                        <a class="btn btn-primary btn-xs" title="save" href="{$_url}plan/sync"
                            onclick="return confirm('This will sync/send Caustomer active plan to Mikrotik?')"><span
                                class="glyphicon glyphicon-refresh" aria-hidden="true"></span> sync</a>
                    </div>
                    {* <div class="btn-group pull-right">
                        <a class="btn btn-info btn-xs" title="save" href="{$_url}plan/csv{$append_url}"
                            onclick="return confirm('This will export to CSV?')"><span class="glyphicon glyphicon-download"
                                aria-hidden="true"></span> CSV</a>
                    </div> *}
                {/if}
                &nbsp;
            </div>
            <form id="site-search" method="post" action="{$_url}plan/list/">
                <div class="panel-body">
                    <div class="row row-no-gutters" style="padding: 5px">
                        <div class="col-lg-2">
                            <div class="input-group">
                                <div class="input-group-btn">
                                    <a class="btn btn-danger" title="Clear Search Query" href="{$_url}plan/list"><span
                                            class="glyphicon glyphicon-remove-circle"></span></a>
                                </div>
                                <input type="text" name="search" class="form-control"
                                    placeholder="{Lang::T('Search')}..." value="{$search}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <select class="form-control" id="router" name="router">
                                <option value="">{Lang::T('Location')}</option>
                                {foreach $routers as $r}
                                    <option value="{$r}" {if $router eq $r }selected{/if}>{$r}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <select class="form-control" id="plan" name="plan">
                                <option value="">{Lang::T('Plan Name')}</option>
                                {foreach $plans as $p}
                                    <option value="{$p['id']}" {if $plan eq $p['id'] }selected{/if}>{$p['name_plan']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <select class="form-control" id="status" name="status">
                                <option value="-">{Lang::T('Status')}</option>
                                <option value="on" {if $status eq 'on' }selected{/if}>{Lang::T('Active')}</option>
                                <option value="off" {if $status eq 'off' }selected{/if}>{Lang::T('Expired')}</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-xs-6">
                            <button class="btn btn-success btn-block" type="submit"><span
                                    class="fa fa-search"></span></button>
                        </div>
                        <div class="col-md-2 col-xs-6">
                            <a href="{$_url}plan/recharge" class="btn btn-primary btn-block"><i
                                    class="ion ion-android-add">
                                </i> {Lang::T('Recharge Account')}</a>
                        </div>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table id="datatable" class="table table-bordered table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>{Lang::T('Username')}</th>
                            <th>{Lang::T('Plan Name')}</th>
                            <th>{Lang::T('Type')}</th>
                            <th>{Lang::T('Created On')}</th>
                            <th>{Lang::T('Expires On')}</th>
                            <th>{Lang::T('Method')}</th>
                            <th><a href="{$_url}routers/list">{Lang::T('Location')}</a></th>
                            <th>{Lang::T('Manage')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $d as $ds}
                            <tr {if $ds['status']=='off'}class="danger" {/if}>
                                <td>
                                    {if $ds['customer_id'] == '0'}
                                        <a href="{$_url}plan/voucher/&search={$ds['username']}">{$ds['username']}</a>
                                    {else}
                                        <a href="{$_url}customers/viewu/{$ds['username']}">{$ds['username']}</a>
                                    {/if}
                                </td>
                                {if $ds['type'] == 'Hotspot'}
                                    <td><a href="{$_url}services/edit/{$ds['plan_id']}">{$ds['namebp']}</a></td>
                                {else}
                                    <td><a href="{$_url}services/pppoe-edit/{$ds['plan_id']}">{$ds['namebp']}</a></td>
                                {/if}
                                <td>{$ds['type']}</td>
                                <td>{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                                <td>{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                                <td>{$ds['method']}</td>
                                <td>{$ds['routers']}</td>
                                <td>
                                    <a href="{$_url}plan/edit/{$ds['id']}" class="btn btn-warning btn-xs"
                                        style="color: black;">{Lang::T('Edit')}</a>
                                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                        <a href="{$_url}plan/delete/{$ds['id']}" id="{$ds['id']}"
                                            onclick="return confirm('{Lang::T('Delete')}?')" class="btn btn-danger btn-xs"><i
                                                class="glyphicon glyphicon-trash"></i></a>
                                    {/if}
                                    {if $ds['status']=='off' && $_c['extend_expired']}
                                        <a href="javascript:extend('{$ds['id']}')"
                                            class="btn btn-info btn-xs">{Lang::T('Extend')}</a>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {include file="pagination.tpl"}
        </div>
    </div>
</div>

<script>
    function extend(idP) {
        var res = prompt("Extend for many days?", "3");
        if (res) {
            if (confirm("Extend for " + res + " days?")) {
                window.location.href = "{$_url}plan/extend/"+idP+"/"+res+"&stoken={App::getToken()}";
            }
        }
    }
</script>

{include file="sections/footer.tpl"}