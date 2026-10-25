<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Marketplace - Device Drivers'));
$ui->assign('_system_menu', 'marketplace');

$admin = Admin::_info();
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$action = ($routes[0] === 'marketplace') ? ($routes['2'] ?? 'list') : ($routes['1'] ?? 'list');
$slug = ($routes[0] === 'marketplace') ? ($routes['3'] ?? '') : ($routes['2'] ?? '');
if (empty($action)) {
    $action = 'list';
}

if ($routes[0] === 'marketplace_devices' && ($action === 'list' || empty($action))) {
    r2(getUrl('marketplace/devices'));
}

$stateChanging = in_array($action, ['install', 'uninstall', 'upload'], true);
if ($stateChanging && $_app_stage == 'Demo') {
    r2(getUrl('marketplace/devices'), 'e', Lang::T('You cannot perform this action in Demo mode'));
}
if ($stateChanging) {
    $csrf_token = _post('csrf_token');
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('marketplace/devices'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }
}

// Fetch device repository (cached 24h)
$plugin_repository = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';
$repoCache = $CACHE_PATH . File::pathFixer('/plugin_repository.json');
$repoJson = [];
if (file_exists($repoCache) && time() - filemtime($repoCache) < (24 * 60 * 60)) {
    $repoJson = json_decode(file_get_contents($repoCache), true) ?: [];
} else {
    $data = Http::getData($plugin_repository);
    if ($data !== false && $data !== '') {
        file_put_contents($repoCache, $data);
        $repoJson = json_decode($data, true) ?: [];
    } elseif (file_exists($repoCache)) {
        $repoJson = json_decode(file_get_contents($repoCache), true) ?: [];
    }
}
$repoDevices = $repoJson['devices'] ?? [];

