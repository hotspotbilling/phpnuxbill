{include file="sections/header.tpl"}

<form method="post" action="{Text::url('widgets/', $do)}">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading">{if $do == 'add'}{Lang::T('Tambah')}{else}{Lang::T('Edit')}{/if} Widget</div>
                <div class="panel-body">
                    <input type="hidden" class="form-control" required id="id" name="id" value="{$widget['id']}">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Widget')}</label>
                        <div class="col-md-5">
                            <select name="widget" id="widget" class="form-control" onchange="changeTitle(this)">
                                {foreach $widgets as $k => $v}
                                    <option value="{$k}" {if $widget['widget'] eq $k}selected="selected" {/if}>
                                        {$v}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <p class="help-block col-md-4">&nbsp;</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Title')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" required id="title" name="title"
                                value="{$widget['title']}">
                        </div>
                        <span class="help-block col-md-4">&nbsp;</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Sequence')}</label>
                        <div class="col-md-5">
                            <input type="number" class="form-control" id="orders" name="orders"
                                value="{if $widget['orders']>0}{$widget['orders']}{else}99{/if}">
                        </div>
                        <span class="help-block col-md-4">&nbsp;</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Position')}</label>
                        <div class="col-md-5">
                            <select name="position" id="position" class="form-control">
                                {for $pos=1 to $max}
                                    <option value="{$pos}" {if $widget['position'] eq $pos}selected="selected" {/if}>
                                        Area {$pos}
                                    </option>
                                {/for}
                            </select>
                        </div>
                        <p class="help-block col-md-4">&nbsp;</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Status')}</label>
                        <div class="col-md-5">
                            <select name="enabled" id="enabled" class="form-control">
                                <option value="1" {if isset($widget['enabled']) && $widget['enabled'] neq 1}selected="selected" {/if}>
                                    {Lang::T('Active')}
                                </option>
                                <option value="0" {if isset($widget['enabled']) && $widget['enabled'] eq 0}selected="selected" {/if}>
                                    {Lang::T('Not Active')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">&nbsp;</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Content')}</label>
                        <div class="col-md-9">
                            <textarea name="content" rows="15" id="summernote" class="form-control">{$widget['content']}</textarea>
                            <p class="help-block">{Lang::T("Not all widgets require content. HTML widgets require content, either text or PHP code. Please be careful when writing this content.")}</p>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <div class="btn-group btn-group-justified" role="group">
                        <a href="{Text::url('widgets/')}" class="btn btn-default btn-block">{Lang::T('Back')}</a>
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-info btn-block">{Lang::T('Save')}</button>
                        </div>
                    </div>
                </div>
            </div>
            {if $do == 'edit'}
                <a href="{Text::url('widgets/delete/', $widget['id'])}" class="btn btn-danger btn-xs"
                    onclick="return ask(this, 'Remove this widget?')">{Lang::T('Delete')}</a>
            {/if}
        </div>
    </div>
</form>

<script>
    function changeTitle(sel){
        var name = sel.options[sel.selectedIndex].text;
        document.getElementById('title').value = name;
    }
    document.addEventListener("DOMContentLoaded", function() {
        $('#summernote').summernote();
    });
</script>

{include file="sections/footer.tpl"}
