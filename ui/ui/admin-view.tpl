{include file="sections/header.tpl"}
<!-- user-edit -->

<form class="form-horizontal">
    <div class="row">
        {if $d['user_type'] == "Sales"}<div class="col-sm-6 col-md-6">{else}<div class="col-md-6 col-md-offset-3">{/if}
                <div
                    class="panel panel-{if $d['status'] != 'Active'}danger{else}primary{/if} panel-hovered panel-stacked mb30">
                    <div class="panel-heading">{$d['fullname']}</div>
                    <div class="panel-body">
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item">
                                <b>{Lang::T('Username')}</b> <span class="pull-right">{$d['username']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Phone Number')}</b> <span class="pull-right">{$d['phone']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Email')}</b> <span class="pull-right">{$d['email']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('City')}</b> <span class="pull-right">{$d['city']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Sub District')}</b> <span class="pull-right">{$d['subdistrict']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Ward')}</b> <span class="pull-right">{$d['ward']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('User Type')}</b> <span class="pull-right">{$d['user_type']}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="panel-footer">
                        <center><a href="{$_url}settings/users-edit/{$d['id']}"
                                class="btn btn-info btn-block">{Lang::T('Edit')}</a>
                            <a href="{$_url}settings/users" class="btn btn-link btn-block">{Lang::T('Cancel')}</a>
                        </center>
                    </div>
                </div>
            </div>
            {if $d['user_type'] == "Sales" && $d['root'] neq ''}
                <div class="col-sm-6 col-md-6">
                    <div class="panel panel-success">
                        <div class="panel-heading">Agent - {$agent['fullname']}</div>
                        <div class="panel-body">
                            <ul class="list-group list-group-unbordered">
                                <li class="list-group-item">
                                    <b>{Lang::T('Phone Number')}</b> <span class="pull-right"><a href="tel:{$agent['phone']}">{$agent['phone']}</a></span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Email')}</b> <span class="pull-right"><a href="mailto:{$agent['email']}">{$agent['email']}</a></span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('City')}</b> <span class="pull-right">{$agent['city']}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Sub District')}</b> <span class="pull-right">{$agent['subdistrict']}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Ward')}</b> <span class="pull-right">{$agent['ward']}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
</form>
{include file="sections/footer.tpl"}