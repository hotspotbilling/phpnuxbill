<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Marketplace - Plugins'));
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

if ($routes[0] === 'marketplace_plugins' && ($action === 'list' || empty($action))) {
    r2(getUrl('marketplace/plugins'));
}

// CSRF check for state-changing actions
$stateChanging = in_array($action, ['activate', 'deactivate', 'uninstall', 'convert', 'install'], true);
if ($stateChanging && $_app_stage == 'Demo') {
    r2(getUrl('marketplace/plugins'), 'e', Lang::T('You cannot perform this action in Demo mode'));
}
if ($stateChanging) {
    $csrf_token = _post('csrf_token');
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('marketplace/plugins'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }
}

// Fetch plugin repository (cached 24h) for the "available to install" listing
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
$availablePlugins = $repoJson['plugins'] ?? [];

// Collect folder-based plugins (from PluginLoader, populated in init.php)
$folderPlugins = [];
foreach (PluginLoader::$plugins as $plgSlug => $plg) {
    $folderPlugins[] = [
        'slug' => $plgSlug,
        'name' => $plg['name'] ?? $plgSlug,
        'version' => $plg['version'] ?? '1.0.0',
        'description' => $plg['description'] ?? '',
        'author' => $plg['author'] ?? '',
        'active' => $plg['active'] ?? true,
        'path' => $plg['path'] ?? '',
        'type' => 'folder',
        'update_url' => $plg['update_url'] ?? null,
    ];
}

// Attach update info for plugins with update_url
$updateInfo = [];
foreach ($folderPlugins as $p) {
    if (!empty($p['update_url'])) {
        $updateInfo[$p['slug']] = PluginLoader::updateAvailable($p);
    }
}

// Collect legacy flat plugins (not shadowed by folder plugins)
$legacyPlugins = [];
$legacyFiles = glob($PLUGIN_PATH . DIRECTORY_SEPARATOR . '*.php') ?: [];
foreach ($legacyFiles as $file) {
    $fileSlug = basename($file, '.php');
    if ($fileSlug === 'index' || PluginLoader::isShadowed($file)) {
        continue;
    }
    $code = @file_get_contents($file);
    $name = $fileSlug;
    if ($code && preg_match('/register_menu\(\s*[\'"]([^\'"]+)[\'"]/', $code, $m)) {
        $name = $m[1];
    }
    $legacyPlugins[] = [
        'slug' => $fileSlug,
        'name' => $name,
        'version' => 'legacy',
        'description' => 'Legacy flat plugin (' . $fileSlug . '.php)',
        'author' => '',
        'active' => true,
        'path' => $file,
        'type' => 'legacy',
    ];
}

$allPlugins = array_merge($folderPlugins, $legacyPlugins);
usort($allPlugins, fn($a, $b) => strcmp($a['name'], $b['name']));

// Determine which repository plugins are NOT installed
$installedIds = [];
foreach ($allPlugins as $p) {
    $installedIds[strtolower($p['slug'])] = true;
}
$available = [];
foreach ($availablePlugins as $plg) {
    $id = strtolower($plg['id'] ?? '');
    if ($id && !isset($installedIds[$id])) {
        $available[] = $plg;
    }
}

