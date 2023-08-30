<!DOCTYPE html>
<html lang="en">

<head>
    <title>PHPNuxBill Updaters</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link type='text/css' href='css/style.css' rel='stylesheet' />
    <link type='text/css' href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body style='background-color: #FBFBFB;'>
    <div id='main-container'>
        <img src="img/logo.png" class="img-responsive" alt="Logo" />
        <hr>

        <div class="span12">
            <h4> PHPNuxBill Updater </h4>
            <pre><?php
            include '../config.php';
            try{
                $dbh = new pdo( "mysql:host=$db_host;dbname=$db_name",
                    "$db_user",
                    "$db_password",
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

                echo "CREATE TABLE `tbl_payment_gateway` (
    `id` int(11) NOT NULL,
    `username` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    `gateway` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'xendit | midtrans',
    `gateway_trx_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    `plan_id` int(11) NOT NULL,
    `plan_name` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
    `routers_id` int(11) NOT NULL,
    `routers` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    `price` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
    `pg_url_payment` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    `payment_method` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    `payment_channel` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    `pg_request` text COLLATE utf8mb4_general_ci,
    `pg_paid_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `expired_date` datetime DEFAULT NULL,
    `created_date` datetime NOT NULL,
    `paid_date` datetime DEFAULT NULL,
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 unpaid 2 paid 3 failed 4 canceled'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;\n\n";
                    $dbh->exec("CREATE TABLE
                        `tbl_payment_gateway` (
                            `id` int(11) NOT NULL,
                            `username` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
                            `gateway` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'xendit | midtrans',
                            `gateway_trx_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
                            `plan_id` int(11) NOT NULL,
                            `plan_name` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
                            `routers_id` int(11) NOT NULL,
                            `routers` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
                            `price` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
                            `pg_url_payment` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
                            `payment_method` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
                            `payment_channel` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
                            `pg_request` text COLLATE utf8mb4_general_ci,
                            `pg_paid_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
                            `expired_date` datetime DEFAULT NULL,
                            `created_date` datetime NOT NULL,
                            `paid_date` datetime DEFAULT NULL,
                            `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 unpaid 2 paid 3 failed 4 canceled'
                        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;");

                    echo "ALTER TABLE `tbl_payment_gateway` ADD PRIMARY KEY (`id`);\n\n";
                    $dbh->exec("ALTER TABLE `tbl_payment_gateway` ADD PRIMARY KEY (`id`);");
                    echo "ALTER TABLE `tbl_payment_gateway` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;\n\n";
                    $dbh->exec("ALTER TABLE `tbl_payment_gateway` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");

                    echo "ALTER TABLE `tbl_customers` ADD `email` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `phonenumber`;\n\n";
                    $dbh->exec("ALTER TABLE `tbl_customers` ADD `email` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `phonenumber`;");

                    echo "ALTER TABLE `tbl_plans` CHANGE `validity_unit` `validity_unit` ENUM('Mins','Hrs','Days','Months') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;\n\n";
                    $dbh->exec("ALTER TABLE `tbl_plans` CHANGE `validity_unit` `validity_unit` ENUM('Mins','Hrs','Days','Months') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
                    echo "ALTER TABLE `tbl_plans` ADD `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 disabled' AFTER `pool`;\n\n";
                    $dbh->exec("ALTER TABLE `tbl_plans` ADD `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 disabled' AFTER `pool`;");

                    echo "ALTER TABLE `tbl_routers` ADD `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 disabled' AFTER `description`;\n\n";
                    $dbh->exec("ALTER TABLE `tbl_routers` ADD `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 disabled' AFTER `description`;");
                    echo "ALTER TABLE `tbl_routers` CHANGE `description` `description` VARCHAR(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;";
                    $dbh->exec("ALTER TABLE `tbl_routers` CHANGE `description` `description` VARCHAR(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;");

                    echo "ALTER TABLE `tbl_user_recharges` CHANGE `method` `method` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';\n\n";
                    $dbh->exec("ALTER TABLE `tbl_user_recharges` CHANGE `method` `method` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';");
                    echo "ALTER TABLE `tbl_transactions` CHANGE `method` `method` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;\n\n";
                    $dbh->exec("ALTER TABLE `tbl_transactions` CHANGE `method` `method` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;");
                    echo "Success update database for new system <a href='/admin/'>Back To Home</a>";
            }catch(PDOException $ex){
                echo "Error Failed to connect to database: ".$ex->getMessage()."\n";
            }
            ?></pre>
        </div>
    </div>
    <div class="footer">Copyright &copy; 2021 PHPNuxBill. All Rights Reserved<br /><br /></div>
</body>

</html>