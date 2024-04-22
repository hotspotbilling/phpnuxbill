<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * This script is for updating PHPNuxBill
 **/
session_start();
include "config.php";

if (empty($update_url)) {
    $update_url = 'https://github.com/hotspotbilling/phpnuxbill/archive/refs/heads/master.zip';
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

$step = $_GET['step'];
$continue = true;
if (!extension_loaded('zip')) {
    $msg = "No PHP ZIP extension is available";
    $msgType = "danger";
    $continue = false;
}


$file = pathFixer('system/cache/phpnuxbill.zip');
$folder = pathFixer('system/cache/phpnuxbill-' . basename($update_url, ".zip") . '/');

if (empty($step)) {
    $step++;
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
        $step++;
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
        $step++;
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
        $step++;
    } else {
        $msg = "Failed to install update file.";
        $msgType = "danger";
        $continue = false;
    }
} else if ($step == 4) {
    if (file_exists("system/updates.json")) {
        require 'config.php';
        $db = new pdo(
            "mysql:host=$db_host;dbname=$db_name",
            $db_user,
            $db_password,
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
    $step++;
} else {
    $path = 'ui/compiled/';
    $files = scandir($path);
    foreach ($files as $file) {
        if (is_file($path . $file)) {
            unlink($path . $file);
        }
    }
    $version = json_decode(file_get_contents('version.json'), true)['version'];
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

    <?php if ($continue) { ?>
        <meta http-equiv="refresh" content="3; ./update.php?step=<?= $step ?>">
    <?php } ?>
    <style>
        ::-moz-selection {
            /* Code for Firefox */
            color: red;
            background: yellow;
        }

        ::selection {
            color: red;
            background: yellow;
        }
    </style>

</head>

<body class="hold-transition skin-blue">
    <div class="container">
        <section class="content-header">
            <h1 class="text-center">
                Update PHPNuxBill
            </h1>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-4">
                    <?php if (!empty($msgType) && !empty($msg)) { ?>
                        <div class="alert alert-<?= $msgType ?>" role="alert">
                            <?= $msg ?>
                        </div>
                    <?php } ?>
                    <?php if ($continue || $step == 5) { ?>
                        <?php if ($step == 1) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 1</div>
                                <div class="panel-body">
                                    Downloading update<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($step == 2) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 2</div>
                                <div class="panel-body">
                                    extracting<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($step == 3) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 3</div>
                                <div class="panel-body">
                                    Installing<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($step == 4) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 4</div>
                                <div class="panel-body">
                                    Updating database...
                                </div>
                            </div>
                        <?php } else if ($step == 5) { ?>
                            <div class="panel panel-success">
                                <div class="panel-heading">Update Finished</div>
                                <div class="panel-body">
                                    PHPNuxBill has been updated to Version <b><?= $version ?></b>
                                </div>
                            </div>
                            <meta http-equiv="refresh" content="5; ./index.php?_route=dashboard">
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </section>
        <footer class="footer text-center">
            PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener" target="_blank">iBNuX</a>
        </footer>
    </div>
</body>

</html>