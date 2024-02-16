{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6 col-sm-12 col-md-offset-3">
        <div class="panel panel-hovered panel-primary panel-stacked mb30">
            <div class="panel-heading">{$in['invoice']}</div>
            <div class="panel-body">
<form class="form-horizontal" method="post" action="{$_url}prepaid/print" target="_blank">
<pre id="content"></pre>
<textarea class="hidden" id="formcontent" name="content">{Lang::pad($_c['CompanyName'],' ', 2)}
{Lang::pad($_c['address'],' ', 2)}
{Lang::pad($_c['phone'],' ', 2)}
{Lang::pad("", '=')}
{Lang::pads("Invoice", $in['invoice'], ' ')}
{Lang::pads(Lang::T('Date'), $date, ' ')}
{Lang::pads(Lang::T('Sales'), $_admin['fullname'], ' ')}
{Lang::pad("", '=')}
{Lang::pads(Lang::T('Type'), $in['type'], ' ')}
{Lang::pads(Lang::T('Plan Name'), $in['plan_name'], ' ')}
{Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($in['price']), ' ')}
{Lang::pad($in['method'], ' ', 2)}

{Lang::pads(Lang::T('Username'), $in['username'], ' ')}
{Lang::pads(Lang::T('Password'), '**********', ' ')}
{if $in['type'] != 'Balance'}
{Lang::pads(Lang::T('Created On'), Lang::dateAndTimeFormat($in['recharged_on'],$in['recharged_time']), ' ')}
{Lang::pads(Lang::T('Expires On'), Lang::dateAndTimeFormat($in['expiration'],$in['time']), ' ')}
{/if}
{Lang::pad("", '=')}
{Lang::pad($_c['note'],' ', 2)}</textarea>
    <input type="hidden" name="id" value="{$in['id']}">
    <a href="{$_url}prepaid/list" class="btn btn-primary btn-sm"><i
            class="ion-reply-all"></i>{Lang::T('Finish')}</a>
    <a href="{$_url}prepaid/view/{$in['id']}/send" class="btn btn-info text-black btn-sm"><i
        class="glyphicon glyphicon-envelope"></i> {Lang::T("Resend To Customer")}</a>
    <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-print"></i>
        {Lang::T('Click Here to Print')}</button>
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