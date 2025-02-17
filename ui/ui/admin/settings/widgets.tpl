{include file="sections/header.tpl"}

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
                                            <a href="{Text::url('widgets/edit/', $w['id'])}" class="btn btn-sm btn-success">{Lang::T("Edit")}</a>
                                            <a href="{Text::url('widgets/delete/', $w['id'])}"
                                                onclick="return ask(this, 'Delete this widget?')" class="btn btn-sm btn-danger">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </a>
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
    <div class="col-md-6 col-lg-4">
        {showWidget widgets=$widgets pos=1}
    </div>
    <div class="col-md-6 col-lg-4">
        {showWidget widgets=$widgets pos=2}
    </div>
    <div class="col-md-6 col-lg-4">
        {showWidget widgets=$widgets pos=3}
    </div>
    <div class="col-md-6 col-lg-4">
        {showWidget widgets=$widgets pos=4}
    </div>
</div>

{include file="sections/footer.tpl"}