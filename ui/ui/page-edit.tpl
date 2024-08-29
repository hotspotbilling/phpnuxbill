{include file="sections/header.tpl"}

<form id="formpages" method="post" role="form" action="{$_url}pages/{$PageFile}-post">
    <div class="row">
        <div class="{if $action=='Voucher'}col-md-8{else}col-md-12{/if}">
            <div class="panel mb20 panel-primary panel-hovered">
                <div class="panel-heading">
                    {if $action!='Voucher'}
                        <div class="btn-group pull-right">
                            <a class="btn btn-danger btn-xs" title="Reset File" href="{$_url}pages/{$PageFile}-reset"
                                onclick="return confirm('Reset File?')"><span class="glyphicon glyphicon-refresh"
                                    aria-hidden="true"></span></a>
                        </div>
                    {/if}
                    {$pageHeader}
                </div>
                <textarea name="html" id="summernote">{$htmls}</textarea>
                {if $writeable}
                    <div class="panel-footer">
                        {if $action=='Voucher'}
                            <label>
                                <input type="checkbox" name="template_save" value="yes"> {Lang::T('Save as template')}
                            </label>
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon3">{Lang::T('Template Name')}</span>
                                <input type="text" class="form-control" id="template_name" name="template_name">
                            </div>
                            <br>
                        {/if}
                        <button type="submit" class="btn btn-primary btn-block">{Lang::T('Save')}</button>
                        <br>
                        <p class="help-block">{Lang::T('Sometimes you need to refresh 3 times until content change')}</p>
                        <input type="text" class="form-control" onclick="this.select()" readonly
                            value="{$app_url}/{$PAGES_PATH}/{$PageFile}.html">
                    </div>
                {else}
                    <div class="panel-footer">
                        {Lang::T("Failed to save page, make sure i can write to folder pages, <i>chmod 664 pages/*.html<i>")}
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
                            <div class="panel-body">{include file="$PAGES_PATH/vouchers/$v"}</div>
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

        function selectTemplate(f) {
            let children = f.children;
            $('#template_name').val(children[0].innerHTML)
            $('#summernote').summernote('code', children[1].innerHTML);
            window.scrollTo(0, 0);
        }
    </script>
{/literal}

{include file="sections/footer.tpl"}
