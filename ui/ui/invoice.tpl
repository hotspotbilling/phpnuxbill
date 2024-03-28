{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6 col-sm-12 col-md-offset-3">
        <div class="panel panel-hovered panel-primary panel-stacked mb30">
            <div class="panel-heading">{$in['invoice']}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" action="{$_url}plan/print" target="_blank">
                    <pre id="content"></pre>
                    <textarea class="hidden" id="formcontent" name="content">{$invoice}</textarea>
                    <input type="hidden" name="id" value="{$in['id']}">
                    <a href="{$_url}plan/list" class="btn btn-default btn-sm"><i
                            class="ion-reply-all"></i>{Lang::T('Finish')}</a>
                    <a href="https://api.whatsapp.com/send/?text={$whatsapp}" target="_blank"
                    class="btn btn-primary btn-sm">
                    <i class="glyphicon glyphicon-share"></i> WhatsApp</a>
                    <a href="{$_url}plan/view/{$in['id']}/send" class="btn btn-info text-black btn-sm"><i
                            class="glyphicon glyphicon-envelope"></i> {Lang::T("Resend")}</a>
                        <button type="submit" class="btn btn-info text-black btn-sm"><i class="glyphicon glyphicon-print"></i>
                        Print</button>
                    <a href="nux://print?text={urlencode($invoice)}"
                    class="btn btn-success text-black btn-sm hidden-md hidden-lg">
                        <i class="glyphicon glyphicon-phone"></i>
                        NuxPrint
                    </a>
                    <a href="https://github.com/hotspotbilling/android-printer"
                    class="btn btn-success text-black btn-sm hidden-xs hidden-sm" target="_blank">
                        <i class="glyphicon glyphicon-phone"></i>
                        NuxPrint
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var s5_taf_parent = window.location;
    document.getElementById('content').innerHTML = document.getElementById('formcontent').innerHTML;
</script>
{include file="sections/footer.tpl"}