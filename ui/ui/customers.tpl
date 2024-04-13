{include file="sections/header.tpl"}
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        display: inline-block;
        padding: 5px 10px;
        margin-right: 5px;
        border: 1px solid #ccc;
        background-color: #fff;
        color: #333;
        cursor: pointer;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{$_url}customers/csv"
                        onclick="return confirm('This will export to CSV?')"><span class="glyphicon glyphicon-download"
                            aria-hidden="true"></span> CSV</a>
                </div>
                {/if}
                {Lang::T('Manage Contact')}
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">

                    </div>
                    <div class="col-md-4">
                        <a href="{$_url}customers/add" class="btn btn-primary btn-block"><i class="ion ion-android-add">
                            </i> {Lang::T('Add New Contact')}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive table_mobile">
                    <table id="customerTable" class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{Lang::T('Username')}</th>
                                <th>{Lang::T('Account Type')}</th>
                                <th>{Lang::T('Full Name')}</th>
                                <th>{Lang::T('Balance')}</th>
                                <th>{Lang::T('Contact')}</th>
                                <th>{Lang::T('Package')}</th>
                                <th>{Lang::T('Service Type')}</th>
                                <th>{Lang::T('Created On')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                            <tr>
                                <td onclick="window.location.href = '{$_url}customers/view/{$ds['id']}'"
                                    style="cursor:pointer;">{$ds['username']}</td>
                                <td>{$ds['account_type']}</td>
                                <td onclick="window.location.href = '{$_url}customers/view/{$ds['id']}'"
                                    style="cursor: pointer;">{$ds['fullname']}</td>
                                <td>{Lang::moneyFormat($ds['balance'])}</td>
                                <td align="center">
                                    {if $ds['phonenumber']}
                                    <a href="tel:{$ds['phonenumber']}" class="btn btn-default btn-xs"
                                        title="{$ds['phonenumber']}"><i class="glyphicon glyphicon-earphone"></i></a>
                                    {/if}
                                    {if $ds['email']}
                                    <a href="mailto:{$ds['email']}" class="btn btn-default btn-xs"
                                        title="{$ds['email']}"><i class="glyphicon glyphicon-envelope"></i></a>
                                    {/if}
                                    {if $ds['coordinates']}
                                    <a href="https://www.google.com/maps/dir//{$ds['coordinates']}/" target="_blank"
                                        class="btn btn-default btn-xs" title="{$ds['coordinates']}"><i
                                            class="glyphicon glyphicon-map-marker"></i></a>
                                    {/if}
                                </td>
                                <td align="center" api-get-text="{$_url}autoload/customer_is_active/{$ds['id']}">
                                    <span class="label label-default">&bull;</span>
                                </td>
                                <td>{$ds['service_type']}</td>
                                <td>{Lang::dateTimeFormat($ds['created_at'])}</td>
                                <td align="center">
                                    <a href="{$_url}customers/view/{$ds['id']}" id="{$ds['id']}"
                                        style="margin: 0px; color:black"
                                        class="btn btn-success btn-xs">&nbsp;&nbsp;{Lang::T('View')}&nbsp;&nbsp;</a>
                                    <a href="{$_url}customers/edit/{$ds['id']}" id="{$ds['id']}"
                                        style="margin: 0px; color:black"
                                        class="btn btn-info btn-xs">&nbsp;&nbsp;{Lang::T('Edit')}&nbsp;&nbsp;</a>
                                    <a href="{$_url}plan/recharge/{$ds['id']}" id="{$ds['id']}" style="margin: 0px;"
                                        class="btn btn-primary btn-xs">{Lang::T('Recharge')}</a>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script>
    var $j = jQuery.noConflict();

    $j(document).ready(function () {
        $j('#customerTable').DataTable({
            "pagingType": "full_numbers",
            "lengthMenu": [ [5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"] ],
            "pageLength": 5
        });
    });
</script>
{include file="sections/footer.tpl"}