<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * This script is for updating PHPNuxBill
 **/
session_start();
include "config.php";

if($db_password != null && ($db_pass == null || empty($db_pass))){
    // compability for old version
    $db_pass = $db_password;
}

if (empty($update_url)) {
    $update_url = 'https://github.com/hotspotbilling/phpnuxbill/archive/refs/heads/master.zip';
}

if(isset($_REQUEST['update_url']) && !empty($_REQUEST['update_url'])){
    $update_url = $_REQUEST['update_url'];
    $_SESSION['update_url'] = $update_url;
}

if(isset($_SESSION['update_url']) && !empty($_SESSION['update_url']) && $_SESSION['update_url'] != $update_url){
    $update_url = $_SESSION['update_url'];
}

if (!isset($_SESSION['aid']) || empty($_SESSION['aid'])) {
    r2("./?_route=login&You_are_not_admin", 'e', 'You are not admin');
}

set_time_limit(-1);

if (!is_writeable(pathFixer('system/cache/'))) {
    r2("./?_route=community", 'e', 'Folder system/cache/ is not writable');
}
if (!is_writeable(pathFixer('.'))) {
    r2("./?_route=community", 'e', 'Folder web is not writable');
}

$step = isset($_GET['step']) ? $_GET['step'] : '';
$continue = true;
if (!extension_loaded('zip')) {
    $msg = "No PHP ZIP extension is available";
    $msgType = "danger";
    $continue = false;
}

$file = pathFixer('system/cache/phpnuxbill.zip');
$folder = pathFixer('system/cache/phpnuxbill-' . basename($update_url, ".zip") . '/');

if (empty($step)) {
    $step = 1;
} else if ($step == 1) {
    if (file_exists($file)) unlink($file);

    // Download update
    $fp = fopen($file, 'w+');
    $ch = curl_init($update_url);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    if (file_exists($file)) {
        $step = 2;
    } else {
        $msg = "Failed to download Update file";
        $msgType = "danger";
        $continue = false;
    }
} else if ($step == 2) {
    $zip = new ZipArchive();
    $zip->open($file);
    $zip->extractTo(pathFixer('system/cache/'));
    $zip->close();
    if (file_exists($folder)) {
        $step = 3;
    } else {
        $msg = "Failed to extract update file";
        $msgType = "danger";
        $continue = false;
    }
    // remove downloaded zip
    if (file_exists($file)) unlink($file);
} else if ($step == 3) {
    deleteFolder('system/autoload/');
    deleteFolder('system/vendor/');
    deleteFolder('ui/ui/');
    copyFolder($folder, pathFixer('./'));
    deleteFolder('install/');
    deleteFolder($folder);
    if (!file_exists($folder . pathFixer('/system/'))) {
        $step = 4;
    } else {
        $msg = "Failed to install update file.";
        $msgType = "danger";
        $continue = false;
    }
} else if ($step == 4) {
    if (file_exists("system/updates.json")) {
        $db = new pdo(
            "mysql:host=$db_host;dbname=$db_name",
            $db_user,
            $db_pass,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );

        $updates = json_decode(file_get_contents("system/updates.json"), true);
        $dones = [];
        if (file_exists("system/cache/updates.done.json")) {
            $dones = json_decode(file_get_contents("system/cache/updates.done.json"), true);
        }
        foreach ($updates as $version => $queries) {
            if (!in_array($version, $dones)) {
                foreach ($queries as $q) {
                    try {
                        $db->exec($q);
                    } catch (PDOException $e) {
                        //ignore, it exists already
                    }
                }
                $dones[] = $version;
            }
        }
        file_put_contents("system/cache/updates.done.json", json_encode($dones));
    }
    $step = 5;
} else if ($step == 5) {
    $path = 'ui/compiled/';
    if (is_dir($path)) {
        $files = scandir($path);
        foreach ($files as $file) {
            if (is_file($path . $file)) {
                unlink($path . $file);
            }
        }
    }
    $version_data = json_decode(file_get_contents('version.json'), true);
    $version = isset($version_data['version']) ? $version_data['version'] : 'Latest';
    $continue = false;
}

function pathFixer($path)
{
    return str_replace("/", DIRECTORY_SEPARATOR, $path);
}

function r2($to, $ntype = 'e', $msg = '')
{
    if ($msg == '') {
        header("location: $to");
        die();
    }
    $_SESSION['ntype'] = $ntype;
    $_SESSION['notify'] = $msg;
    header("location: $to");
    die();
}

function copyFolder($from, $to, $exclude = [])
{
    if (!is_dir($from)) return;
    $files = scandir($from);
    foreach ($files as $file) {
        if (is_file($from . $file) && !in_array($file, $exclude)) {
            if (file_exists($to . $file)) unlink($to . $file);
            rename($from . $file, $to . $file);
        } else if (is_dir($from . $file) && !in_array($file, ['.', '..'])) {
            if (!file_exists($to . $file)) {
                mkdir($to . $file);
            }
            copyFolder($from . $file . DIRECTORY_SEPARATOR, $to . $file . DIRECTORY_SEPARATOR);
        }
    }
}

function deleteFolder($path)
{
    if (!is_dir($path)) return;
    $files = scandir($path);
    foreach ($files as $file) {
        if (is_file($path . $file)) {
            unlink($path . $file);
        } else if (is_dir($path . $file) && !in_array($file, ['.', '..'])) {
            deleteFolder($path . $file . DIRECTORY_SEPARATOR);
            rmdir($path . $file);
        }
    }
    rmdir($path);
}

