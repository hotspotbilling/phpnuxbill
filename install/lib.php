<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Shared by every installer page: the session (for the form token), the "already installed" lock, the requirement checks and
 * the page chrome. The installer refuses to run on a site that already has a config.php: re-running it would point the site at
 * another database (or wipe this one), so anyone who could open /install/ could take the whole site over.
 */
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

function pnb_h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function pnb_root()
{
    return dirname(__DIR__);
}

/** Has this copy been installed already? (config.php written by step 4 holds the database login.) */
function pnb_installed()
{
    $f = pnb_root() . DIRECTORY_SEPARATOR . 'config.php';
    return is_file($f) && filesize($f) > 0 && strpos((string) file_get_contents($f), '$db_name') !== false;
}

/** Stop the page when the site is already installed. step 5 may still run once, right after step 4 finished in the same session. */
function pnb_guard($allowJustInstalled = false)
{
    if (!pnb_installed()) {
        return;
    }
    if ($allowJustInstalled && !empty($_SESSION['pnb_done']) && time() - (int) $_SESSION['pnb_done'] < 3600) {
        return;
    }
    http_response_code(403);
    pnb_head('Already installed', 0);
    echo '<h1>PHPNuxBill is already installed</h1>
        <p class="lede">This page is switched off because <code>config.php</code> exists. Anyone who could use it could point your site at another database.</p>
        <ol class="next-list">
            <li><b>To update:</b> sign in as SuperAdmin and open <a href="../update.php">update.php</a>.</li>
            <li><b>To reinstall from scratch:</b> back up your database, delete <code>config.php</code>, then open this page again.</li>
            <li><b>For safety</b>, delete the <code>install</code> folder from the server.</li>
        </ol>
        <div class="actions"><a href="../" class="btn">Open the site</a></div>';
    pnb_foot();
    exit;
}

function pnb_token()
{
    if (empty($_SESSION['pnb_token'])) {
        $_SESSION['pnb_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['pnb_token'];
}

function pnb_token_ok($t)
{
    return !empty($_SESSION['pnb_token']) && is_string($t) && hash_equals($_SESSION['pnb_token'], $t);
}

/**
 * What this server must (and should) have. Each row: [label, required, ok, detail].
 */
function pnb_requirements()
{
    $root = pnb_root();
    $rows = [];
    $rows[] = ['PHP 8.2 or newer', true, version_compare(PHP_VERSION, '8.2.0', '>='), 'You have ' . PHP_VERSION];
    foreach ([
        ['pdo_mysql', 'PDO MySQL driver', true],
        ['mysqli', 'MySQLi', true],
        ['curl', 'cURL (downloads, SMS and payment gateways)', true],
        ['gd', 'GD (image handling)', true],
        ['zip', 'ZIP (updates and the Marketplace)', true],
        ['mbstring', 'mbstring', true],
        ['json', 'JSON', true],
        ['openssl', 'OpenSSL', false],
        ['sodium', 'Sodium (signed Marketplace packages)', false],
    ] as [$ext, $label, $req]) {
        $rows[] = [$label, $req, extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'not loaded'];
    }
    foreach (['system/cache', 'system/logs', 'system/uploads', 'ui/compiled', 'ui/cache', 'pages_template'] as $dir) {
        $p = $root . '/' . $dir;
        $rows[] = ['Folder ' . $dir . ' is writable', !in_array($dir, ['pages_template', 'system/logs'], true), is_dir($p) && is_writable($p), is_dir($p) ? (is_writable($p) ? 'writable' : 'not writable') : 'missing'];
    }
    $rows[] = ['The site folder is writable (config.php is created there)', true, is_writable($root), is_writable($root) ? 'writable' : 'not writable: you will have to create config.php by hand'];
    return $rows;
}

const PNB_STEPS = ['Welcome', 'Requirements', 'Database', 'Install', 'Done'];

/**
 * The page shell: a dark "front panel" rail with one port and status light per step, then the content panel.
 * $step is the current step (1 to 5); $opts: 'all_done' turns every light green, 'bad' marks the current step as failed.
 */
function pnb_head($title = 'PHPNuxBill Installer', $step = 0, $opts = [])
{
    $version = (string) (json_decode((string) @file_get_contents(pnb_root() . '/version.json'), true)['version'] ?? '');
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>' . pnb_h($title) . ' - PHPNuxBill</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link rel="stylesheet" href="css/installer.css?2026.11.06">
</head>
<body>
<div class="shell' . (!empty($opts['all_done']) ? ' all-done' : '') . '">
    <aside class="rail">
        <div class="brand"><img src="img/logo.png" alt="PHPNuxBill"><b>Installer</b></div>
        <ol class="ports" aria-label="Progress">';
    foreach (PNB_STEPS as $i => $name) {
        $n = $i + 1;
        $class = '';
        if (!empty($opts['all_done']) || $n < $step) {
            $class = 'is-done';
        } elseif ($n === (int) $step) {
            $class = !empty($opts['bad']) ? 'is-bad' : 'is-on';
        }
        echo '<li class="port ' . $class . '"' . ($n === (int) $step ? ' aria-current="step"' : '') . '><span class="port-no">' . $n . '</span><span class="port-name">' . pnb_h($name) . '</span><span class="led"></span></li>';
    }
    echo '</ol>
        <p class="rail-foot">' . ($version !== '' ? 'Version ' . pnb_h($version) . '<br>' : '') . '<a href="https://t.me/phpnuxbill" target="_blank" rel="noopener">Need help?</a></p>
    </aside>
    <main class="panel">
';
}

function pnb_foot()
{
    echo '    </main>
</div>
<div class="footer">Copyright &copy; 2015-' . date('Y') . ' PHPNuxBill. All Rights Reserved</div>
</body>
</html>';
}

/** Kept so older page code still works: the step list now lives in the rail drawn by pnb_head(). */
function pnb_steps($n)
{
}
