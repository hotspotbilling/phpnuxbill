<!DOCTYPE html>
<html lang="en">
<head>
    <title>PHPMixBill  Installer</title>
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
			<h4> PHPMixBill  Installer </h4>
			<p>
				<strong>Congratulations!</strong><br>
				You have just install PHPMixBill !<br>
				To Login Admin Portal:<br>
				Use this link -
				<?php
				$cururl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')|| $_SERVER['SERVER_PORT'] == 443)?'https':'http').'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$appurl = str_replace('/install/step5.php', '', $cururl);
				$appurl = str_replace('/system', '', $appurl);
				echo '<a href="' . $appurl . '/admin">' . $appurl . '/admin</a>';
				?>
				<br>
				Username: admin<br>
				Password: admin<br>
				For security, Delete the <b>install</b> directory inside system folder.
			</p>
		</div>
	</div>
	<div class="footer">Copyright &copy; 2021 PHPMixBill. All Rights Reserved<br/><br/></div>
</body>
</html>