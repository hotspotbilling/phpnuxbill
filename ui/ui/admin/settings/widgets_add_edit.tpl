{include file="sections/header.tpl"}

<form method="post" action="{Text::url('widgets/', $do)}" class="form-horizontal st">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading">{if $do == 'add'}{Lang::T("Add")}{else}{Lang::T("Edit")}{/if} {Lang::T("Widget")}</div>
                <div class="panel-body">
                    <input type="hidden" class="form-control" required id="id" name="id" value="{$widget['id']}">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T("Widget")}</label>
                        <div class="col-md-5">
                            <select name="widget" id="widget" class="form-control" onchange="changeTitle(this)">
                                {foreach $widgets as $k => $v}
                                    <option value="{$k}" {if $widget['widget'] eq $k}selected="selected" {/if}>
                                        {$v}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T("Title")}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" required id="title" name="title"
                                value="{$widget['title']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T("Sequence")}</label>
                        <div class="col-md-5">
                            <input type="number" class="form-control" id="orders" name="orders"
                                value="{if $widget['orders']>0}{$widget['orders']}{else}99{/if}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T("Position")}</label>
                        <div class="col-md-5">
                            <select name="position" id="position" class="form-control">
                                {for $pos=1 to $max}
                                    <option value="{$pos}" {if $widget['position'] eq $pos}selected="selected" {/if}>
                                        Area {$pos}
                                    </option>
                                {/for}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T("User")}</label>
                        <div class="col-md-5">
                            <select name="tipeUser" aria-readonly="" id="tipeUser" class="form-control">
                                <option value="{$widget['user']}" {if $widget['user'] eq $u}selected="selected" {/if}>
                                    {Lang::T("Dashboard")} {$widget['user']}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T("Status")}</label>
                        <div class="col-md-5">
                            <select name="enabled" id="enabled" class="form-control">
                                <option value="1"
                                    {if !isset($widget['enabled']) || $widget['enabled'] neq 0}selected="selected" {/if}>
                                    {Lang::T("Active")}
                                </option>
                                <option value="0"
                                    {if isset($widget['enabled']) && $widget['enabled'] eq 0}selected="selected" {/if}>
                                    {Lang::T("Not Active")}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T("Content")}</label>
                        <div class="col-md-9">
                            <textarea name="content" rows="15" id="summernote"
                                class="form-control">{$widget['content']}</textarea>
                            <p class="help-block">
                                {Lang::T("Not all widgets require content. HTML widgets require content, either text or PHP code. Please be careful when writing this content.")}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel-footer st-card-foot">
                    {if $do == 'edit'}
                    <a href="{Text::url('widgets/delete/', $widget['id'], '&user=', $tipeUser)}" class="ls-btn ls-btn--danger"
                        onclick="return ask(this, '{Lang::T("Remove this widget?")}')"><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T("Delete")}</a>
                    {else}<span></span>{/if}
                    <span class="st-foot-end">
                        <a href="{Text::url('widgets/', '&user=', $tipeUser)}" class="ls-btn"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T("Back")}</a>
                        <button type="submit" class="ls-btn ls-btn--primary"><i class="fa fa-check" aria-hidden="true"></i> {Lang::T("Save")}</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    function changeTitle(sel) {
        var name = sel.options[sel.selectedIndex].text;
        document.getElementById('title').value = name;
    }
    document.addEventListener("DOMContentLoaded", function() {
        $('#summernote').summernote();
    });
</script>

{include file="sections/footer.tpl"}
