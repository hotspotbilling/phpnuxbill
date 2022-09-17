<!DOCTYPE html>
<html>
<head>
    <title>{$_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/x-icon" href="ui/ui/images/favicon.ico">
    <style>
	.ukuran {
		size:A4;
	}

	body,td,th {
		font-size: 12px;
		font-family: Segoe, "Segoe UI", "DejaVu Sans", "Trebuchet MS", Verdana, sans-serif;
	}
	page[size="A4"] {
	  background: white;
	  width: 21cm;
	  height: 29.7cm;
	  display: block;
	  margin: 0 auto;
	  margin-bottom: 0.5cm;
	  html, body {
		width: 210mm;
		height: 297mm;
	  }
	}
	@media print {
        body {
            size: auto;
            margin: 0;
            box-shadow: 0;
        }
        page[size="A4"] {
            margin: 0;
            size: auto;
            box-shadow: 0;
        }
        .page-break	{ display: block; page-break-before: always; }
        .no-print, .no-print *
        {
            display: none !important;
        }
    }
    </style>
</head>

<body>
<page size="A4">
        <form method="post" action="{$_url}prepaid/print-voucher/" class="no-print">
        <table width="100%" border="0" cellspacing="0" cellpadding="1" class="btn btn-default btn-sm">
            <tr>
                <td>ID &gt; <input type="text" name="from_id" style="width:40px" value="{$from_id}"> limit <input type="text" name="limit" style="width:40px" value="{$limit}"></td>
                <td>PageBreak after  <input type="text" style="width:40px" name="pagebreak" value="{$pagebreak}"> vouchers</td>
                <td>Plans <select id="plan_id" name="planid" style="width:150px">
                <option value="0">--all--</option>
                {foreach $plans as $plan}
                    <option value="{$plan['id']}" {if $plan['id']==$planid}selected{/if}>{$plan['name_plan']}</option>
                {/foreach}
                </select></td>
                <td><button type="submit">submit</button></td>
            </tr>
        </table><hr>
        <center><button type="button" id="actprint" class="btn btn-default btn-sm no-print">{$_L['Click_Here_to_Print']}</button><br>
        {$_L['Print_Info']}<br>
        show {$v|@count} vouchers from {$vc} vouchers<br>
        from ID {$v[0]['id']} limit {$limit} vouchers
        </center>
        </form>
        <div id="printable">
            <hr>
            {foreach $v as $vs}
            {$jml = $jml + 1}
            <table width="100%" height="200" border="0" cellspacing="0" cellpadding="1" style="margin-bottom:5px">
                <tbody>
                    <tr><td align="center" valign="middle"></td></tr>
                    <tr>
                    <td align="center" valign="top">
                        <table width="100%" border="0" cellspacing="0" cellpadding="2">
                        <tr>
                            <td width="50%" valign="middle" style="padding-right:10px">
                            <center><strong style="font-size:38px">{$_L['Voucher_Hotspot']}</strong><span class="no-print">  ID {$vs['id']}</span></center>
                            <table width="100%" border="1" cellspacing="0" cellpadding="1" bordercolor="#757575">
                                <tbody>
                                <tr>
                                    <td rowspan="5" width="1"><img src="qrcode/?data={$vs['code']}"></td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="center" style="font-size:25px">{$_c['currency_code']} {number_format($vs['price'],2,$_c['dec_point'],$_c['thousands_sep'])}</td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="center" style="font-size:20px">{$_L['Code_Voucher']}</td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="center" style="font-size:25px">{$vs['code']}</td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="center" style="font-size:15px">{$vs['name_plan']}</td>
                                </tr>
                                </tbody>
                            </table>
                            </td>
                            <td valign="top" style="padding-left:10px">
                                {include file="$_path/../pages/Voucher.html"}
                            </td>
                        </tr>
                        </table>
                    </td>
                    </tr>
                </tbody>
                </table>
                <hr>
                {if $jml == $pagebreak}
                {$jml = 0}
                <!-- pageBreak -->
                <div class="page-break"><div class="no-print" style="background-color: #E91E63; color:#FFF;" align="center">-- pageBreak --<hr></div></div>
                {/if}
                {/foreach}
        </div>
</page>
<script src="ui/ui/scripts/jquery-1.10.2.js"></script>
{if isset($xfooter)}
    {$xfooter}
{/if}
<script>
    jQuery(document).ready(function() {
        // initiate layout and plugins
        $("#actprint").click(function() {
            window.print();
            return false;
        });
    });
</script>

</body>
</html>