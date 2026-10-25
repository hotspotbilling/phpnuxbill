<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Marketplace - Payment Gateways'));
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

if ($routes[0] === 'marketplace_gateways' && ($action === 'list' || empty($action))) {
    r2(getUrl('marketplace/gateways'));
}

$stateChanging = in_array($action, ['install', 'uninstall', 'upload', 'toggle', 'refresh'], true);
if ($stateChanging && $_app_stage == 'Demo') {
    r2(getUrl('marketplace/gateways'), 'e', Lang::T('You cannot perform this action in Demo mode'));
}
if (in_array($action, ['install', 'uninstall', 'upload', 'toggle'], true)) {
    $csrf_token = _post('csrf_token') ?: _get('csrf_token');
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('marketplace/gateways'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }
}

// Fetch payment gateway repository (cached 24h)
$plugin_repository = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';
$repoCache = $CACHE_PATH . File::pathFixer('/plugin_repository.json');
$repoJson = [];

if ($action === 'refresh') {
    if (file_exists($repoCache)) {
        unlink($repoCache);
    }
}

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
$repoGateways = $repoJson['payment_gateway'] ?? [];

// Helper function to detect region/category
function _gateway_region($slug, $desc = '') {
    $text = strtolower($slug . ' ' . $desc);
    if (strpos($text, 'indonesia') !== false || in_array($slug, ['duitku', 'tripay', 'xendit', 'midtrans', 'moota', 'faspay', 'oy'])) {
        return 'Indonesia';
    }
    if (strpos($text, 'kenya') !== false || strpos($text, 'nigeria') !== false || strpos($text, 'africa') !== false || in_array($slug, ['paystack', 'flutterwave', 'umspay', 'mpesa', 'monnify', 'opay'])) {
        return 'Africa';
    }
    if (strpos($text, 'bangladesh') !== false || in_array($slug, ['bkash', 'nagad', 'rocket'])) {
        return 'Bangladesh';
    }
    if (strpos($text, 'india') !== false || in_array($slug, ['razorpay', 'payu', 'allupipay', 'cashfree', 'phonepe', 'paytm'])) {
        return 'India';
    }
    if (strpos($text, 'oman') !== false || in_array($slug, ['thawani'])) {
        return 'Middle East';
    }
    if (in_array($slug, ['paypal', 'stripe', 'hitpay', 'coingate', 'coinbase', 'crypto'])) {
        return 'Global';
    }
    return 'Other';
}

// Active gateways in billing system
$activeList = array_filter(array_map('trim', explode(',', $config['payment_gateway'] ?? '')));

// Scan installed payment gateways
$installedGateways = [];
$regions = [];
$configuredCount = 0;

