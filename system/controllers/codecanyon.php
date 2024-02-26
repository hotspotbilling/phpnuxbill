<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'CodeCanyon.net');
$ui->assign('_system_menu', 'settings');

$plugin_repository = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';

$action = $routes['1'];
$ui->assign('_admin', $admin);
$cache = File::pathFixer($CACHE_PATH . '/codecanyon.json');

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}
if (empty($config['envato_token'])) {
    r2(U . 'settings/app', 'w', '<a href="' . U . 'settings/app#envato' . '">Envato Personal Access Token</a> is not set');
}

switch ($action) {

    case 'install':
        if (!is_writeable(File::pathFixer($CACHE_PATH . '/'))) {
            r2(U . "codecanyon", 'e', 'Folder system/cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(U . "codecanyon", 'e', 'Folder plugin/ is not writable');
        }
        if (!is_writeable($PAYMENTGATEWAY_PATH)) {
            r2(U . "codecanyon", 'e', 'Folder paymentgateway/ is not writable');
        }
        set_time_limit(-1);
        $item_id = $routes['2'];
        $tipe = $routes['3'];
        $result = Http::getData('https://api.envato.com/v3/market/buyer/download?item_id=' . $item_id, ['Authorization: Bearer ' . $config['envato_token']]);
        $json = json_decode($result, true);
        if (!isset($json['download_url'])) {
            r2(U . 'codecanyon', 'e', 'Failed to get download url. ' . $json['description']);
        }
        $file = File::pathFixer($CACHE_PATH . '/codecanyon/');
        if (!file_exists($file)) {
            mkdir($file);
        }
        $file .= $item_id . '.zip';
        if (file_exists($file))
            unlink($file);
        //download
        $fp = fopen($file, 'w+');
        $ch = curl_init($json['download_url']);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        //extract
        $target = File::pathFixer($CACHE_PATH . '/codecanyon/' . $item_id . '/');
        $zip = new ZipArchive();
        $zip->open($file);
        $zip->extractTo($target);
        $zip->close();
        //moving
        if (file_exists($target . 'plugin')) {
            File::copyFolder($target . 'plugin', $PLUGIN_PATH . DIRECTORY_SEPARATOR);
        } else if (file_exists($target . 'paymentgateway')) {
            File::copyFolder($target . 'paymentgateway', $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
        } else if (file_exists($target . 'theme')) {
            File::copyFolder($target . 'theme', File::pathFixer('ui/themes/'));
        }
        //Cleaning
        File::deleteFolder($target);
        unlink($file);
        r2(U . "codecanyon", 's', 'Installation success');
    case 'reload':
        if (file_exists($cache))
            unlink($cache);
    default:
        if (class_exists('ZipArchive')) {
            $zipExt = true;
        } else {
            $zipExt = false;
        }
        $ui->assign('zipExt', $zipExt);

        if (file_exists($cache) && time() - filemtime($cache) < (24 * 60 * 60)) {
            $txt = file_get_contents($cache);
            $plugins = json_decode($txt, true);
            $ui->assign('chached_until', date($config['date_format'] . ' H:i', filemtime($cache) + (24 * 60 * 60)));
            if (count($plugins) == 0) {
                unlink($cache);
                r2(U . 'codecanyon');
            }
        } else {
            $plugins = [];
            $page = _get('page', 1);
            back:
            $result = Http::getData('https://api.envato.com/v3/market/buyer/list-purchases?&page=' . $page, ['Authorization: Bearer ' . $config['envato_token']]);
            $items = json_decode($result, true);
            if ($items && count($items['results']) > 0) {
                foreach ($items['results'] as $item) {
                    $name = strtolower($item['item']['name']);
                    if (strpos($name, 'phpnuxbill') !== false) {
                        $plugins[] = $item;
                    }
                }
                $page++;
                goto back;
            }
            if (count($plugins) > 0) {
                file_put_contents($cache, json_encode($plugins));
                if (file_exists($cache)) {
                    $ui->assign('chached_until', date($config['date_format'] . ' H:i', filemtime($cache) + (24 * 60 * 60)));
                }
            }
        }
        $ui->assign('plugins', $plugins);
        $ui->display('codecanyon.tpl');
}
