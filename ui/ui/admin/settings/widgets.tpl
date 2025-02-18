{include file="sections/header.tpl"}


<hr>

{function showWidget pos=0}
    <form method="post" action="{Text::url('widgets/pos/')}">
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
                                                class="form-control" placeholder="orders">
                                        </div>
                                        <input type="hidden" name="id[]" value="{$w['id']}">
                                    </td>
                                    <td width="130">
                                        <div class="btn-group btn-group-justified" role="group">
                                            <a href="{Text::url('widgets/delete/', $w['id'])}"
                                                onclick="return ask(this, 'Delete this widget?')" class="btn btn-sm btn-danger">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </a>
                                            <a href="{Text::url('widgets/edit/', $w['id'])}"
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
                        <button type="submit" class="btn btn-info">Save sequence</button>
                    </div>
                    <a href="{Text::url('widgets/add/', $pos)}" class="btn btn-primary">{Lang::T("Add new widget")}</a>
                </div>
            </div>
        </div>
    </form>
{/function}

<div class="row">
    <div class="col-md-3">
        <div class="panel panel-info">
            <div class="panel-heading">Dashboard Structure</div>
            <div class="panel-body">
                {assign rows explode(".", $_c['dashboard_cr'])}
                {assign pos 1}
                {foreach $rows as $cols}
                    {if $cols == 12}
                        <div class="row row-no-gutters">
                            <div class="col-xs-12" style="border: 1px;">
                                <a href="{Text::url('widgets/add/', $pos)}" class="btn btn-default btn-block">{$pos}</a>
                            </div>
                        </div>
                        {assign pos value=$pos+1}
                    {else}
                        {assign colss explode(",", $cols)}
                        <div class="row row-no-gutters">
                            {foreach $colss as $c}
                                <div class="col-xs-{$c}">
                                    <a href="{Text::url('widgets/add/', $pos)}" class="btn btn-default btn-block">{$pos}</a>
                                </div>
                                {assign pos value=$pos+1}
                            {/foreach}
                        </div>
                    {/if}
                {/foreach}
            </div>
            <div class="panel-footer">
                <form method="post" action="{Text::url('widgets')}">
                    <div class="input-group">
                        <span class="input-group-addon"><a href="{$app_url}/docs/#Dashboard%20Structure"
                        target="_blank">{Lang::T("Structure")}</a></span>
                        <input type="text" name="dashboard_cr" value="{$_c['dashboard_cr']}" class="form-control"
                            placeholder="Dashboard">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" name="save" value="struct">{Lang::T("Save")}</button>
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