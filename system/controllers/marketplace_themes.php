<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Marketplace - Themes'));
$ui->assign('_system_menu', 'marketplace');

$admin = Admin::_info();
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$action = ($routes[0] === 'marketplace') ? ($routes['2'] ?? 'list') : ($routes['1'] ?? 'list');
$theme = ($routes[0] === 'marketplace') ? ($routes['3'] ?? '') : ($routes['2'] ?? '');
if (empty($action)) {
    $action = 'list';
}

if ($routes[0] === 'marketplace_themes' && ($action === 'list' || empty($action))) {
    r2(getUrl('marketplace/themes'));
}

$stateChanging = in_array($action, ['activate', 'default', 'uninstall', 'upload'], true);
if ($stateChanging && $_app_stage == 'Demo') {
    r2(getUrl('marketplace/themes'), 'e', Lang::T('You cannot perform this action in Demo mode'));
}
if ($stateChanging) {
    $csrf_token = _post('csrf_token');
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('marketplace/themes'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }
}

// Scan available themes
$themesDir = File::pathFixer($UI_PATH . '/themes/');
$availableThemes = [];
if (is_dir($themesDir)) {
    foreach (glob($themesDir . '*' . DIRECTORY_SEPARATOR . 'theme.json') ?: [] as $metaFile) {
        $themeDir = dirname($metaFile);
        $slug = basename($themeDir);
        $meta = json_decode(file_get_contents($metaFile), true);
        if (!is_array($meta)) {
            $meta = [];
        }
        $availableThemes[] = [
            'slug' => $slug,
            'name' => $meta['name'] ?? $slug,
            'version' => $meta['version'] ?? '1.0.0',
            'author' => $meta['author'] ?? '',
            'description' => $meta['description'] ?? '',
            'parent' => $meta['parent'] ?? null,
            'active' => ($config['theme'] ?? '') === $slug,
            'has_functions' => is_file($themeDir . DIRECTORY_SEPARATOR . 'functions.php'),
            'path' => $themeDir,
        ];
    }
}
usort($availableThemes, fn($a, $b) => strcmp($a['name'], $b['name']));