$pgFiles = glob($PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . '*.php') ?: [];
foreach ($pgFiles as $file) {
    $fileName = basename($file, '.php');
    if ($fileName === 'index' || $fileName === 'readme') {
        continue;
    }
    $code = file_get_contents($file);

    $title = ucwords(str_replace(['phpnuxbill-', '-payment-gateway', '_', '-'], [' ', ' ', ' ', ' '], $fileName));
    $description = '';
    $author = '';
    $version = '1.0.0';

    if (preg_match('/\*\s*Title\s*:\s*([^\n\r*]+)/i', $code, $m)) {
        $title = trim($m[1]);
    } elseif (preg_match('/\*\s*Name\s*:\s*([^\n\r*]+)/i', $code, $m)) {
        $title = trim($m[1]);
    }
    if (preg_match('/\*\s*Description\s*:\s*([^\n\r*]+)/i', $code, $m)) {
        $description = trim($m[1]);
    }
    if (preg_match('/\*\s*Author\s*:\s*([^\n\r*]+)/i', $code, $m)) {
        $author = trim($m[1]);
    }
    if (preg_match('/\*\s*Version\s*:\s*([^\n\r*]+)/i', $code, $m)) {
        $version = trim($m[1]);
    }

    $isActive = in_array($fileName, $activeList);

    // Check if configured in tbl_appconfig
    $isConfigured = false;
    try {
        $cfgMatches = ORM::for_table('tbl_appconfig')
            ->where_like('setting', $fileName . '_%')
            ->where_not_equal('value', '')
            ->count();
        if ($cfgMatches > 0) {
            $isConfigured = true;
            $configuredCount++;
        }
    } catch (Throwable $e) {
        // ignore db error
    }

    $hasUi = false;
    $uiPath = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $fileName . '.tpl';
    if (file_exists($uiPath)) {
        $hasUi = true;
    }

    // Try finding in repository for extra meta
    $repoMeta = null;
    foreach ($repoGateways as $rg) {
        $rgId = strtolower($rg['id'] ?? '');
        $cleanId = str_replace(['phpnuxbill-', '-payment-gateway', '-'], '', $rgId);
        $cleanFile = str_replace(['phpnuxbill-', '-payment-gateway', '-'], '', strtolower($fileName));
        if ($cleanId === $cleanFile || strpos($cleanId, $cleanFile) !== false || strpos($cleanFile, $cleanId) !== false) {
            $repoMeta = $rg;
            if (empty($description) && !empty($rg['description'])) {
                $description = $rg['description'];
            }
            if (empty($author) && !empty($rg['author'])) {
                $author = $rg['author'];
            }
            break;
        }
    }

    $region = _gateway_region($fileName, $description);
    $regions[$region] = ($regions[$region] ?? 0) + 1;

    $installedGateways[] = [
        'slug'         => $fileName,
        'name'         => $title,
        'description'  => $description ?: Lang::T('Payment gateway integration for online transactions.'),
        'author'       => $author ?: 'PHPNuxBill Community',
        'version'      => $version,
        'region'       => $region,
        'active'       => $isActive,
        'configured'   => $isConfigured,
        'has_ui'       => $hasUi,
        'repo_meta'    => $repoMeta,
        'file_size'    => round(filesize($file) / 1024, 1) . ' KB',
        'last_updated' => date('Y-m-d', filemtime($file))
    ];
}

usort($installedGateways, fn($a, $b) => strcmp($a['name'], $b['name']));

// Installed slugs index for fast lookup
$installedSlugs = [];
foreach ($installedGateways as $g) {
    $clean = strtolower(preg_replace('/[^a-z0-9]/', '', $g['slug']));
    $installedSlugs[$clean] = true;
}

// Available gateways from official repository (not yet installed)
$availableGateways = [];
foreach ($repoGateways as $rg) {
    $id = strtolower($rg['id'] ?? '');
    $cleanId = str_replace(['phpnuxbill', 'paymentgateway', 'gateway'], '', preg_replace('/[^a-z0-9]/', '', $id));
    $isInstalled = false;

    foreach (array_keys($installedSlugs) as $installed) {
        $cleanInst = str_replace(['phpnuxbill', 'paymentgateway', 'gateway'], '', $installed);
        if ($cleanId === $cleanInst || ($cleanId !== '' && strpos($cleanInst, $cleanId) !== false) || ($cleanInst !== '' && strpos($cleanId, $cleanInst) !== false)) {
            $isInstalled = true;
            break;
        }
    }

    if (!$isInstalled) {
        $region = _gateway_region($rg['name'] ?? $rg['id'], $rg['description'] ?? '');
        $rg['region'] = $region;
        $availableGateways[] = $rg;
    }
}

