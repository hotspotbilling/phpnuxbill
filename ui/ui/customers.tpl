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
                <form id="site-search" method="post" action="{$_url}customers">
                    <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                        <div class="col-lg-4">
                            <div class="input-group">
                                <span class="input-group-addon">Order &nbsp;&nbsp;</span>
                                <div class="row row-no-gutters">
                                    <div class="col-xs-8">
                                        <select class="form-control" id="order" name="order">
                                            <option value="username" {if $order eq 'username' }selected{/if}>{Lang::T('Username')}</option>
                                            <option value="created_at" {if $order eq 'created_at' }selected{/if}>{Lang::T('Created Date')}</option>
                                            <option value="balance" {if $order eq 'balance' }selected{/if}>{Lang::T('Balance')}</option>
                                            <option value="status" {if $order eq 'status' }selected{/if}>{Lang::T('Status')}</option>
                                        </select>
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control" id="orderby" name="orderby">
                                            <option value="asc" {if $orderby eq 'asc' }selected{/if}>{Lang::T('Ascending')}</option>
                                            <option value="desc" {if $orderby eq 'desc' }selected{/if}>{Lang::T('Descending')}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="input-group">
                                <span class="input-group-addon">Status</span>
                                <select class="form-control" id="filter" name="filter">
                                    {foreach $statuses as $status}
                                        <option value="{$status}" {if $filter eq $status }selected{/if}>{Lang::T($status)}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="search" class="form-control"
                                    placeholder="{Lang::T('Search')}..." value="{$search}">
                                <div class="input-group-btn">
                                    <button class="btn btn-primary" type="submit"><span class="fa fa-search"></span></button>
                                    <button class="btn btn-primary" type="submit" name="export" value="csv">
                                        <span class="glyphicon glyphicon-download"
                                        aria-hidden="true"></span> CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <a href="{$_url}customers/add" class="btn btn-success text-black btn-block" title="{Lang::T('Add')}">
                            <i class="ion ion-android-add"></i><i class="glyphicon glyphicon-user"></i>
                            </a>
                        </div>
                    </div>
                </form>
                <br>&nbsp;
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
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Created On')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr {if $ds['status'] != 'Active'}class="danger"{/if}>
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
                                    <td>{Lang::T($ds['status'])}</td>
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

    $j(document).ready(function() {
        $j('#customerTable').DataTable({
            order: [[{$order_pos}, '{$orderby}']],
            "pagingType": "full_numbers",
            "lengthMenu": [
                [5, 10, 25, 50, 100, -1],
                [5, 10, 25, 50, 100, "All"]
            ],
            "pageLength": 25
        });
    });
</script>
{include file="sections/footer.tpl"}