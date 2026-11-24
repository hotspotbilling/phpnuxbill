{include file="sections/header.tpl"}

<form id="formpages" method="post" role="form" action="{Text::url('')}pages/{$PageFile}-post" class="st">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="row">
        <div class="{if $action=='Voucher'}col-md-8{else}col-md-12{/if}">
            <div class="panel mb20 panel-primary panel-hovered">
                <div class="panel-heading">
                    {if $action!='Voucher'}
                        <div class="btn-group pull-right">
                            <a class="btn btn-danger btn-xs" title="Reset File"
                                href="{Text::url('')}pages/{$PageFile}-reset"
                                onclick="var a = this; return ask(a, 'Reset File?', function () { window.location.href = a.href; })"><span class="glyphicon glyphicon-refresh"
                                    aria-hidden="true"></span></a>
                        </div>
                    {/if}
                    {$pageHeader}
                </div>
                <textarea name="html" id="summernote">{$htmls}</textarea>
                {if $writeable}
                    <div class="panel-footer st-page-foot">
                        {if $action=='Voucher'}
                        <div class="st-page-tpl">
                            <label class="st-all"><input type="checkbox" name="template_save" value="yes"> {Lang::T('Save as template')}</label>
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon3">{Lang::T('Template Name')}</span>
                                <input type="text" class="form-control" id="template_name" name="template_name">
                            </div>
                        </div>
                        {/if}
                        <div class="st-page-url">
                            <label class="mc-label" for="stPageUrl">{Lang::T('Page address')}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="stPageUrl" onclick="this.select()" readonly
                                    value="{$app_url}/{$PAGES_PATH}/{$PageFile}.html">
                                <span class="input-group-btn"><button type="button" class="btn btn-default" id="stPageCopy" data-copied="{Lang::T('Copied')|escape}">{Lang::T('Copy')}</button></span>
                            </div>
                            <small>{Lang::T('Sometimes you need to refresh 3 times until content change')}</small>
                            <small id="stPageCopyHint" hidden>{Lang::T('Copying is not available here: the address is selected, press Ctrl+C (or Cmd+C) to copy it.')}</small>
                        </div>
                        <button type="submit" class="ls-btn ls-btn--primary st-page-save"><i class="fa fa-check" aria-hidden="true"></i> {Lang::T('Save')}</button>
                    </div>
                {else}
                    <div class="panel-footer">
                        <div class="rf-note rf-note--warn" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                            <div>{Lang::T("Failed to save page, make sure i can write to folder pages, <i>chmod 664 pages/*.html<i>")}</div></div>
                    </div>
                {/if}
                {if $PageFile=='Voucher'}
                    <div class="panel-footer">
                        <p class="help-block">
                            <b>[[company_name]]</b> {Lang::T('Your Company Name at Settings')}.<br>
                            <b>[[price]]</b> {Lang::T('Package Price')}.<br>
                            <b>[[voucher_code]]</b> {Lang::T('Voucher Code')}.<br>
                            <b>[[plan]]</b> {Lang::T('Voucher Package')}.<br>
                            <b>[[counter]]</b> {Lang::T('Counter')}.<br>
                        </p>
                    </div>
                {/if}
            </div>
        </div>
        {if $action=='Voucher'}
            <div class="col-md-4">
                {foreach $vouchers as $v}
                    {if is_file("$PAGES_PATH/vouchers/$v")}
                        <div class="panel mb20 panel-primary panel-hovered" style="cursor: pointer;" onclick="selectTemplate(this)">
                            <div class="panel-heading">{str_replace(".html", '', $v)}</div>
                            {* the file is read as text, never compiled as a template: printed vouchers put it out the same way (only [[...]] is replaced) *}
                            {fetch file="$PAGES_PATH/vouchers/$v" assign=vsrc}
                            <div class="panel-body">{$vsrc}</div>
                            {* the file as written, for the editor: the preview above is the rendered page, which right-to-left languages mirror *}
                            <textarea class="hidden" data-template-source hidden>{$vsrc|escape}</textarea>
                        </div>
                    {/if}
                {/foreach}
            </div>
        {/if}
    </div>
</form>
{literal}
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            $('#summernote').summernote();
        });

        // "Copied" only once the clipboard took it; without the Clipboard API (or when it refuses) the address stays selected
        document.addEventListener("DOMContentLoaded", function () {
            var btn = document.getElementById('stPageCopy'), field = document.getElementById('stPageUrl'), hint = document.getElementById('stPageCopyHint');
            if (!btn) { return; }
            var label = btn.textContent;
            btn.addEventListener('click', function () {
                field.select();
                var manual = function () { field.focus(); field.select(); hint.hidden = false; };
                if (!navigator.clipboard || !window.isSecureContext) { manual(); return; }
                navigator.clipboard.writeText(field.value).then(function () {
                    hint.hidden = true;
                    btn.textContent = btn.getAttribute('data-copied');
                    setTimeout(function () { btn.textContent = label; }, 1600);
                }, manual);
            });
        });

        function selectTemplate(f) {
            let children = f.children;
            $('#template_name').val(children[0].innerHTML)
            var src = f.querySelector('[data-template-source]');
            $('#summernote').summernote('code', src ? src.value : children[1].innerHTML);
            window.scrollTo(0, 0);
        }
    </script>
{/literal}

{include file="sections/footer.tpl"}