switch ($action) {
    case 'activate':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            if (PluginLoader::setActive($slug, true)) {
                $install = PluginLoader::runInstall($slug);
                $msg = Lang::T('Plugin activated.');
                if (!$install['success']) {
                    $msg .= ' ' . Lang::T('Install hook failed:') . ' ' . $install['message'];
                    r2(getUrl('marketplace/plugins'), 'e', $msg);
                } else {
                    r2(getUrl('marketplace/plugins'), 's', $msg);
                }
            } else {
                r2(getUrl('marketplace/plugins'), 'e', Lang::T('Failed to activate plugin.'));
            }
        } else {
            r2(getUrl('marketplace/plugins'), 'e', Lang::T('Invalid plugin slug.'));
        }
        break;

    case 'deactivate':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            if (PluginLoader::setActive($slug, false)) {
                r2(getUrl('marketplace/plugins'), 's', Lang::T('Plugin deactivated.'));
            } else {
                r2(getUrl('marketplace/plugins'), 'e', Lang::T('Failed to deactivate plugin.'));
            }
        } else {
            r2(getUrl('marketplace/plugins'), 'e', Lang::T('Invalid plugin slug.'));
        }
        break;

    case 'uninstall':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $folder = $PLUGIN_PATH . DIRECTORY_SEPARATOR . $slug;
            if (is_dir($folder)) {
                // Folder plugin: run uninstall.php then remove the folder
                $uninstall = PluginLoader::runUninstall($slug);
                if (!$uninstall['success']) {
                    r2(getUrl('marketplace/plugins'), 'e', Lang::T('Uninstall hook failed:') . ' ' . $uninstall['message']);
                }
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $path) {
                    if ($path->isDir()) {
                        rmdir($path->getPathname());
                    } else {
                        unlink($path->getPathname());
                    }
                }
                rmdir($folder);
                $stateFile = $CACHE_PATH . DIRECTORY_SEPARATOR . 'plugins.json';
                $state = PluginLoader::getState();
                $state['disabled'] = array_values(array_diff($state['disabled'] ?? [], [$slug]));
                file_put_contents($stateFile, json_encode($state));
                r2(getUrl('marketplace/plugins'), 's', Lang::T('Plugin uninstalled.'));
            } elseif (is_file($PLUGIN_PATH . DIRECTORY_SEPARATOR . $slug . '.php')) {
                // Legacy flat plugin: remove .php + matching templates
                $result = PluginLoader::uninstallLegacy($slug);
                if ($result['success']) {
                    r2(getUrl('marketplace/plugins'), 's', Lang::T('Plugin uninstalled.') . ' ' . $result['message']);
                } else {
                    r2(getUrl('marketplace/plugins'), 'e', $result['message']);
                }
            } else {
                r2(getUrl('marketplace/plugins'), 'e', Lang::T('Plugin not found.'));
            }
        } else {
            r2(getUrl('marketplace/plugins'), 'e', Lang::T('Invalid plugin slug.'));
        }
        break;

    case 'convert':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $result = PluginLoader::convertLegacy($slug);
            if ($result['success']) {
                r2(getUrl('marketplace/plugins'), 's', Lang::T('Converted to folder style.') . ' ' . $result['message']);
            } else {
                r2(getUrl('marketplace/plugins'), 'e', $result['message']);
            }
        } else {
            r2(getUrl('marketplace/plugins'), 'e', Lang::T('Invalid plugin slug.'));
        }
        break;

    case 'install':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $found = null;
            foreach ($availablePlugins as $plg) {
                if (strcasecmp($plg['id'] ?? '', $slug) === 0) {
                    $found = $plg;
                    break;
                }
            }
            if ($found) {
                if (!is_writeable($CACHE_PATH)) {
                    r2(getUrl('marketplace/plugins'), 'e', Lang::T('Folder cache/ is not writable'));
                }
                if (!is_writeable($PLUGIN_PATH)) {
                    r2(getUrl('marketplace/plugins'), 'e', Lang::T('Folder plugin/ is not writable'));
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
                    r2(getUrl('marketplace/plugins'), 'e', Lang::T('Extracted Folder is unknown'));
                }
                File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                File::deleteFolder($folder);
                unlink($file);
                $installHook = PluginLoader::runInstall($slug);
                $msg = Lang::T('Plugin') . ' ' . $slug . ' ' . Lang::T('has been installed');
                if (!$installHook['success']) {
                    $msg .= '. ' . Lang::T('Install hook failed:') . ' ' . $installHook['message'];
                }
                r2(getUrl('marketplace/plugins'), 's', $msg);
            } else {
                r2(getUrl('marketplace/plugins'), 'e', Lang::T('Plugin not found in repository.'));
            }
        } else {
            r2(getUrl('marketplace/plugins'), 'e', Lang::T('Invalid plugin slug.'));
        }
        break;

    default:
        break;
}

// Stats for the header
$stats = ['total' => count($allPlugins), 'active' => 0, 'inactive' => 0, 'legacy' => 0, 'folder' => 0, 'updates' => 0];
foreach ($allPlugins as $p) {
    if ($p['type'] === 'legacy') {
        $stats['legacy']++;
    } else {
        $stats['folder']++;
    }
    if ($p['active']) {
        $stats['active']++;
    } else {
        $stats['inactive']++;
    }
}
foreach ($updateInfo as $u) {
    if ($u['update_available']) {
        $stats['updates']++;
    }
}

$ui->assign('active_tab', 'plugins');
$ui->assign('plugins', $allPlugins);
$ui->assign('update_info', $updateInfo);
$ui->assign('stats', $stats);
$ui->assign('available', $available);
$ui->assign('csrf_token', Csrf::generateAndStoreToken());
$ui->assign('zipExt', class_exists('ZipArchive'));
$ui->display('admin/marketplace/marketplace-plugins.tpl');
