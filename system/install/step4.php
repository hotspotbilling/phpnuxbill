<?php
/**
* PHP Mikrotik Billing (www.phpmixbill.com)
* Ismail Marzuqi <iesien22@yahoo.com>
* @version		5.0
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt
* @donate		PayPal: iesien22@yahoo.com / Bank Mandiri: 130.00.1024957.4
**/
//error_reporting (0);
$appurl = $_POST['appurl'];
$db_host = $_POST['dbhost'];
$db_user = $_POST['dbuser'];
$db_password = $_POST['dbpass'];
$db_name = $_POST['dbname'];
$cn = '0';
try{
    $dbh = new pdo( "mysql:host=$db_host;dbname=$db_name",
        "$db_user",
        "$db_password",
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
   $cn = '1';
}
catch(PDOException $ex){
   $cn = '0';
}

if ($cn == '1') {
    $input = '<?php
	$db_host	    = \'' . $db_host . '\';
	$db_user        = \'' . $db_user . '\';
	$db_password	= \'' . $db_password . '\';
	$db_name	    = \'' . $db_name . '\';
	define(\'APP_URL\', \'' . $appurl . '\');
	$_app_stage = \'Live\';
	';
    $wConfig = "../config.php";
    $fh = fopen($wConfig, 'w') or die("Can't create config file, your server does not support 'fopen' function,
	please create a file named - config.php with following contents- <br/>$input");
	
    fwrite($fh, $input);
    fclose($fh);

    $sql = file_get_contents('phpmixbill.sql');

    $qr = $dbh->exec($sql);

} else {
    header("location: step3.php?_error=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>PHPMixBill v5.0 Installer</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link type='text/css' href='css/style.css' rel='stylesheet'/>
    <link type='text/css' href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body style='background-color: #FBFBFB;'>
	<div id='main-container'>
		<div class='header'>
			<div class="header-box wrapper">
				<div class="hd-logo"><a href="#"><img src="img/logo.png" alt="Logo"/></a></div>
			</div>
		</div>

		<div class="span12">
			<h4> PHPMixBill v5.0 Installer </h4>
			<?php
			if ($cn == '1') {
			?>
				<p><strong>Config File Created and Database Imported.</strong><br></p>
				<form action="step5.php" method="post">
					<fieldset>
						<legend>Click Continue</legend>
						<button type='submit' class='btn btn-primary'>Continue</button>
					</fieldset>
				</form>
			<?php
			} elseif ($cn == '2') {
			?>
				<p> MySQL Connection was successfull. An error occured while adding data on MySQL. Unsuccessfull
					Installation. Please refer manual installation in the website phpmixbill.com or Contact iesien22@yahoo.com for
					helping on installation</p>
			<?php
			} else {
			?>
			<p> MySQL Connection Failed.</p>
			<?php
			}
			?>
		</div>
	</div>

	<div class="footer">Copyright &copy; 2015 PHPMixBill. All Rights Reserved<br/><br/></div>
</body>
</html>

