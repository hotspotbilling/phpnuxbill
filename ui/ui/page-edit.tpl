{include file="sections/header.tpl"}
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
            <div id="myNicPanel" style="width: 100%;"></div>
            <div id="panel-edit" class="panel-body">{$htmls}</div>
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
<form id="formpages" class="hidden" method="post" role="form" action="{$_url}pages/{$PageFile}-post">
    <textarea name="html" id="html"></textarea>
</form>
<script src="ui/ui/scripts/nicEdit.js"></script>
{literal}
    <script type="text/javascript">
        var myNicEditor
        bkLib.onDomLoaded(function() {
            myNicEditor = new nicEditor({fullPanel : true});
            myNicEditor.setPanel('myNicPanel');
            myNicEditor.addInstance('panel-edit');
        });

        function saveIt() {
            //alert(document.getElementById('panel-edit').innerHTML);
            document.getElementById('html').value = nicEditors.findEditor('panel-edit').getContent()
            document.getElementById('formpages').submit();
        }
    </script>
{/literal}

{include file="sections/footer.tpl"}