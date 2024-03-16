{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6 col-sm-12 col-md-offset-3">
        <div class="panel panel-hovered panel-primary panel-stacked mb30">
            <div class="panel-body">
                <form class="form-horizontal" method="post" action="{$_url}plan/print" target="_blank">
                    <pre id="content"></pre>
                    <textarea class="hidden" id="formcontent" name="content">{$print}</textarea>
                    <input type="hidden" name="id" value="{$in['id']}">
                    <a href="{$_url}plan/voucher" class="btn btn-default btn-sm"><i
                            class="ion-reply-all"></i>{Lang::T('Finish')}</a>
                    <a href="https://api.whatsapp.com/send/?text={$whatsapp}" target="_blank"
                        class="btn btn-primary btn-sm">
                        <i class="glyphicon glyphicon-share"></i> WhatsApp</a>
                    <button type="submit" class="btn btn-info text-black btn-sm"><i class="glyphicon glyphicon-print"></i>
                        Print</button>
                        <a href="nux://print?text={urlencode($print)}"
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
                <javascript type="text/javascript">
                </javascript>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    document.getElementById('content').innerHTML = document.getElementById('formcontent').innerHTML;
</script>
{include file="sections/footer.tpl"}