// Actions routing
switch ($action) {
    case 'toggle':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $currentActives = array_filter(array_map('trim', explode(',', $config['payment_gateway'] ?? '')));
            if (in_array($slug, $currentActives)) {
                $currentActives = array_diff($currentActives, [$slug]);
                $msg = Lang::T('Payment Gateway deactivated from checkout.');
            } else {
                $currentActives[] = $slug;
                $msg = Lang::T('Payment Gateway activated for checkout.');
            }
            $newVal = implode(',', array_unique($currentActives));
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'payment_gateway')->find_one();
            if ($d) {
                $d->value = $newVal;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'payment_gateway';
                $d->value = $newVal;
                $d->save();
            }
            $config['payment_gateway'] = $newVal;
            r2(getUrl('marketplace/gateways'), 's', $msg);
        } else {
            r2(getUrl('marketplace/gateways'), 'e', Lang::T('Invalid gateway slug.'));
        }
        break;

    case 'install':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $found = null;
            foreach ($repoGateways as $g) {
                if (strcasecmp($g['id'] ?? '', $slug) === 0) {
                    $found = $g;
                    break;
                }
            }
            if ($found) {
                if (!is_writable($CACHE_PATH)) {
                    r2(getUrl('marketplace/gateways'), 'e', Lang::T('Folder cache/ is not writable'));
                }
                if (!is_writable($PAYMENTGATEWAY_PATH)) {
                    r2(getUrl('marketplace/gateways'), 'e', Lang::T('Folder paymentgateway/ is not writable'));
                }
                set_time_limit(-1);
                $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $slug . '.zip';
                if (file_exists($file)) unlink($file);

                $gh = $found['github'];
                if (!empty($config['github_token']) && !empty($config['github_username'])) {
                    $gh = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $gh);
                }

                // Download archive (try master branch first, fallback to main branch)
                $branches = ['master', 'main'];
                $downloadSuccess = false;

                foreach ($branches as $branch) {
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($gh . '/archive/refs/heads/' . $branch . '.zip');
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'PHPNuxBill-Marketplace');
                    curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    fclose($fp);

                    if ($httpCode == 200 && file_exists($file) && filesize($file) > 1000) {
                        $downloadSuccess = true;
                        break;
                    }
                }

                if (!$downloadSuccess) {
                    if (file_exists($file)) unlink($file);
                    r2(getUrl('marketplace/gateways'), 'e', Lang::T('Failed to download payment gateway from repository.'));
                }

                $zip = new ZipArchive();
                if ($zip->open($file) === true) {
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    unlink($file);
                } else {
                    if (file_exists($file)) unlink($file);
                    r2(getUrl('marketplace/gateways'), 'e', Lang::T('Failed to extract gateway zip package.'));
                }

                $folder = $CACHE_PATH . File::pathFixer('/' . $slug . '-main/');
                if (!file_exists($folder)) {
                    $folder = $CACHE_PATH . File::pathFixer('/' . $slug . '-master/');
                }
                if (!file_exists($folder)) {
                    // Try glob matching
                    $matches = glob($CACHE_PATH . DIRECTORY_SEPARATOR . $slug . '-*');
                    if (!empty($matches) && is_dir($matches[0])) {
                        $folder = $matches[0] . DIRECTORY_SEPARATOR;
                    } else {
                        r2(getUrl('marketplace/gateways'), 'e', Lang::T('Extracted Folder is unknown'));
                    }
                }

                // Find payment gateway source directory inside archive
                $srcDir = $folder;
                if (is_dir($folder . 'paymentgateway')) {
                    $srcDir = $folder . 'paymentgateway' . DIRECTORY_SEPARATOR;
                } elseif (is_dir($folder . 'paymentGateway')) {
                    $srcDir = $folder . 'paymentGateway' . DIRECTORY_SEPARATOR;
                }

                // Copy PHP gateway files to PAYMENTGATEWAY_PATH
                $foundPhp = false;
                foreach (glob($srcDir . '*.php') ?: [] as $phpFile) {
                    $bname = basename($phpFile);
                    if ($bname !== 'index.php') {
                        copy($phpFile, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $bname);
                        $foundPhp = true;
                    }
                }

                // Copy template files in ui/ if exists
                $uiSource = $srcDir . 'ui' . DIRECTORY_SEPARATOR;
                if (!is_dir($uiSource) && is_dir($folder . 'ui')) {
                    $uiSource = $folder . 'ui' . DIRECTORY_SEPARATOR;
                }
                if (is_dir($uiSource)) {
                    $destUi = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
                    if (!is_dir($destUi)) {
                        mkdir($destUi, 0755, true);
                    }
                    foreach (glob($uiSource . '*.tpl') ?: [] as $tplFile) {
                        copy($tplFile, $destUi . basename($tplFile));
                    }
                }

                File::deleteFolder($folder);

                if ($foundPhp) {
                    r2(getUrl('marketplace/gateways'), 's', Lang::T('Payment Gateway') . ' ' . ($found['name'] ?? $slug) . ' ' . Lang::T('has been installed successfully!'));
                } else {
                    r2(getUrl('marketplace/gateways'), 'w', Lang::T('Gateway extracted, but no driver file found.'));
                }
            } else {
                r2(getUrl('marketplace/gateways'), 'e', Lang::T('Payment gateway not found in repository.'));
            }
        } else {
            r2(getUrl('marketplace/gateways'), 'e', Lang::T('Invalid gateway slug.'));
        }
        break;

    case 'upload':
        if (!is_writable($PAYMENTGATEWAY_PATH)) {
            r2(getUrl('marketplace/gateways'), 'e', Lang::T('Folder paymentgateway/ is not writable'));
        }

        // 1. Direct GitHub URL install
        $ghUrl = trim(_post('gh_url') ?? '');
        if (!empty($ghUrl)) {
            if (filter_var($ghUrl, FILTER_VALIDATE_URL) && strpos($ghUrl, 'github.com') !== false) {
                $ghParts = explode('/', parse_url($ghUrl, PHP_URL_PATH));
                $slug = end($ghParts);
                if (empty($slug)) {
                    $slug = prev($ghParts);
                }
                $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);

                $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $slug . '.zip';
                $fp = fopen($file, 'w+');
                $ch = curl_init(rtrim($ghUrl, '/') . '/archive/refs/heads/master.zip');
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PHPNuxBill-Marketplace');
                curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fp);

                if ($code != 200 || filesize($file) < 500) {
                    // Try main branch
                    $fp = fopen($file, 'w+');
                    $ch = curl_init(rtrim($ghUrl, '/') . '/archive/refs/heads/main.zip');
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'PHPNuxBill-Marketplace');
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);
                }

                $zip = new ZipArchive();
                if ($zip->open($file) === true) {
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    unlink($file);

                    $matches = glob($CACHE_PATH . DIRECTORY_SEPARATOR . $slug . '-*');
                    if (!empty($matches) && is_dir($matches[0])) {
                        $folder = $matches[0] . DIRECTORY_SEPARATOR;
                        $srcDir = (is_dir($folder . 'paymentgateway')) ? $folder . 'paymentgateway' . DIRECTORY_SEPARATOR : $folder;
                        foreach (glob($srcDir . '*.php') ?: [] as $phpFile) {
                            if (basename($phpFile) !== 'index.php') {
                                copy($phpFile, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . basename($phpFile));
                            }
                        }
                        if (is_dir($srcDir . 'ui')) {
                            $destUi = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
                            if (!is_dir($destUi)) mkdir($destUi, 0755, true);
                            foreach (glob($srcDir . 'ui' . DIRECTORY_SEPARATOR . '*.tpl') ?: [] as $tplFile) {
                                copy($tplFile, $destUi . basename($tplFile));
                            }
                        }
                        File::deleteFolder($folder);
                        r2(getUrl('marketplace/gateways'), 's', Lang::T('Payment gateway installed from GitHub successfully.'));
                    }
                }
            }
            r2(getUrl('marketplace/gateways'), 'e', Lang::T('Failed to install from GitHub URL.'));
        }

        // 2. Direct File Upload (.zip or .php)
        if (!empty($_FILES['gateway_file']['name'])) {
            $name = $_FILES['gateway_file']['name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $tmp = $_FILES['gateway_file']['tmp_name'];

            if ($ext === 'php') {
                $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($name, PATHINFO_FILENAME)) . '.php';
                if ($cleanName === 'index.php') {
                    r2(getUrl('marketplace/gateways'), 'e', Lang::T('Invalid file name.'));
                }
                if (move_uploaded_file($tmp, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $cleanName)) {
                    r2(getUrl('marketplace/gateways'), 's', Lang::T('Payment Gateway driver uploaded successfully.'));
                } else {
                    r2(getUrl('marketplace/gateways'), 'e', Lang::T('Failed to move uploaded file.'));
                }
            } elseif ($ext === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($tmp) === true) {
                    $tempExtract = $CACHE_PATH . DIRECTORY_SEPARATOR . 'upload_pg_' . time() . DIRECTORY_SEPARATOR;
                    mkdir($tempExtract, 0755, true);
                    $zip->extractTo($tempExtract);
                    $zip->close();

                    $srcDir = $tempExtract;
                    if (is_dir($tempExtract . 'paymentgateway')) {
                        $srcDir = $tempExtract . 'paymentgateway' . DIRECTORY_SEPARATOR;
                    }
                    $copied = 0;
                    foreach (glob($srcDir . '*.php') ?: [] as $f) {
                        if (basename($f) !== 'index.php') {
                            copy($f, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . basename($f));
                            $copied++;
                        }
                    }
                    if (is_dir($srcDir . 'ui')) {
                        $destUi = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
                        if (!is_dir($destUi)) mkdir($destUi, 0755, true);
                        foreach (glob($srcDir . 'ui' . DIRECTORY_SEPARATOR . '*.tpl') ?: [] as $tpl) {
                            copy($tpl, $destUi . basename($tpl));
                        }
                    }
                    File::deleteFolder($tempExtract);

                    if ($copied > 0) {
                        r2(getUrl('marketplace/gateways'), 's', Lang::T('Payment Gateway package installed successfully.'));
                    } else {
                        r2(getUrl('marketplace/gateways'), 'e', Lang::T('ZIP package contained no gateway PHP files.'));
                    }
                } else {
                    r2(getUrl('marketplace/gateways'), 'e', Lang::T('Unable to open uploaded ZIP file.'));
                }
            } else {
                r2(getUrl('marketplace/gateways'), 'e', Lang::T('Only .php and .zip gateway files are supported.'));
            }
        }
        r2(getUrl('marketplace/gateways'), 'e', Lang::T('No file uploaded.'));
        break;

    case 'uninstall':
        if ($slug && preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $target = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $slug . '.php';
            if (file_exists($target)) {
                unlink($target);
                // Also clean up any associated template in ui/
                $uiTpl = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $slug . '.tpl';
                if (file_exists($uiTpl)) {
                    unlink($uiTpl);
                }
                // Remove from active gateways list if active
                $currentActives = array_filter(array_map('trim', explode(',', $config['payment_gateway'] ?? '')));
                if (in_array($slug, $currentActives)) {
                    $newVal = implode(',', array_diff($currentActives, [$slug]));
                    $d = ORM::for_table('tbl_appconfig')->where('setting', 'payment_gateway')->find_one();
                    if ($d) {
                        $d->value = $newVal;
                        $d->save();
                    }
                }
                r2(getUrl('marketplace/gateways'), 's', Lang::T('Payment Gateway uninstalled successfully.'));
            } else {
                r2(getUrl('marketplace/gateways'), 'e', Lang::T('Payment Gateway driver file not found.'));
            }
        } else {
            r2(getUrl('marketplace/gateways'), 'e', Lang::T('Invalid gateway slug.'));
        }
        break;

    default:
        // Summary stats
        $stats = [
            'total'      => count($installedGateways),
            'active'     => count($activeList),
            'configured' => $configuredCount,
            'available'  => count($availableGateways),
            'repo_total' => count($repoGateways),
            'regions'    => $regions
        ];

        $ui->assign('active_tab', 'gateways');
        $ui->assign('stats', $stats);
        $ui->assign('gateways', $installedGateways);
        $ui->assign('available', $availableGateways);
        $ui->assign('active_list', $activeList);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->assign('zipExt', class_exists('ZipArchive'));
        $ui->display('admin/marketplace/marketplace-gateways.tpl');
        break;
}
