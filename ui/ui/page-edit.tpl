{include file="sections/header.tpl"}

<form id="formpages" method="post" role="form" action="{$_url}pages/{$PageFile}-post">
<div class="row">
    <div class="col-sm-12">
        <div class="panel mb20 panel-primary panel-hovered">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-danger btn-xs" title="Reset File" href="{$_url}pages/{$PageFile}-reset" onclick="return confirm('Reset File?')"><span
                            class="glyphicon glyphicon-refresh" aria-hidden="true"></span></a>
                </div>
                {$pageHeader}
            </div>
            <textarea name="html" id="summernote">{$htmls}</textarea>
            {if $writeable}
                <div class="panel-footer">
                    <a href="javascript:saveIt()" class="btn btn-primary btn-block">SAVE</a>
                    <br>
                    <p class="help-block">{Lang::T("Sometimes you need to refresh 3 times until content change")}</p>
                    <input type="text" class="form-control" onclick="this.select()" readonly
                        value="{$app_url}/pages/{$PageFile}.html">
                </div>
            {else}
                <div class="panel-footer">
                    {Lang::T("Failed to save page, make sure i can write to folder pages, <i>chmod 664 pages/*.html<i>")}
                </div>
            {/if}
            {if $PageFile=='Voucher'}
                <div class="panel-footer">
                    <p class="help-block">
                        <b>[[company_name]]</b> Your Company Name at Settings.<br>
                        <b>[[price]]</b> Plan Price.<br>
                        <b>[[voucher_code]]</b> Voucher Code.<br>
                        <b>[[plan]]</b> Voucher Plan.<br>
                        <b>[[counter]]</b> Counter.<br>
                    </p>
                </div>
            {/if}
        </div>
    </div>
</div>
</form>
{literal}
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            $('#summernote').summernote();
        });
    </script>
{/literal}

{include file="sections/footer.tpl"}