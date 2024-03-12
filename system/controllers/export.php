<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Reports'));
$ui->assign('_sysfrm_menu', 'reports');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$mdate = date('Y-m-d');
$tdate = date('Y-m-d', strtotime('today - 30 days'));

//first day of month
$first_day_month = date('Y-m-01');
//
$this_week_start = date('Y-m-d', strtotime('previous sunday'));
// 30 days before
$before_30_days = date('Y-m-d', strtotime('today - 30 days'));
//this month
$month_n = date('n');

switch ($action) {

    case 'print-by-date':
        $mdate = date('Y-m-d');
        $d = ORM::for_table('tbl_transactions');
        $d->where('recharged_on', $mdate);
        $d->order_by_desc('id');
        $x =  $d->find_many();

        $dr = ORM::for_table('tbl_transactions');
        $dr->where('recharged_on', $mdate);
        $dr->order_by_desc('id');
        $xy =  $dr->sum('price');

        $ui->assign('d', $x);
        $ui->assign('dr', $xy);
        $ui->assign('mdate', $mdate);
        $ui->assign('recharged_on', $mdate);
        run_hook('print_by_date'); #HOOK
        $ui->display('print-by-date.tpl');
        break;

    case 'pdf-by-date':
        $mdate = date('Y-m-d');

        $d = ORM::for_table('tbl_transactions');
        $d->where('recharged_on', $mdate);
        $d->order_by_desc('id');
        $x =  $d->find_many();

        $dr = ORM::for_table('tbl_transactions');
        $dr->where('recharged_on', $mdate);
        $dr->order_by_desc('id');
        $xy =  $dr->sum('price');

        $title = ' Reports [' . $mdate . ']';
        $title = str_replace('-', ' ', $title);

        $UPLOAD_URL_PATH = str_replace($root_path, '',  $UPLOAD_PATH);
        if (file_exists($UPLOAD_PATH . '/logo.png')) {
            $logo = $UPLOAD_URL_PATH . '/logo.png';
        } else {
            $logo = $UPLOAD_URL_PATH . '/logo.default.png';
        }

        if ($x) {
            $html = '
			<div id="page-wrap">
				<div id="address">
					<h3>' . $config['CompanyName'] . '</h3>
					' . $config['address'] . '<br>
					' . Lang::T('Phone Number') . ': ' . $config['phone'] . '<br>
				</div>
				<div id="logo"><img id="image" src="' . $logo . '" alt="logo" /></div>
			</div>
			<div id="header">' . Lang::T('All Transactions at Date') . ': ' . date($config['date_format'], strtotime($mdate)) . '</div>
			<table id="customers">
				<tr>
				<th>' . Lang::T('Username') . '</th>
				<th>' . Lang::T('Plan Name') . '</th>
				<th>' . Lang::T('Type') . '</th>
				<th>' . Lang::T('Plan Price') . '</th>
				<th>' . Lang::T('Created On') . '</th>
				<th>' . Lang::T('Expires On') . '</th>
				<th>' . Lang::T('Method') . '</th>
				<th>' . Lang::T('Routers') . '</th>
				</tr>';
            $c = true;
            foreach ($x as $value) {

                $username = $value['username'];
                $plan_name = $value['plan_name'];
                $type = $value['type'];
                $price = $config['currency_code'] . ' ' . number_format($value['price'], 0, $config['dec_point'], $config['thousands_sep']);
                $recharged_on = date($config['date_format'], strtotime($value['recharged_on']));
                $expiration = date($config['date_format'], strtotime($value['expiration']));
                $time = $value['time'];
                $method = $value['method'];
                $routers = $value['routers'];

                $html .= "<tr" . (($c = !$c) ? ' class="alt"' : ' class=""') . ">" . "
				<td>$username</td>
				<td>$plan_name</td>
				<td>$type</td>
				<td align='right'>$price</td>
				<td>$recharged_on</td>
				<td>$expiration $time </td>
				<td>$method</td>
				<td>$routers</td>
				</tr>";
            }
            $html .= '</table>
			<h4 class="text-uppercase text-bold">' . Lang::T('Total Income') . ':</h4>
			<h3 class="sum">' . $config['currency_code'] . ' ' . number_format($xy, 2, $config['dec_point'], $config['thousands_sep']) . '</h3>';
            run_hook('print_pdf_by_date'); #HOOK

            $mpdf = new \Mpdf\Mpdf();
            $mpdf->SetProtection(array('print'));
            $mpdf->SetTitle($config['CompanyName'] . ' Reports');
            $mpdf->SetAuthor($config['CompanyName']);
            $mpdf->SetWatermarkText($d['price']);
            $mpdf->showWatermarkText = true;
            $mpdf->watermark_font = 'Helvetica';
            $mpdf->watermarkTextAlpha = 0.1;
            $mpdf->SetDisplayMode('fullpage');

            $style = '<style>
			#page-wrap { width: 100%; margin: 0 auto; }
			#header { text-align: center; position: relative; color: black; font: bold 15px Helvetica, Sans-Serif; margin-top: 10px; margin-bottom: 10px;}

			#address { width: 300px; float: left; }
			#logo { text-align: right; float: right; position: relative; margin-top: 15px; border: 5px solid #fff; overflow: hidden; }

			#customers
			{
			font-family: Helvetica, sans-serif;
			width:100%;
			border-collapse:collapse;
			}
			#customers td, #customers th
			{
			font-size:0.8em;
			border:1px solid #98bf21;
			padding:3px 5px 2px 5px;
			}
			#customers th
			{
			font-size:0.8em;
			text-align:left;
			padding-top:5px;
			padding-bottom:4px;
			background-color:#A7C942;
			color:#fff;
			}
			#customers tr.alt td
			{
			color:#000;
			background-color:#EAF2D3;
			}
			</style>';

            $nhtml = <<<EOF
$style
$html
EOF;
            $mpdf->WriteHTML($nhtml);
            $mpdf->Output(date('Ymd_His') . '.pdf', 'D');
        } else {
            echo 'No Data';
        }

        break;

    case 'print-by-period':
        $fdate = _post('fdate');
        $tdate = _post('tdate');
        $stype = _post('stype');

        $d = ORM::for_table('tbl_transactions');
        if ($stype != '') {
            $d->where('type', $stype);
        }
        $d->where_gte('recharged_on', $fdate);
        $d->where_lte('recharged_on', $tdate);
        $d->order_by_desc('id');
        $x =  $d->find_many();

        $dr = ORM::for_table('tbl_transactions');
        if ($stype != '') {
            $dr->where('type', $stype);
        }

        $dr->where_gte('recharged_on', $fdate);
        $dr->where_lte('recharged_on', $tdate);
        $xy = $dr->sum('price');

        $ui->assign('d', $x);
        $ui->assign('dr', $xy);
        $ui->assign('fdate', $fdate);
        $ui->assign('tdate', $tdate);
        $ui->assign('stype', $stype);
        run_hook('print_by_period'); #HOOK
        $ui->display('print-by-period.tpl');
        break;


    case 'pdf-by-period':
        $fdate = _post('fdate');
        $tdate = _post('tdate');
        $stype = _post('stype');
        $d = ORM::for_table('tbl_transactions');
        if ($stype != '') {
            $d->where('type', $stype);
        }

        $d->where_gte('recharged_on', $fdate);
        $d->where_lte('recharged_on', $tdate);
        $d->order_by_desc('id');
        $x =  $d->find_many();

        $dr = ORM::for_table('tbl_transactions');
        if ($stype != '') {
            $dr->where('type', $stype);
        }

        $dr->where_gte('recharged_on', $fdate);
        $dr->where_lte('recharged_on', $tdate);
        $xy = $dr->sum('price');

        $title = ' Reports [' . $mdate . ']';
        $title = str_replace('-', ' ', $title);

        $UPLOAD_URL_PATH = str_replace($root_path, '',  $UPLOAD_PATH);
        if (file_exists($UPLOAD_PATH . '/logo.png')) {
            $logo = $UPLOAD_URL_PATH . '/logo.png';
        } else {
            $logo = $UPLOAD_URL_PATH . '/logo.default.png';
        }

        if ($x) {
            $html = '
			<div id="page-wrap">
				<div id="address">
					<h3>' . $config['CompanyName'] . '</h3>
					' . $config['address'] . '<br>
					' . Lang::T('Phone Number') . ': ' . $config['phone'] . '<br>
				</div>
				<div id="logo"><img id="image" src="' . $logo . '" alt="logo" /></div>
			</div>
			<div id="header">' . Lang::T('All Transactions at Date') . ': ' . date($config['date_format'], strtotime($fdate)) . ' - ' . date($config['date_format'], strtotime($tdate)) . '</div>
			<table id="customers">
				<tr>
				<th>' . Lang::T('Username') . '</th>
				<th>' . Lang::T('Plan Name') . '</th>
				<th>' . Lang::T('Type') . '</th>
				<th>' . Lang::T('Plan Price') . '</th>
				<th>' . Lang::T('Created On') . '</th>
				<th>' . Lang::T('Expires On') . '</th>
				<th>' . Lang::T('Method') . '</th>
				<th>' . Lang::T('Routers') . '</th>
				</tr>';
            $c = true;
            foreach ($x as $value) {

                $username = $value['username'];
                $plan_name = $value['plan_name'];
                $type = $value['type'];
                $price = $config['currency_code'] . ' ' . number_format($value['price'], 0, $config['dec_point'], $config['thousands_sep']);
                $recharged_on = date($config['date_format'], strtotime($value['recharged_on']));
                $expiration = date($config['date_format'], strtotime($value['expiration']));
                $time = $value['time'];
                $method = $value['method'];
                $routers = $value['routers'];

                $html .= "<tr" . (($c = !$c) ? ' class="alt"' : ' class=""') . ">" . "
				<td>$username</td>
				<td>$plan_name</td>
				<td>$type</td>
				<td align='right'>$price</td>
				<td>$recharged_on </td>
				<td>$expiration $time </td>
				<td>$method</td>
				<td>$routers</td>
				</tr>";
            }
            $html .= '</table>
			<h4 class="text-uppercase text-bold">' . Lang::T('Total Income') . ':</h4>
			<h3 class="sum">' . $config['currency_code'] . ' ' . number_format($xy, 2, $config['dec_point'], $config['thousands_sep']) . '</h3>';

            run_hook('pdf_by_period'); #HOOK
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->SetProtection(array('print'));
            $mpdf->SetTitle($config['CompanyName'] . ' Reports');
            $mpdf->SetAuthor($config['CompanyName']);
            $mpdf->SetWatermarkText($d['price']);
            $mpdf->showWatermarkText = true;
            $mpdf->watermark_font = 'Helvetica';
            $mpdf->watermarkTextAlpha = 0.1;
            $mpdf->SetDisplayMode('fullpage');

            $style = '<style>
			#page-wrap { width: 100%; margin: 0 auto; }
			#header { text-align: center; position: relative; color: black; font: bold 15px Helvetica, Sans-Serif;  margin-top: 10px; margin-bottom: 10px;}

			#address { width: 300px; float: left; }
			#logo { text-align: right; float: right; position: relative; margin-top: 15px; border: 5px solid #fff; overflow: hidden; }

			#customers
			{
			font-family: Helvetica, sans-serif;
			width:100%;
			border-collapse:collapse;
			}
			#customers td, #customers th
			{
			font-size:0.8em;
			border:1px solid #98bf21;
			padding:3px 5px 2px 5px;
			}
			#customers th
			{
			font-size:0.8em;
			text-align:left;
			padding-top:5px;
			padding-bottom:4px;
			background-color:#A7C942;
			color:#fff;
			}
			#customers tr.alt td
			{
			color:#000;
			background-color:#EAF2D3;
			}
			</style>';

            $nhtml = <<<EOF
$style
$html
EOF;
            $mpdf->WriteHTML($nhtml);
            $mpdf->Output(date('Ymd_His') . '.pdf', 'D');
        } else {
            echo 'No Data';
        }

        break;

    default:
        $ui->display('a404.tpl');
}
