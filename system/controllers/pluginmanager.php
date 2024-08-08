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
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$cache = $CACHE_PATH . File::pathFixer('/plugin_repository.json');
if (file_exists($cache) && time() - filemtime($cache) < (24 * 60 * 60)) {
    $txt = file_get_contents($cache);
    $json = json_decode($txt, true);
    if (empty($json['plugins']) && empty($json['payment_gateway'])) {
        unlink($cache);
        r2(U . 'pluginmanager');
    }
} else {
    $data = Http::getData($plugin_repository);
    file_put_contents($cache, $data);
    $json = json_decode($data, true);
}
switch ($action) {
    case 'refresh':
        if (file_exists($cache)) unlink($cache);
        r2(U . "pluginmanager", 's', 'Refresh success');
        break;
    case 'dlinstall':
        if ($_app_stage == 'demo') {
            r2(U . "pluginmanager", 'e', 'Demo Mode cannot install as it Security risk');
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(U . "pluginmanager", 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(U . "pluginmanager", 'e', 'Folder plugin/ is not writable');
        }
        if (!is_writeable($DEVICE_PATH)) {
            r2(U . "pluginmanager", 'e', 'Folder devices/ is not writable');
        }
        if (!is_writeable($UI_PATH . DIRECTORY_SEPARATOR . 'themes')) {
            r2(U . "pluginmanager", 'e', 'Folder themes/ is not writable');
        }
        $cache = $CACHE_PATH . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR;
        if (!file_exists($cache)) {
            mkdir($cache);
        }
        if (file_exists($_FILES['zip_plugin']['tmp_name'])) {
            $zip = new ZipArchive();
            $zip->open($_FILES['zip_plugin']['tmp_name']);
            $zip->extractTo($cache);
            $zip->close();
            $plugin = basename($_FILES['zip_plugin']['name']);
            unlink($_FILES['zip_plugin']['tmp_name']);
            $success = 0;
            //moving
            if (file_exists($cache . 'plugin')) {
                File::copyFolder($cache . 'plugin' . DIRECTORY_SEPARATOR, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'paymentgateway')) {
                File::copyFolder($cache . 'paymentgateway' . DIRECTORY_SEPARATOR, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'theme')) {
                File::copyFolder($cache . 'theme' . DIRECTORY_SEPARATOR, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'device')) {
                File::copyFolder($cache . 'device' . DIRECTORY_SEPARATOR, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if ($success == 0) {
                // old plugin and theme using this
                $check = strtolower($ghUrl);
                if (strpos($check, 'plugin') !== false) {
                    File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'payment') !== false) {
                    File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'theme') !== false) {
                    rename($folder, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $plugin);
                } else if (strpos($check, 'device') !== false) {
                    File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                }
            }
            //Cleaning
            File::deleteFolder($cache);
            r2(U . "pluginmanager", 's', 'Installation success');
        } else if (_post('gh_url', '') != '') {
            $ghUrl = _post('gh_url', '');
            if (!empty($config['github_token']) && !empty($config['github_username'])) {
                $ghUrl = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $ghUrl);
            }
            $plugin = basename($ghUrl);
            $file = $cache . $plugin . '.zip';
            $fp = fopen($file, 'w+');
            $ch = curl_init($ghUrl . '/archive/refs/heads/master.zip');
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
            $zip->extractTo($cache);
            $zip->close();
            $folder = $cache . DIRECTORY_SEPARATOR . $plugin . '-main' . DIRECTORY_SEPARATOR;
            if(!file_exists($folder)) {
                $folder = $cache . DIRECTORY_SEPARATOR . $plugin . '-master' . DIRECTORY_SEPARATOR;
            }
            $success = 0;
            if (file_exists($folder . 'plugin')) {
                File::copyFolder($folder . 'plugin' . DIRECTORY_SEPARATOR, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'paymentgateway')) {
                File::copyFolder($folder . 'paymentgateway' . DIRECTORY_SEPARATOR, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'theme')) {
                File::copyFolder($folder . 'theme' . DIRECTORY_SEPARATOR, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'device')) {
                File::copyFolder($folder . 'device' . DIRECTORY_SEPARATOR, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if ($success == 0) {
                // old plugin and theme using this
                $check = strtolower($ghUrl);
                if (strpos($check, 'plugin') !== false) {
                    File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'payment') !== false) {
                    File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'theme') !== false) {
                    rename($folder, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $plugin);
                } else if (strpos($check, 'device') !== false) {
                    File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                }
            }
            File::deleteFolder($cache);
            r2(U . "pluginmanager", 's', 'Installation success');
        } else {
            r2(U . 'pluginmanager', 'e', 'Nothing Installed');
        }
        break;
    case 'delete':
        if (!is_writeable($CACHE_PATH)) {
            r2(U . "pluginmanager", 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(U . "pluginmanager", 'e', 'Folder plugin/ is not writable');
        }
        set_time_limit(-1);
        $tipe = $routes['2'];
        $plugin = $routes['3'];
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $plugin . '.zip';
        if (file_exists($file)) unlink($file);
        if ($tipe == 'plugin') {
            foreach ($json['plugins'] as $plg) {
                if ($plg['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $plg['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $plg['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'] . '/archive/refs/heads/master.zip');
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
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (!file_exists($folder)) {
                        r2(U . "pluginmanager", 'e', 'Extracted Folder is unknown');
                    }
                    scanAndRemovePath($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(U . "pluginmanager", 's', 'Plugin ' . $plugin . ' has been deleted');
                    break;
                }
            }
            break;
        }
        break;
    case 'install':
        if (!is_writeable($CACHE_PATH)) {
            r2(U . "pluginmanager", 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(U . "pluginmanager", 'e', 'Folder plugin/ is not writable');
        }
        set_time_limit(-1);
        $tipe = $routes['2'];
        $plugin = $routes['3'];
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $plugin . '.zip';
        if (file_exists($file)) unlink($file);
        if ($tipe == 'plugin') {
            foreach ($json['plugins'] as $plg) {
                if ($plg['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $plg['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $plg['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'] . '/archive/refs/heads/master.zip');
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
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (!file_exists($folder)) {
                        r2(U . "pluginmanager", 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(U . "pluginmanager", 's', 'Plugin ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        } else if ($tipe == 'payment') {
            foreach ($json['payment_gateway'] as $plg) {
                if ($plg['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $plg['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $plg['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'] . '/archive/refs/heads/master.zip');
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
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (!file_exists($folder)) {
                        r2(U . "pluginmanager", 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(U . "paymentgateway", 's', 'Payment Gateway ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        } else if ($tipe == 'device') {
            foreach ($json['devices'] as $d) {
                if ($d['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $d['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $d['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($d['github'] . '/archive/refs/heads/master.zip');
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
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (!file_exists($folder)) {
                        r2(U . "pluginmanager", 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(U . "settings/devices", 's', 'Device ' . $plugin . ' has been installed');
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
        $ui->assign('dvcs', $json['devices']);
        $ui->display('plugin-manager.tpl');
}


function scanAndRemovePath($source, $target)
{
    $files = scandir($source);
    foreach ($files as $file) {
        if (is_file($source . $file)) {
            if (file_exists($target . $file)) {
                unlink($target . $file);
            }
        } else if (is_dir($source . $file) && !in_array($file, ['.', '..'])) {
            scanAndRemovePath($source . $file . DIRECTORY_SEPARATOR, $target . $file . DIRECTORY_SEPARATOR);
            if (file_exists($target . $file)) {
                rmdir($target . $file);
            }
        }
    }
    if (file_exists($target)) {
        rmdir($target);
    }
}
