<!DOCTYPE html>
<html lang="en">

<head>
    <title>PHPNuxBill Installer</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link type='text/css' href='css/style.css' rel='stylesheet' />
    <link type='text/css' href="css/bootstrap.min.css" rel="stylesheet">
</head>
<?php if (!file_exists('../pages')) rename('../pages_template', '../pages'); ?>

<body style='background-color: #FBFBFB;'>
    <div id='main-container'>
        <img src="img/logo.png" class="img-responsive" alt="Logo" />
        <hr>
        <div class="span12">
            <h4> PHPNuxBill Installer </h4>
            <p>
                <strong>Congratulations!</strong><br>
                You have just install PHPNuxBill !<br><br>
                <span class="text-danger">But wait!!<br>
                    <ol>
                        <li>Don't forget to rename folder <b>pages_example</b> to <b>pages</b>.<br>
                            if it not yet renamed</li>
                        <li>Activate <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/Cron-Jobs" target="_blank">Cronjob</a> for Expired and Reminder.</li>
                        <li>Check <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/How-It-Works---Cara-kerja" target="_blank">how PHPNuxbill Works</a></li>
                        <li><a href="https://github.com/hotspotbilling/phpnuxbill/wiki#login-page-mikrotik" target="_blank">how to link Mikrotik Login to PHPNuxBill</a></li>
                        <li>or use <a href="https://github.com/hotspotbilling/phpnuxbill-mikrotik-login-template" target="_blank">Mikrotik Login Template for PHPNuxBill</a></li>
                    </ol>
                </span><br><br>
                To Login Admin Portal:<br>
                Use this link -
                <?php
                $cururl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
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
    <div class="footer">Copyright &copy; 2021 PHPNuxBill. All Rights Reserved<br /><br /></div>
</body>

</html>