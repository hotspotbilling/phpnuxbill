<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'Plugin Manager');
$ui->assign('_system_menu', 'settings');

$plugin_repository = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';

$action = $routes['1'];
$ui->assign('_admin', $admin);


if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'),'danger', "dashboard");
}

$cache = File::pathFixer('system/cache/plugin_repository.json');
if (file_exists($cache) && time() - filemtime($cache) < (24 * 60 * 60)) {
    $txt = file_get_contents($cache);
    $json = json_decode($txt, true);
    if(empty($json['plugins']) && empty($json['payment_gateway'])){
        unlink($cache);
        r2(U . 'dashboard', 'd', $txt);
    }
} else {
    $data = Http::getData($plugin_repository);
    file_put_contents($cache, $data);
    $json = json_decode($data, true);
}

switch ($action) {

    case 'install':
        if(!is_writeable(File::pathFixer('system/cache/'))){
            r2(U . "pluginmanager", 'e', 'Folder system/cache/ is not writable');
        }
        if(!is_writeable(File::pathFixer('system/plugin/'))){
            r2(U . "pluginmanager", 'e', 'Folder system/plugin/ is not writable');
        }
        set_time_limit(-1);
        $tipe = $routes['2'];
        $plugin = $routes['3'];
        $file = File::pathFixer('system/cache/') . $plugin . '.zip';
        if (file_exists($file)) unlink($file);
        if ($tipe == 'plugin') {
            foreach ($json['plugins'] as $plg) {
                if ($plg['id'] == $plugin) {
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'].'/archive/refs/heads/master.zip');
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $zip = new ZipArchive();
                    $zip->open($file);
                    $zip->extractTo(File::pathFixer('system/cache/'));
                    $zip->close();
                    $folder = File::pathFixer('system/cache/' . $plugin.'-main/');
                    if(!file_exists($folder)){
                        $folder = File::pathFixer('system/cache/' . $plugin.'-master/');
                    }
                    if(!file_exists($folder)){
                        r2(U . "pluginmanager", 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, File::pathFixer('system/plugin/'), ['README.md','LICENSE']);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(U . "pluginmanager", 's', 'Plugin '.$plugin.' has been installed');
                    break;
                }
            }
            break;
        } else if ($tipe == 'payment') {
            foreach ($json['payment_gateway'] as $plg) {
                if ($plg['id'] == $plugin) {
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'].'/archive/refs/heads/master.zip');
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $zip = new ZipArchive();
                    $zip->open($file);
                    $zip->extractTo(File::pathFixer('system/cache/'));
                    $zip->close();
                    $folder = File::pathFixer('system/cache/' . $plugin.'-main/');
                    if(!file_exists($folder)){
                        $folder = File::pathFixer('system/cache/' . $plugin.'-master/');
                    }
                    if(!file_exists($folder)){
                        r2(U . "pluginmanager", 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, File::pathFixer('system/paymentgateway/'), ['README.md','LICENSE']);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(U . "paymentgateway", 's', 'Payment Gateway '.$plugin.' has been installed');
                    break;
                }
            }
            break;
        }
    default:
        if (class_exists('ZipArchive')) {
            $zipExt = true;
        } else {
            $zipExt = false;
        }
        $ui->assign('zipExt', $zipExt);
        $ui->assign('plugins', $json['plugins']);
        $ui->assign('pgs', $json['payment_gateway']);
        $ui->display('plugin-manager.tpl');
}
