{include file="sections/header.tpl"}
<ul class="nav nav-tabs nav-justified">
    <li role="presentation" {if $tipeUser=='Admin'} class="active" {/if}><a
            href="{Text::url('widgets&user=Admin')}">Admin</a></li>
    <li role="presentation" {if $tipeUser=='Agent'} class="active" {/if}><a
            href="{Text::url('widgets&user=Agent')}">Agent</a></li>
    <li role="presentation" {if $tipeUser=='Sales'} class="active" {/if}><a
            href="{Text::url('widgets&user=Sales')}">Sales</a></li>
    <li role="presentation" {if $tipeUser=='Customer'} class="active" {/if}><a
            href="{Text::url('widgets&user=Customer')}">Customer</a></li>
</ul>
<br>
{function showWidget pos=0}
    <form method="post" action="{Text::url('widgets/pos/', '&user=', $tipeUser)}">
        <div class="panel panel-info">
            <div class="panel-heading">{Lang::T("Area Fields")} {$pos}</div>
            <div class="panel-body">
                {foreach $widgets as $w}
                    {if $w['position'] == $pos}
                        <div class="panel panel-{if $w['enabled']}default{else}danger{/if}">
                            <div class="panel-heading"><b>{$w['title']}</b></div>
                            <div class="panel-body">{ucwords(str_replace('.php', '', str_replace('_', ' ', $w['widget'])))}</div>
                            <table class="table table-bordered table-condensed">
                                <tr>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-addon">{Lang::T("Sequence")}</span>
                                            <input type="number" style="width: 100px;" name="orders[]" value="{$w['orders']}"
                                                class="form-control" placeholder="{Lang::T("Sequence")}">
                                        </div>
                                        <input type="hidden" name="id[]" value="{$w['id']}">
                                    </td>
                                    <td width="130">
                                        <div class="btn-group btn-group-justified" role="group">
                                            <a href="{Text::url('widgets/delete/', $w['id'], '&user=', $tipeUser)}"
                                                onclick="return ask(this, '{Lang::T("Delete this widget?")}')" class="btn btn-sm btn-danger">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </a>
                                            <a href="{Text::url('widgets/edit/', $w['id'], '&user=', $tipeUser)}"
                                                class="btn btn-sm btn-success">{Lang::T("Edit")}</a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    {/if}
                {/foreach}
            </div>
            <div class="panel-footer">
                <div class="btn-group btn-group-justified" role="group">
                    <div class="btn-group" role="group">
                        <button type="submit" class="btn btn-info">{Lang::T("Save sequence")}</button>
                    </div>
                    <a href="{Text::url('widgets/add/', $pos, '&user=', $tipeUser)}" class="btn btn-xs btn-primary">{Lang::T("Add new widget")}</a>
                </div>
            </div>
        </div>
    </form>
{/function}
{assign dtipe value="dashboard_`$tipeUser`"}
<div class="row">
    <div class="col-md-3">
        <div class="panel panel-info">
            <div class="panel-heading">{Lang::T("Dashboard Structure")}</div>
            <div class="panel-body">
                {assign rows explode(".", $_c[$dtipe])}
                {assign pos 1}
                {foreach $rows as $cols}
                    {if $cols == 12}
                        <div class="row row-no-gutters">
                            <div class="col-xs-12" style="border: 1px;">
                                <a href="{Text::url('widgets/add/', $pos, '&user=', $tipeUser)}" class="btn btn-default btn-block">{$pos}</a>
                            </div>
                        </div>
                        {assign pos value=$pos+1}
                    {else}
                        {assign colss explode(",", $cols)}
                        <div class="row row-no-gutters">
                            {foreach $colss as $c}
                                <div class="col-xs-{$c}">
                                    <a href="{Text::url('widgets/add/', $pos, '&user=', $tipeUser)}" class="btn btn-default btn-block">{$pos}</a>
                                </div>
                                {assign pos value=$pos+1}
                            {/foreach}
                        </div>
                    {/if}
                {/foreach}
            </div>
            <div class="panel-footer">
                <form method="post">
                    <div class="input-group">
                        <span class="input-group-addon"><a href="{$app_url}/docs/#Dashboard%20Structure"
                                target="_blank">{Lang::T("Structure")}</a></span>
                        <input type="text" name="dashboard" value="{$_c[$dtipe]}" class="form-control"
                            placeholder="{Lang::T("Dashboard")}">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" name="save"
                        value="struct">{Lang::T("Save")}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="row">
            <div class="col-md-6">
                {for $pos=1 to $max}
                    {if $pos%2 != 0}
                        {showWidget widgets=$widgets pos=$pos}
                    {/if}
                {/for}
            </div>
            <div class="col-md-6">
                {for $pos=1 to $max}
                    {if $pos%2 == 0}
                        {showWidget widgets=$widgets pos=$pos}
                    {/if}
                {/for}
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