$version_data = file_exists('version.json') ? json_decode(file_get_contents('version.json'), true) : [];
$version = isset($version_data['version']) ? $version_data['version'] : '';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>PHPNuxBill Updater</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">

    <?php if ($continue && $step < 5) { ?>
        <meta http-equiv="refresh" content="3; ./update.php?step=<?= $step ?>">
    <?php } ?>
    
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
        }
        .update-wrapper {
            max-width: 550px;
            margin: 50px auto;
            padding: 0 15px;
        }
        .content-header h1 {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 26px;
        }
        .custom-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .custom-card-header {
            background: #1e293b;
            color: #ffffff;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .custom-card-header.success-header {
            background: #10b981;
        }
        .custom-card-body {
            padding: 25px 20px;
            text-align: center;
            font-size: 15px;
            color: #475569;
        }
        .spinner-container {
            margin: 15px auto;
        }
        .loader-icon {
            font-size: 32px;
            color: #3b82f6;
            animation: fa-spin 2s infinite linear;
        }
        .success-icon {
            font-size: 48px;
            color: #10b981;
            margin-bottom: 5px;
        }
        .alert {
            border-radius: 8px;
            border: none;
            font-size: 14px;
        }
        .footer-note {
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            margin-top: 30px;
        }
        .footer-note a {
            color: #3b82f6;
            text-decoration: none;
        }
        .btn-modern {
            background-color: #10b981;
            color: #fff;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 600;
            border: none;
            display: inline-block;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-modern:hover {
            background-color: #059669;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>

<body class="hold-transition skin-blue">
    <div class="update-wrapper">
        <section class="content-header text-center">
            <h1>
                <i class="ion-ios-cloud-download-outline" style="vertical-align: middle; margin-right: 8px;"></i> Update PHPNuxBill
            </h1>
        </section>

        <section class="content" style="padding: 0;">
            <?php if (!empty($msgType) && !empty($msg)) { ?>
                <div class="alert alert-<?= $msgType ?>" role="alert">
                    <i class="ion-alert-circled"></i> <?= $msg ?>
                </div>
            <?php } ?>

            <?php if ($step == 1) { ?>
                <div class="custom-card">
                    <div class="custom-card-header">Step 1 dari Proses Update</div>
                    <div class="custom-card-body">
                        <div class="spinner-container">
                            <i class="fa fa-spinner loader-icon"></i>
                        </div>
                        <p style="margin-top: 10px; margin-bottom: 0;">Mengunduh file pembaruan...<br><small style="color: #94a3b8;">Mohon tunggu beberapa saat, jangan menutup halaman ini.</small></p>
                    </div>
                </div>
            <?php } else if ($step == 2) { ?>
                <div class="custom-card">
                    <div class="custom-card-header">Step 2 dari Proses Update</div>
                    <div class="custom-card-body">
                        <div class="spinner-container">
                            <i class="fa fa-cog fa-spin loader-icon"></i>
                        </div>
                        <p style="margin-top: 10px; margin-bottom: 0;">Mengekstrak file pembaruan...<br><small style="color: #94a3b8;">Sedang mempersiapkan file sistem baru.</small></p>
                    </div>
                </div>
            <?php } else if ($step == 3) { ?>
                <div class="custom-card">
                    <div class="custom-card-header">Step 3 dari Proses Update</div>
                    <div class="custom-card-body">
                        <div class="spinner-container">
                            <i class="fa fa-refresh fa-spin loader-icon"></i>
                        </div>
                        <p style="margin-top: 10px; margin-bottom: 0;">Menginstal pembaruan sistem...<br><small style="color: #94a3b8;">Menimpa file lama dengan versi terbaru.</small></p>
                    </div>
                </div>
            <?php } else if ($step == 4) { ?>
                <div class="custom-card">
                    <div class="custom-card-header">Step 4 dari Proses Update</div>
                    <div class="custom-card-body">
                        <div class="spinner-container">
                            <i class="fa fa-database fa-spin loader-icon" style="color: #f59e0b;"></i>
                        </div>
                        <p style="margin-top: 10px; margin-bottom: 0;">Memperbarui struktur database...<br><small style="color: #94a3b8;">Menyesuaikan tabel dengan versi terbaru.</small></p>
                    </div>
                </div>
            <?php } else if ($step == 5) { ?>
                <div class="custom-card">
                    <div class="custom-card-header success-header">Pembaruan Selesai</div>
                    <div class="custom-card-body">
                        <div class="spinner-container">
                            <i class="ion-checkmark-circled success-icon"></i>
                        </div>
                        <p style="margin-top: 5px; margin-bottom: 20px; font-size: 16px;">PHPNuxBill berhasil diperbarui ke Versi <b style="color: #1e293b;"><?= $version ?></b></p>
                        <a href="./?_route=dashboard" class="btn btn-modern">Ke Dashboard Sekarang</a>
                    </div>
                </div>
                <meta http-equiv="refresh" content="5; ./?_route=dashboard">
            <?php } ?>
        </section>

        <footer class="footer-note">
            PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener" target="_blank">iBNuX</a>
        </footer>
    </div>
</body>

</html>
