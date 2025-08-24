{include file="sections/header.tpl"}
<style>
    /* Styles for overall layout and responsiveness */
    body {
        background-color: #f8f9fa;
        font-family: 'Arial', sans-serif;
    }

    .container {
        margin-top: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }


    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table th {
        vertical-align: middle;
        border-color: #dee2e6;
        background-color: #343a40;
        color: #fff;
    }

    .table td {
        vertical-align: middle;
        border-color: #dee2e6;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .badge {
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        border-radius: 4px;
        transition: background-color 0.3s, color 0.3s;
    }

    .badge-danger {
        color: #721c24;
        background-color: #f8d7da;
    }

    .badge-success {
        color: #155724;
        background-color: #d4edda;
    }

    .badge-warning {
        color: #856404;
        background-color: #ffeeba;
    }

    .badge-info {
        color: #0c5460;
        background-color: #d1ecf1;
    }

    .badge:hover {
        opacity: 0.8;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{Text::url('logs/message-csv')}"
                        onclick="return ask(this, '{Lang::T('This will export to CSV')}?')"><span
                            class="glyphicon glyphicon-download" aria-hidden="true"></span> CSV</a>
                </div>
                {/if}
                {Lang::T('Message Log')}
            </div>
            <div class="panel-body">
                <div class="text-center" style="padding: 15px">
                    <div class="col-md-4">
                        <form id="site-search" method="post" action="{Text::url('logs/message/')}">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="q" class="form-control" value="{$q}"
                                    placeholder="{Lang::T('Search')}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <form class="form-inline" method="post" action="{Text::url('')}logs/message/">
                            <div class="input-group has-error">
                                <span class="input-group-addon">{Lang::T('Keep Logs')} </span>
                                <input type="text" name="keep" class="form-control" placeholder="90" value="90">
                                <span class="input-group-addon">{Lang::T('Days')}</span>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return ask(this, '{Lang::T("
                                Clear old logs?")}')">{Lang::T('Clean up Logs')}</button>
                        </form>
                    </div>&nbsp;
                </div>
                <br>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>{Lang::T('ID')}</th>
                                <th>{Lang::T('Date Sent')}</th>
                                <th>{Lang::T('Type')}</th>
                                <th>{Lang::T('Status')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if $d} {foreach $d as $ds}
                            <tr>
                                <td>{$ds['id']}</td>
                                <td>{Lang::dateTimeFormat($ds['sent_at'])}</td>
                                <td>{$ds['message_type']}</td>
                                <td>
                                    {if $ds['status'] == 'Success'}
                                    <span class="badge badge-success"> {$ds['status']} </span>
                                    {else}
                                    <span class="badge badge-danger"> {$ds['status']} </span>
                                    {/if}
                                </td>
                            </tr>
                            {if $ds['message_content']}
                            <tr>
                                <td colspan="4" style="text-align: center;" style="overflow-x: scroll;">
                                    {nl2br($ds['message_content'])}</td>
                            </tr>
                            {/if}
                            {if $ds['error_message']}
                            <tr>
                                <td colspan="4" style="text-align: center;" style="overflow-x: scroll;">
                                    {nl2br($ds['error_message'])}</td>
                            </tr>
                            {/if}
                            {/foreach}{else}
                            <tr>
                                <td colspan="4" style="text-align: center;">
                                    {Lang::T('No logs found.')}
                                </td>
                            </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}