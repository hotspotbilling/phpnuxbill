<!DOCTYPE html>
<html lang="en">
<head>
    <title>PHPNuxBill  Installer</title>
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
        <img src="img/logo.png" class="img-responsive" alt="Logo" />
        <hr>

		<div class="span12">
			<h4> PHPNuxBill  Installer </h4>
			<?php
			if (isset($_GET['_error']) && ($_GET['_error']) == '1') {
				echo '<h4 style="color: red;"> Unable to Connect Database, Please make sure database info is correct and try again ! </h4>';
			}//

			$cururl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')|| $_SERVER['SERVER_PORT'] == 443)?'https':'http').'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$appurl = str_replace('/install/step3.php', '', $cururl);
			$appurl = str_replace('?_error=1', '', $appurl);
			$appurl = str_replace('/system', '', $appurl);
			?>

			<form action="step4.php" method="post">
				<fieldset>
					<legend>Database Connection &amp Site config</legend>

					<div class="form-group">
						<label for="appurl">Application URL</label>
						<input type="text" class="form-control" id="appurl" name="appurl" value="<?php echo $appurl; ?>">
						<span class='help-block'>Application url without trailing slash at the end of url (e.g. http://172.16.10.10). Please keep default, if you are unsure.</span>
					</div>
					<div class="form-group">
						<label for="dbhost">Database Host</label>
						<input type="text" class="form-control" id="dbhost" name="dbhost">
					</div>
					<div class="form-group">
						<label for="dbuser">Database Username</label>
						<input type="text" class="form-control" id="dbuser" name="dbuser">
					</div>
					<div class="form-group">
						<label for="dbpass">Database Password</label>
						<input type="text" class="form-control" id="dbpass" name="dbpass">
					</div>

					<div class="form-group">
						<label for="dbname">Database Name</label>
						<input type="text" class="form-control" id="dbname" name="dbname">
					</div>

                    <div class="form-group">
						<label for="dbname"><input type="checkbox" class="form-control" id="radius" name="radius" value="yes"> Install Radius Table?</label>

					</div>

					<button type="submit" class="btn btn-primary">Submit</button>
				</fieldset>
			</form>
		</div>
	</div>
	<div class="footer">Copyright &copy; 2021 PHPNuxBill. All Rights Reserved<br/><br/></div>
</body>
</html>