// Scan installed device drivers
$devices = [];
$categories = [];
foreach (glob($DEVICE_PATH . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
    $className = basename($file, '.php');
    if ($className === 'readme' || $className === 'index') {
        continue;
    }
    $code = file_get_contents($file);

    $title = $className;
    $description = '';
    $author = '';
    if (preg_match("/'title'\s*=>\s*'([^']+)'/", $code, $m)) {
        $title = $m[1];
    }
    if (preg_match("/'description'\s*=>\s*'([^']+)'/", $code, $m)) {
        $description = $m[1];
    }
    if (preg_match("/'author'\s*=>\s*'([^']+)'/", $code, $m)) {
        $author = $m[1];
    }

    $category = 'Other';
    if (stripos($className, 'Mikrotik') === 0) {
        $category = 'Mikrotik';
    } elseif (stripos($className, 'Radius') === 0) {
        $category = 'Radius';
    } elseif (stripos($className, 'Voucher') === 0) {
        $category = 'Voucher';
    } elseif (stripos($className, 'Dummy') === 0) {
        $category = 'Dummy';
    }
    $categories[$category] = ($categories[$category] ?? 0) + 1;

    $devices[] = [
        'slug' => $className,
        'name' => $title,
        'description' => $description,
        'author' => $author,
        'category' => $category,
        'path' => $file,
        'size' => round(filesize($file) / 1024, 1) . ' KB',
    ];
}
usort($devices, fn($a, $b) => strcmp($a['name'], $b['name']));

// Determine installed device slugs (loose match)
$installedSlugs = [];
foreach ($devices as $d) {
    $installedSlugs[strtolower($d['slug'])] = true;
}

// Available devices from repository (not installed)
$available = [];
foreach ($repoDevices as $d) {
    $id = strtolower($d['id'] ?? '');
    $normId = preg_replace('/[^a-z0-9]/', '', $id);
    $isInstalled = false;
    foreach (array_keys($installedSlugs) as $installed) {
        $normInstalled = preg_replace('/[^a-z0-9]/', '', $installed);
        if ($normId === $normInstalled || strpos($normId, $normInstalled) !== false || strpos($normInstalled, $normId) !== false) {
            $isInstalled = true;
            break;
        }
    }
    if (!$isInstalled) {
        $available[] = $d;
    }
}

switch ($action) {
    case 'install':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $found = null;
            foreach ($repoDevices as $d) {
                if (strcasecmp($d['id'] ?? '', $slug) === 0) {
                    $found = $d;
                    break;
                }
            }
            if ($found) {
                if (!is_writeable($CACHE_PATH)) {
                    r2(getUrl('marketplace/devices'), 'e', Lang::T('Folder cache/ is not writable'));
                }
                if (!is_writeable($DEVICE_PATH)) {
                    r2(getUrl('marketplace/devices'), 'e', Lang::T('Folder devices/ is not writable'));
                }
                set_time_limit(-1);
                $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $slug . '.zip';
                if (file_exists($file)) unlink($file);
                $gh = $found['github'];
                if (!empty($config['github_token']) && !empty($config['github_username'])) {
                    $gh = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $gh);
                }
                $fp = fopen($file, 'w+');
                $ch = curl_init($gh . '/archive/refs/heads/master.zip');
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
                $folder = $CACHE_PATH . File::pathFixer('/' . $slug . '-main/');
                if (!file_exists($folder)) {
                    $folder = $CACHE_PATH . File::pathFixer('/' . $slug . '-master/');
                }
                if (!file_exists($folder)) {
                    r2(getUrl('marketplace/devices'), 'e', Lang::T('Extracted Folder is unknown'));
                }
                File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                File::deleteFolder($folder);
                unlink($file);
                r2(getUrl('marketplace/devices'), 's', Lang::T('Device') . ' ' . $slug . ' ' . Lang::T('has been installed'));
            } else {
                r2(getUrl('marketplace/devices'), 'e', Lang::T('Device not found in repository.'));
            }
        } else {
            r2(getUrl('marketplace/devices'), 'e', Lang::T('Invalid device slug.'));
        }
        break;

    case 'uninstall':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $file = $DEVICE_PATH . DIRECTORY_SEPARATOR . $slug . '.php';
            if (is_file($file)) {
                unlink($file);
                r2(getUrl('marketplace/devices'), 's', Lang::T('Device uninstalled.'));
            } else {
                r2(getUrl('marketplace/devices'), 'e', Lang::T('Device not found.'));
            }
        } else {
            r2(getUrl('marketplace/devices'), 'e', Lang::T('Invalid device slug.'));
        }
        break;

    case 'upload':
        if ($_app_stage == 'Demo') {
            r2(getUrl('marketplace/devices'), 'e', Lang::T('You cannot perform this action in Demo mode'));
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('marketplace/devices'), 'e', Lang::T('Folder cache/ is not writable'));
        }
        if (!is_writeable($DEVICE_PATH)) {
            r2(getUrl('marketplace/devices'), 'e', Lang::T('Folder devices/ is not writable'));
        }
        $cache = $CACHE_PATH . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR;
        if (!file_exists($cache)) {
            mkdir($cache, 0755, true);
        }
        if (isset($_FILES['zip_device']) && file_exists($_FILES['zip_device']['tmp_name'])) {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['zip_device']['tmp_name']) === true) {
                $zip->extractTo($cache);
                $zip->close();
                $deviceFolder = $cache . 'device' . DIRECTORY_SEPARATOR;
                if (is_dir($deviceFolder)) {
                    File::copyFolder($deviceFolder, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                    File::deleteFolder($cache);
                    r2(getUrl('marketplace/devices'), 's', Lang::T('Device has been installed'));
                } else {
                    File::deleteFolder($cache);
                    r2(getUrl('marketplace/devices'), 'e', Lang::T('Device folder not found in ZIP. Expected a "device/" folder.'));
                }
            } else {
                r2(getUrl('marketplace/devices'), 'e', Lang::T('Cannot open ZIP file'));
            }
        } elseif (_post('gh_url', '') != '') {
            $ghUrl = _post('gh_url', '');
            if (!empty($config['github_token']) && !empty($config['github_username'])) {
                $ghUrl = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $ghUrl);
            }
            set_time_limit(-1);
            $deviceName = basename($ghUrl);
            $file = $cache . $deviceName . '.zip';
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
            unlink($file);

            $folder = $cache . $deviceName . '-main' . DIRECTORY_SEPARATOR;
            if (!is_dir($folder)) {
                $folder = $cache . $deviceName . '-master' . DIRECTORY_SEPARATOR;
            }
            if (!is_dir($folder)) {
                File::deleteFolder($cache);
                r2(getUrl('marketplace/devices'), 'e', Lang::T('Extracted Folder is unknown'));
            }

            $deviceFolder = $folder . 'device' . DIRECTORY_SEPARATOR;
            if (is_dir($deviceFolder)) {
                File::copyFolder($deviceFolder, $DEVICE_PATH . DIRECTORY_SEPARATOR);
            } else {
                // The repo root contains the device driver(s) — copy into devices/
                File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
            }
            File::deleteFolder($cache);
            r2(getUrl('marketplace/devices'), 's', Lang::T('Device has been installed'));
        } else {
            r2(getUrl('marketplace/devices'), 'e', Lang::T('Nothing Installed'));
        }
        break;

    default:
        break;
}

$stats = ['total' => count($devices), 'categories' => $categories];

$ui->assign('active_tab', 'devices');
$ui->assign('devices', $devices);
$ui->assign('available', $available);
$ui->assign('stats', $stats);
$ui->assign('csrf_token', Csrf::generateAndStoreToken());
$ui->display('admin/marketplace/marketplace-devices.tpl');
