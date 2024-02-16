{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6 col-sm-12 col-md-offset-3">
        <div class="panel panel-hovered panel-primary panel-stacked mb30">
            <div class="panel-body">
                <form class="form-horizontal" method="post" action="{$_url}prepaid/print" target="_blank">
                    <pre id="content"></pre>
                    <textarea class="hidden" id="formcontent" name="content">{$print}</textarea>
                    <input type="hidden" name="id" value="{$in['id']}">
                    <a href="{$_url}prepaid/voucher" class="btn btn-primary btn-sm"><i
                            class="ion-reply-all"></i>{Lang::T('Finish')}</a>
                    <a href="https://api.whatsapp.com/send/?text={$wa}" target="_blank" class="btn btn-info text-black btn-sm"><i
                            class="glyphicon glyphicon-envelope"></i> {Lang::T("Send To Customer")}</a>
                    <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-print"></i>
                        {Lang::T('Click Here to Print')}</button>
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