switch ($action) {
    case 'activate':
        if ($theme && preg_match('/^[A-Za-z0-9_-]+$/', $theme)) {
            $themeExists = false;
            foreach ($availableThemes as $t) {
                if ($t['slug'] === $theme) {
                    $themeExists = true;
                    break;
                }
            }
            if ($themeExists) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'theme')->find_one();
                if ($d) {
                    $d->value = $theme;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = 'theme';
                    $d->value = $theme;
                    $d->save();
                }
                $config['theme'] = $theme;
                $_c['theme'] = $theme;
                r2(getUrl('marketplace/themes'), 's', Lang::T('Theme activated.'));
            } else {
                r2(getUrl('marketplace/themes'), 'e', Lang::T('Theme not found.'));
            }
        } else {
            r2(getUrl('marketplace/themes'), 'e', Lang::T('Invalid theme slug.'));
        }
        break;

    case 'default':
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'theme')->find_one();
        if ($d) {
            $d->value = 'default';
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'theme';
            $d->value = 'default';
            $d->save();
        }
        $config['theme'] = 'default';
        $_c['theme'] = 'default';
        r2(getUrl('marketplace/themes'), 's', Lang::T('Switched to default theme.'));
        break;

    case 'uninstall':
        if ($theme && preg_match('/^[A-Za-z0-9_-]+$/', $theme)) {
            if (($config['theme'] ?? '') === $theme) {
                r2(getUrl('marketplace/themes'), 'e', Lang::T('Cannot uninstall the active theme. Switch to another theme first.'));
            }
            $themeDir = File::pathFixer($UI_PATH . '/themes/' . $theme);
            if (is_dir($themeDir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($themeDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $path) {
                    if ($path->isDir()) {
                        rmdir($path->getPathname());
                    } else {
                        unlink($path->getPathname());
                    }
                }
                rmdir($themeDir);
                r2(getUrl('marketplace/themes'), 's', Lang::T('Theme uninstalled.'));
            } else {
                r2(getUrl('marketplace/themes'), 'e', Lang::T('Theme not found.'));
            }
        } else {
            r2(getUrl('marketplace/themes'), 'e', Lang::T('Invalid theme slug.'));
        }
        break;

    case 'upload':
        if ($_app_stage == 'Demo') {
            r2(getUrl('marketplace/themes'), 'e', Lang::T('You cannot perform this action in Demo mode'));
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('marketplace/themes'), 'e', Lang::T('Folder cache/ is not writable'));
        }
        if (!is_writeable(File::pathFixer($UI_PATH . '/themes'))) {
            r2(getUrl('marketplace/themes'), 'e', Lang::T('Folder themes/ is not writable'));
        }
        $cache = $CACHE_PATH . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR;
        if (!file_exists($cache)) {
            mkdir($cache, 0755, true);
        }
        if (isset($_FILES['zip_theme']) && file_exists($_FILES['zip_theme']['tmp_name'])) {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['zip_theme']['tmp_name']) === true) {
                $zip->extractTo($cache);
                $zip->close();
                $themeFolder = $cache . 'theme' . DIRECTORY_SEPARATOR;
                if (is_dir($themeFolder)) {
                    File::copyFolder($themeFolder, File::pathFixer($UI_PATH . '/themes') . DIRECTORY_SEPARATOR);
                    File::deleteFolder($cache);
                    r2(getUrl('marketplace/themes'), 's', Lang::T('Theme has been installed'));
                } else {
                    File::deleteFolder($cache);
                    r2(getUrl('marketplace/themes'), 'e', Lang::T('Theme folder not found in ZIP. Expected a "theme/" folder.'));
                }
            } else {
                r2(getUrl('marketplace/themes'), 'e', Lang::T('Cannot open ZIP file'));
            }
        } elseif (_post('gh_url', '') != '') {
            $ghUrl = _post('gh_url', '');
            if (!empty($config['github_token']) && !empty($config['github_username'])) {
                $ghUrl = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $ghUrl);
            }
            set_time_limit(-1);
            $themeName = basename($ghUrl);
            $file = $cache . $themeName . '.zip';
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

            $folder = $cache . $themeName . '-main' . DIRECTORY_SEPARATOR;
            if (!is_dir($folder)) {
                $folder = $cache . $themeName . '-master' . DIRECTORY_SEPARATOR;
            }
            if (!is_dir($folder)) {
                File::deleteFolder($cache);
                r2(getUrl('marketplace/themes'), 'e', Lang::T('Extracted Folder is unknown'));
            }

            $themeFolder = $folder . 'theme' . DIRECTORY_SEPARATOR;
            if (is_dir($themeFolder)) {
                File::copyFolder($themeFolder, File::pathFixer($UI_PATH . '/themes') . DIRECTORY_SEPARATOR);
                File::deleteFolder($cache);
                r2(getUrl('marketplace/themes'), 's', Lang::T('Theme has been installed'));
            }

            // The repo root is the theme itself — move it under themes/<name>
            $dest = File::pathFixer($UI_PATH . '/themes') . DIRECTORY_SEPARATOR . $themeName;
            if (is_dir($dest)) {
                File::deleteFolder($dest);
            }
            rename($folder, $dest);
            File::deleteFolder($cache);
            r2(getUrl('marketplace/themes'), 's', Lang::T('Theme has been installed'));
        } else {
            r2(getUrl('marketplace/themes'), 'e', Lang::T('Nothing Installed'));
        }
        break;

    default:
        break;
}

$stats = ['total' => count($availableThemes), 'active' => 0, 'child' => 0, 'functions' => 0];
foreach ($availableThemes as $t) {
    if ($t['active']) {
        $stats['active']++;
    }
    if ($t['parent']) {
        $stats['child']++;
    }
    if ($t['has_functions']) {
        $stats['functions']++;
    }
}

$ui->assign('active_tab', 'themes');
$ui->assign('themes', $availableThemes);
$ui->assign('stats', $stats);
$ui->assign('active_theme', $config['theme'] ?? 'default');
$ui->assign('csrf_token', Csrf::generateAndStoreToken());
$ui->display('admin/marketplace/marketplace-themes.tpl');