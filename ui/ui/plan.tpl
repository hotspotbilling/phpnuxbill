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
                    <div class="btn-group pull-right">
                        <a class="btn btn-info btn-xs" title="save" href="{$_url}customers/csv-prepaid"
                            onclick="return confirm('This will export to CSV?')"><span class="glyphicon glyphicon-download"
                                aria-hidden="true"></span> CSV</a>
                    </div>
                {/if}
                &nbsp;
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{$_url}plan/list/">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="search" class="form-control"
                                    placeholder="{Lang::T('Search by Username')}..." value="{$search}">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{$_url}plan/recharge" class="btn btn-primary btn-block"><i
                                class="ion ion-android-add"> </i> {Lang::T('Recharge Account')}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{Lang::T('Username')}</th>
                                <th>{Lang::T('Plan Name')}</th>
                                <th>{Lang::T('Plan Type')}</th>
                                <th>{Lang::T('Type')}</th>
                                <th>{Lang::T('Created On')}</th>
                                <th>{Lang::T('Expires On')}</th>
                                <th>{Lang::T('Method')}</th>
                                <th>{Lang::T('Routers')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr {if $ds['status']=='off'}class="danger" {/if}>
                                    <td><a href="{$_url}customers/viewu/{$ds['username']}">{$ds['username']}</a></td>
                                    <td>{$ds['namebp']}</td>
                                    <td>{$ds['type']}</td>
                                    <td>{$ds['plan_type']}</td>
                                    <td>{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                                    <td>{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                                    <td>{$ds['method']}</td>
                                    <td>{$ds['routers']}</td>
                                    <td>
                                        <a href="{$_url}plan/edit/{$ds['id']}"
                                            class="btn btn-warning btn-xs" style="color: black;">{Lang::T('Edit')}</a>
                                        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                            <a href="{$_url}plan/delete/{$ds['id']}" id="{$ds['id']}"
                                                onclick="return confirm('{Lang::T('Delete')}?')"
                                                class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-trash"></i></a>
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
</div>

<script>
function extend(idP){
    var res = prompt("Extend for many days?", "3");
    if(res){
        if(confirm("Extend for "+res+" days?")){
            window.location.href = "{$_url}plan/extend/"+idP+"/"+res+"&stoken={App::getToken()}";
        }
    }
}
</script>

{include file="sections/footer.tpl"}
