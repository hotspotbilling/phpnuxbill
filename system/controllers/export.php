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
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
        $reset_day = $config['reset_day'];
        if (empty($reset_day)) {
            $reset_day = 1;
        }
        //first day of month
        if (date("d") >= $reset_day) {
            $start_date = date('Y-m-' . $reset_day);
        } else {
            $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
        }
        $tps = ($_GET['tps']) ? $_GET['tps'] : $types;
        $mts = ($_GET['mts']) ? $_GET['mts'] : $methods;
        $rts = ($_GET['rts']) ? $_GET['rts'] : $routers;
        $plns = ($_GET['plns']) ? $_GET['plns'] : $plans;
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');

        $query = ORM::for_table('tbl_transactions')
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
            ->order_by_desc('id');
        if (count($tps) > 0) {
            $query->where_in('type', $tps);
        }
        if (count($mts) > 0) {
            if (count($mts) != count($methods)) {
                foreach ($mts as $mt) {
                    $query->where_like('method', "$mt - %");
                }
            }
        }
        if (count($rts) > 0) {
            $query->where_in('routers', $rts);
        }
        if (count($plns) > 0) {
            $query->where_in('plan_name', $plns);
        }
        $x =  $query->find_array();
        $xy =  $query->sum('price');

        $ui->assign('sd', $sd);
        $ui->assign('ed', $ed);
        $ui->assign('ts', $ts);
        $ui->assign('te', $te);
        $ui->assign('d', $x);
        $ui->assign('dr', $xy);
        $ui->assign('mdate', $mdate);
        $ui->assign('recharged_on', $mdate);
        run_hook('print_by_date'); #HOOK
        $ui->display('print-by-date.tpl');
        break;

    case 'pdf-by-date':
        $mdate = date('Y-m-d');
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
        $reset_day = $config['reset_day'];
        if (empty($reset_day)) {
            $reset_day = 1;
        }
        //first day of month
        if (date("d") >= $reset_day) {
            $start_date = date('Y-m-' . $reset_day);
        } else {
            $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
        }
        $tps = ($_GET['tps']) ? $_GET['tps'] : $types;
        $mts = ($_GET['mts']) ? $_GET['mts'] : $methods;
        $rts = ($_GET['rts']) ? $_GET['rts'] : $routers;
        $plns = ($_GET['plns']) ? $_GET['plns'] : $plans;
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');

        $query = ORM::for_table('tbl_transactions')
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
            ->order_by_desc('id');
        if (count($tps) > 0) {
            $query->where_in('type', $tps);
        }
        if (count($mts) > 0) {
            if (count($mts) != count($methods)) {
                foreach ($mts as $mt) {
                    $query->where_like('method', "$mt - %");
                }
            }
        }
        if (count($rts) > 0) {
            $query->where_in('routers', $rts);
        }
        if (count($plns) > 0) {
            $query->where_in('plan_name', $plns);
        }
        $x =  $query->find_array();
        $xy =  $query->sum('price');

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
			<div id="header">' . Lang::T('All Transactions at Date') . ': ' . Lang::dateAndTimeFormat($sd, $ts) .' - '. Lang::dateAndTimeFormat($ed, $te) . '</div>
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
                $recharged_on = Lang::dateAndTimeFormat($value['recharged_on'], $value['recharged_time']);
                $expiration = Lang::dateAndTimeFormat($value['expiration'], $value['time']);
                $time = $value['time'];
                $method = $value['method'];
                $routers = $value['routers'];

                $html .= "<tr" . (($c = !$c) ? ' class="alt"' : ' class=""') . ">" . "
				<td>$username</td>
				<td>$plan_name</td>
				<td>$type</td>
				<td align='right'>$price</td>
				<td>$recharged_on</td>
				<td>$expiration</td>
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
            $mpdf->Output('phpnuxbill_reports_'.date('Ymd_His') . '.pdf', 'D');
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
