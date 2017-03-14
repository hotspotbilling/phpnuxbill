<!DOCTYPE html>
<html>
<head>
    <title>{$_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/x-icon" href="{$_theme}/images/favicon.ico">
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
        margin: 0;
        box-shadow: 0;
        }
        page[size="A4"] {
        margin: 0;
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
                <td>ID more than <input type="text" name="from_id" width="4" value="{$from_id}"></td>
                <td>PageBreak after  <input type="text" name="pagebreak" width="2" value="{$pagebreak}"> vouchers</td>
                <td><button type="submit">submit</button></td>
            </tr>
        </table><hr>
        <center><button type="button" id="actprint" class="btn btn-default btn-sm no-print">{$_L['Click_Here_to_Print']}</button><br>
        {$_L['Print_Info']}</center>
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
                            <table width="100%" border="1" cellspacing="0" cellpadding="4" bordercolor="#757575">
                                <tbody>
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
                                <center><strong style="font-size:38px">{$_c['CompanyName']}</strong></center>
                                <table width="100%" border="1" cellspacing="0" cellpadding="4" bordercolor="#757575">
                                <tbody>
                                <tr>
                                    <td valign="top" align="left">Pendaftaran dan Informasi Billing buka <b>billing.ibnux.net</b></td>
                                </tr>
                                <tr>
                                    <td valign="top" align="left">Wireless Hotspot:
                                        <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                            <tr>
                                                <td>iBNuXnet</td>
                                                <td>iBNuXnet-P</td>
                                                <td>iBNuXnet-Q</td>
                                            </tr>
                                            <tr>
                                                <td>CitraGadingBlokP 3/4</td>
                                                <td>CitraGadingBlokQ 2/3/4/5/6</td>
                                                <td>iBNuXnet 5Ghz</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" align="left">Voucher yang sudah dibeli tidak dapat dikembalikan</td>
                                </tr>
                                <tr>
                                    <td valign="top" align="center"><b>hotspot.ibnux.net</b></td>
                                </tr>
                                </tbody>
                            </table>
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
<script src="{$_theme}/scripts/jquery-1.10.2.js"></script>
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