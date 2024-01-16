{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6 col-sm-12 col-md-offset-3">
        <div class="panel panel-hovered panel-primary panel-stacked mb30">
            <div class="panel-heading">{$in['invoice']}</div>
            <div class="panel-body">
<pre><b>{Lang::pad($_c['CompanyName'],' ', 2)}</b>
{Lang::pad($_c['address'],' ', 2)}
{Lang::pad($_c['phone'],' ', 2)}
{Lang::pad("", '=')}
{Lang::pads("Invoice", $in['invoice'], ' ')}
{Lang::pads($_L['Date'], $date, ' ')}
{Lang::pads($_L['Sales'], $_admin['fullname'], ' ')}
{Lang::pad("", '=')}
{Lang::pads($_L['Type'], $in['type'], ' ')}
{Lang::pads($_L['Plan_Name'], $in['plan_name'], ' ')}
{Lang::pads($_L['Plan_Price'], Lang::moneyFormat($in['price']), ' ')}
{Lang::pad($in['method'], ' ', 2)}

{Lang::pads($_L['Username'], $in['username'], ' ')}
{Lang::pads($_L['Password'], '**********', ' ')}
{if $in['type'] != 'Balance'}
{Lang::pads($_L['Created_On'], Lang::dateAndTimeFormat($in['recharged_on'],$in['recharged_time']), ' ')}
{Lang::pads($_L['Expires_On'], Lang::dateAndTimeFormat($in['expiration'],$in['time']), ' ')}
{/if}
{Lang::pad("", '=')}
{Lang::pad($_c['note'],' ', 2)}</pre>
<form class="form-horizontal" method="post" action="{$_url}prepaid/print" target="_blank">
    <input type="hidden" name="id" value="{$in['id']}">
    <a href="{$_url}prepaid/list" class="btn btn-primary btn-sm"><i
            class="ion-reply-all"></i>{$_L['Finish']}</a>
    <a href="{$_url}prepaid/view/{$in['id']}/send" class="btn btn-info text-black btn-sm"><i
        class="glyphicon glyphicon-envelope"></i> {Lang::T("Resend To Customer")}</a>
    <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-print"></i>
        {$_L['Click_Here_to_Print']}</button>
</form>

            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var s5_taf_parent = window.location;

    function popup_print() {
        window.open('print.php?page=<?php echo $_GET['
            act '];?>', 'page',
            'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=800,height=600,left=50,top=50,titlebar=yes'
            )
    }
</script>
{include file="sections/footer.tpl"}