<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * This script is for updating PHPNuxBill
 *
 * Only a signed-in SuperAdmin can use it. Nothing happens by just opening the page: the update starts from a button, every step is a
 * POST with a one-time token, the download must be https and a real zip, the package is checked before anything is replaced, the
 * parts that are replaced are backed up first (and put back if the copy fails), and the database changes are reported honestly.
 **/

session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

if (!is_file(__DIR__ . '/config.php')) {
    header('location: install/');
    exit;
}
include __DIR__ . '/config.php';

if (!empty($db_password) && empty($db_pass)) {
    $db_pass = $db_password; // compatibility with an old config.php
}
if (empty($update_url)) {
    $update_url = 'https://github.com/hotspotbilling/phpnuxbill/archive/refs/heads/master.zip';
}

const U_MAX_ZIP = 200 * 1024 * 1024;      // a download bigger than this is refused
const U_MAX_UNPACKED = 600 * 1024 * 1024; // so is a package that unpacks to more than this
const U_MAX_FILES = 30000;

function u_h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function u_path($path)
{
    return __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

// ------------------------------------------------------------------------------------------------ who may update

function u_db()
{
    global $db_host, $db_name, $db_user, $db_pass;
    return new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

/** The signed-in staff member, or an error page. Only SuperAdmin may replace the program files. */
function u_require_superadmin()
{
    if (empty($_SESSION['aid'])) {
        $_SESSION['ntype'] = 'e';
        $_SESSION['notify'] = 'You are not admin';
        header('location: ./?_route=login&You_are_not_admin');
        exit;
    }
    try {
        $st = u_db()->prepare('SELECT user_type FROM tbl_users WHERE id = ?');
        $st->execute([(int) $_SESSION['aid']]);
        $type = $st->fetchColumn();
    } catch (Throwable $e) {
        u_page('Updater', '<div class="alert alert-danger">The database could not be reached: ' . u_h($e->getMessage()) . '</div>');
    }
    if ($type !== 'SuperAdmin') {
        http_response_code(403);
        u_page('Updater', '<div class="alert alert-danger">Only a SuperAdmin can update PHPNuxBill.</div><p><a href="./?_route=dashboard" class="btn">Back</a></p>');
    }
}

function u_token()
{
    if (empty($_SESSION['update_token'])) {
        $_SESSION['update_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['update_token'];
}

function u_token_ok($t)
{
    return !empty($_SESSION['update_token']) && is_string($t) && hash_equals($_SESSION['update_token'], $t);
}

/** An address the updater may download from: the one in config.php (the administrator's own file), or a GitHub branch zip typed in. */
function u_url_ok($url, $trusted)
{
    if ($url === $trusted) {
        return preg_match('#^https://#i', $url) === 1;
    }
    return strpos($url, '..') === false && preg_match('#^https://(?:github\.com|codeload\.github\.com)/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+/(?:archive/refs/heads/[A-Za-z0-9_./-]+\.zip|zip/refs/heads/[A-Za-z0-9_./-]+)$#', $url) === 1;
}

// ------------------------------------------------------------------------------------------------ the page

function u_page($title, $body, $next = null, $extraHead = '', $ui = [])
{
    global $self_version;
    $names = ['Download', 'Check', 'Install', 'Database', 'Finish'];
    // which ports are done / current: a page that hands over to step N has finished step N-1
    $done = $ui['done'] ?? ($next !== null ? (int) $next - 1 : 0);
    $bad = $ui['bad'] ?? 0;
    $all = !empty($ui['all_done']);
    $ports = '';
    foreach ($names as $i => $n) {
        $no = $i + 1;
        $cls = $all || $no <= $done ? 'is-done' : ($no === $bad ? 'is-bad' : ($no === $done + 1 && ($next !== null || $bad === 0 && ($ui['active'] ?? false)) ? 'is-on is-running' : ''));
        if ($all) {
            $cls .= ' seq';
        }
        $ports .= '<li class="port ' . $cls . '" style="--i:' . $i . '"><span class="no">' . $no . '</span><span class="nm">' . $n . '</span><span class="led"></span></li>';
    }
    $form = '';
    if ($next !== null) {
        $form = '<form method="post" action="update.php" id="nextform"><input type="hidden" name="token" value="' . u_h(u_token()) . '">
            <input type="hidden" name="step" value="' . (int) $next . '">
            <noscript><button type="submit" class="btn btn-primary">Continue</button></noscript></form>
            <script>setTimeout(function () { document.getElementById("nextform").submit(); }, 1200);</script>';
    }
    // inline on purpose: step 3 replaces ui/ and the installer folder, so this page may not depend on either
    echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PHPNuxBill Updater</title>
<style>
:root{--face:#0e1a2b;--paper:#f3f5f9;--card:#fff;--ink:#0f172a;--mute:#5b6679;--line:#dde2ea;--sig:#2563eb;--ok:#16a34a;--warn:#d97706;--bad:#dc2626;--field:#fff}
@media (prefers-color-scheme:dark){:root{--paper:#070d17;--card:#0f1a2c;--ink:#e6ecf5;--mute:#93a0b5;--line:#1f2d44;--sig:#4f86f7;--field:#0a1322}}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--paper);color:var(--ink);font:15px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-variant-numeric:tabular-nums;padding:24px 16px}
.shell{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:260px 1fr;border-radius:18px;overflow:hidden;border:1px solid var(--line);background:var(--card);box-shadow:0 20px 50px rgba(2,8,23,.12)}
.rail{background:var(--face);color:#e6ecf5;padding:28px 22px;display:flex;flex-direction:column;min-height:480px}
.brand{font-weight:700;font-size:19px;letter-spacing:-.01em}.brand small{display:block;font-weight:400;font-size:13px;color:#93a0b5;margin-top:2px}
.ports{list-style:none;margin:28px 0 0;padding:0;display:grid;gap:8px}
.port{display:flex;align-items:center;gap:10px;padding:9px 10px;border:1px solid #1f2d44;border-radius:10px;color:#93a0b5;background:#0a1322}
.port .no{width:26px;height:22px;display:grid;place-items:center;font-size:12px;font-weight:700;border:2px solid #3a4a63;border-top-color:transparent;border-radius:5px;color:#93a0b5}
.port .nm{flex:1;font-weight:600;font-size:14px}
.led{width:10px;height:10px;border-radius:50%;background:#334155}
.port.is-on{border-color:var(--sig);color:#fff}.port.is-on .no{border-color:var(--sig);color:#fff}.port.is-on .led{background:var(--sig)}
.port.is-done{color:#e6ecf5}.port.is-done .no{border-color:var(--ok)}.port.is-done .led{background:var(--ok);box-shadow:0 0 8px rgba(34,197,94,.6)}
.port.is-bad{border-color:var(--bad);color:#fff}.port.is-bad .no{border-color:var(--bad)}.port.is-bad .led{background:var(--bad);box-shadow:0 0 8px rgba(220,38,38,.6)}
.port.is-running .led{animation:pulse 1s ease-in-out infinite}
.port.seq .led{animation:lit .4s ease both;animation-delay:calc(var(--i)*.25s)}
@keyframes pulse{50%{opacity:.25}}@keyframes lit{from{background:#334155;box-shadow:none}}
.railfoot{margin-top:auto;font-size:12px;color:#93a0b5;padding-top:24px}
.panel{padding:36px 40px;min-width:0}
h1{margin:0 0 8px;font-size:26px;letter-spacing:-.02em;line-height:1.2}
.panel p{max-width:62ch}
code{background:rgba(127,127,127,.15);padding:1px 5px;border-radius:4px;font-size:.9em;word-break:break-all}
a{color:var(--sig)}
.alert{margin:14px 0;padding:12px 14px;border-radius:10px;border:1px solid var(--line);border-left-width:4px}
.alert-danger{border-left-color:var(--bad);background:rgba(220,38,38,.08)}.alert-warning{border-left-color:var(--warn);background:rgba(217,119,6,.08)}
.alert ul{margin:6px 0 0;padding-left:18px}
.form-group{margin:18px 0}.form-group label{display:block;font-weight:600;margin-bottom:6px}
.form-control{width:100%;padding:10px 12px;font:inherit;color:var(--ink);background:var(--field);border:1px solid var(--line);border-radius:10px}
.form-control:focus{outline:2px solid var(--sig);outline-offset:1px;border-color:var(--sig)}
.help-block{display:block;color:var(--mute);font-size:13px;margin-top:6px}
.btn{display:inline-block;padding:10px 18px;border-radius:10px;border:1px solid var(--line);background:transparent;color:var(--ink);font:inherit;font-weight:600;text-decoration:none;cursor:pointer}
.btn-primary{background:var(--sig);border-color:var(--sig);color:#fff}.btn[disabled]{opacity:.5;cursor:not-allowed}
.btn:focus-visible{outline:2px solid var(--sig);outline-offset:2px}
.footer{max-width:1000px;margin:16px auto 0;text-align:center;font-size:12px;color:var(--mute)}
.ver{display:flex;align-items:center;gap:10px;margin:12px 0 4px;font-weight:600}.ver span{color:var(--mute);font-weight:400}
@media (max-width:760px){.shell{grid-template-columns:1fr}.rail{min-height:0;padding:18px}.ports{grid-auto-flow:column;grid-auto-columns:1fr;margin-top:16px;gap:6px}
.port{justify-content:center;padding:8px 4px}.port .nm,.railfoot{display:none}.port .led{display:none}.panel{padding:24px 18px}}
@media (prefers-reduced-motion:reduce){*{animation:none!important}}
</style>
' . $extraHead . '
</head>
<body>
<div class="shell">
<aside class="rail"><div class="brand">PHPNuxBill<small>Updater</small></div><ul class="ports">' . $ports . '</ul>
<div class="railfoot">Installed version ' . u_h($self_version ?? '') . '</div></aside>
<main class="panel"><h1>' . u_h($title) . '</h1>' . $body . $form . '</main>
</div>
<div class="footer">PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener" target="_blank">iBNuX</a></div>
</body>
</html>';
    exit;
}

function u_fail($step, $message)
{
    u_unlock();
    u_page('Update stopped at step ' . (int) $step, '<div class="alert alert-danger">' . u_h($message) . '</div>
        <p>Nothing was left half-changed.</p><p><a href="update.php" class="btn">Back</a></p>', null, '', ['bad' => (int) $step, 'done' => max(0, (int) $step - 1)]);
}

// ------------------------------------------------------------------------------------------------ files

function u_rmtree($path)
{
    if (is_link($path) || is_file($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $f) {
        if ($f !== '.' && $f !== '..') {
            u_rmtree($path . DIRECTORY_SEPARATOR . $f);
        }
    }
    @rmdir($path);
}

/** Copy a tree over another one. $skipRoot names are left alone at the top level only. Returns the number of files that failed. */
function u_copytree($from, $to, $skipRoot = [], $top = true)
{
    $failed = 0;
    if (!is_dir($to) && !@mkdir($to, 0755, true)) {
        return 1;
    }
    foreach (scandir($from) ?: [] as $f) {
        if ($f === '.' || $f === '..' || $f === '.git' || ($top && in_array($f, $skipRoot, true))) {
            continue;
        }
        $src = $from . DIRECTORY_SEPARATOR . $f;
        $dst = $to . DIRECTORY_SEPARATOR . $f;
        if (is_link($src)) {
            continue;
        }
        if (is_dir($src)) {
            $failed += u_copytree($src, $dst, [], false);
        } elseif (!@copy($src, $dst)) {
            $failed++;
        }
    }
    return $failed;
}

function u_lock_file()
{
    return u_path('system/cache/update.lock');
}

function u_unlock()
{
    @unlink(u_lock_file());
    unset($_SESSION['update_run']);
}

/** A zip is safe to unpack when no entry leaves the folder it is unpacked into and the sizes are sane. */
function u_zip_check(ZipArchive $z)
{
    if ($z->numFiles < 1 || $z->numFiles > U_MAX_FILES) {
        return 'The package holds an unreasonable number of files';
    }
    $total = 0;
    for ($i = 0; $i < $z->numFiles; $i++) {
        $n = (string) $z->getNameIndex($i);
        if ($n === '' || $n[0] === '/' || $n[0] === '\\' || strpos($n, '..') !== false || strpos($n, '\\') !== false || preg_match('/^[A-Za-z]:/', $n) || strpos($n, "\0") !== false) {
            return 'The package contains unsafe paths';
        }
        $total += (int) ($z->statIndex($i)['size'] ?? 0);
        if ($total > U_MAX_UNPACKED) {
            return 'The package is too large once unpacked';
        }
    }
    return '';
}

// ------------------------------------------------------------------------------------------------ the steps

$self_version = (string) (json_decode((string) @file_get_contents(u_path('version.json')), true)['version'] ?? '0');
u_require_superadmin();

$run = $_SESSION['update_run'] ?? null;
$step = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!u_token_ok($_POST['token'] ?? '')) {
        u_page('Update', '<div class="alert alert-danger">This page expired. <a href="update.php">Start again</a>.</div>');
    }
    $step = (int) ($_POST['step'] ?? 0);
} elseif (in_array((int) ($_GET['step'] ?? 0), [4, 5], true) && empty($run)) {
    // An update started by the previous version of this page continues here after its files were replaced: only the database step and the
    // tidy-up, which are safe to run again (pending database changes, clearing caches).
    $step = (int) $_GET['step'];
    $run = $_SESSION['update_run'] = ['url' => '', 'from' => 'the previous version', 'started' => time()];
}

if ($step === 0) {
    // the confirmation page: nothing is changed by opening it
    $problems = [];
    if (!extension_loaded('zip')) {
        $problems[] = 'The PHP ZIP extension is not available.';
    }
    if (!is_writable(u_path('system/cache/'))) {
        $problems[] = 'The folder system/cache/ is not writable.';
    }
    if (!is_writable(__DIR__)) {
        $problems[] = 'The site folder is not writable, so program files cannot be replaced.';
    }
    $busy = is_file(u_lock_file()) && time() - (int) filemtime(u_lock_file()) < 1800 && empty($run);
    if ($busy) {
        $problems[] = 'Another update is running (or stopped less than 30 minutes ago). Try again later.';
    }
    $urlNow = $_SESSION['update_url'] ?? $update_url;
    $body = '<p>You are running version <b>' . u_h($self_version) . '</b>. Nothing changes until you press the button.</p>';
    foreach ($problems as $p) {
        $body .= '<div class="alert alert-danger">' . u_h($p) . '</div>';
    }
    $body .= '<form method="post" action="update.php">
        <input type="hidden" name="token" value="' . u_h(u_token()) . '"><input type="hidden" name="step" value="1">
        <div class="form-group"><label for="update_url">Download address</label>
            <input type="text" class="form-control" id="update_url" name="update_url" value="' . u_h($urlNow) . '">
            <span class="help-block">An https GitHub branch zip (a fork works too), or the address set as <code>$update_url</code> in config.php.</span></div>
        <p>The update downloads the package, checks it, <b>backs up</b> the program folders it replaces (<code>system/cache/backups/</code>),
            copies the new files and updates the database. Your <code>config.php</code>, uploads and settings are kept.
            It is safest to back up your database first.</p>
        <button type="submit" class="btn btn-primary"' . ($problems ? ' disabled' : '') . '>Start the update</button>
        <a href="./?_route=dashboard" class="btn">Cancel</a></form>';
    u_page('Update PHPNuxBill', $body);
}

// from here on a step changes things
set_time_limit(0);
ignore_user_abort(true);

if ($step === 1) {
    $asked = trim((string) ($_POST['update_url'] ?? ''));
    $url = $asked !== '' ? $asked : $update_url;
    if (!u_url_ok($url, $update_url)) {
        u_fail(1, 'That download address is not allowed. Use an https GitHub branch zip, or the address in config.php.');
    }
    if (!extension_loaded('zip')) {
        u_fail(1, 'The PHP ZIP extension is not available.');
    }
    if (is_file(u_lock_file()) && time() - (int) filemtime(u_lock_file()) < 1800 && empty($run)) {
        u_fail(1, 'Another update is running. Try again later.');
    }
    file_put_contents(u_lock_file(), (string) time());
    $_SESSION['update_url'] = $url;
    $run = $_SESSION['update_run'] = ['url' => $url, 'from' => $self_version, 'started' => time()];

    $zipFile = u_path('system/cache/phpnuxbill.zip');
    @unlink($zipFile);
    $fp = fopen($zipFile, 'w+');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 900,
        CURLOPT_USERAGENT => 'PHPNuxBill-Updater',
        CURLOPT_NOPROGRESS => false,
        CURLOPT_PROGRESSFUNCTION => function ($c, $total, $got) {
            return $got > U_MAX_ZIP ? 1 : 0;
        },
    ]);
    curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    clearstatcache(true, $zipFile);
    $size = (int) @filesize($zipFile);
    $magic = (string) @file_get_contents($zipFile, false, null, 0, 2);
    if ($http !== 200 || $size < 10240 || $magic !== 'PK') {
        @unlink($zipFile);
        u_fail(1, 'Failed to download the update' . ($err !== '' ? ': ' . $err : ($http !== 200 ? ' (the server answered ' . $http . ')' : ': the answer was not a zip file')) . '.');
    }
    u_page('Step 1 of 5 - downloaded', '<p>Downloaded ' . round($size / 1048576, 1) . ' MB. Checking the package next...</p>', 2);
}

if (empty($run)) {
    u_fail($step, 'The update has not been started in this session.');
}

if ($step === 2) {
    $zipFile = u_path('system/cache/phpnuxbill.zip');
    $z = new ZipArchive();
    if (!is_file($zipFile) || $z->open($zipFile) !== true) {
        u_fail(2, 'The downloaded file cannot be opened as a zip.');
    }
    if (($bad = u_zip_check($z)) !== '') {
        $z->close();
        u_fail(2, $bad . ', so it was not unpacked.');
    }
    $root = explode('/', (string) $z->getNameIndex(0))[0];
    for ($i = 0; $i < $z->numFiles; $i++) {
        if (strpos((string) $z->getNameIndex($i), $root . '/') !== 0) {
            $z->close();
            u_fail(2, 'The package does not have one top folder, so it is not a PHPNuxBill release.');
        }
    }
    foreach (['system/boot.php', 'index.php', 'version.json', 'system/autoload/'] as $must) {
        if ($z->locateName($root . '/' . $must) === false) {
            $z->close();
            u_fail(2, 'This is not a PHPNuxBill package (' . $must . ' is missing). Nothing was changed.');
        }
    }
    $pkg = u_path('system/cache/update_pkg');
    u_rmtree($pkg);
    mkdir($pkg, 0755, true);
    $ok = $z->extractTo($pkg);
    $z->close();
    @unlink($zipFile);
    $folder = $pkg . DIRECTORY_SEPARATOR . $root;
    $newVersion = (string) (json_decode((string) @file_get_contents($folder . DIRECTORY_SEPARATOR . 'version.json'), true)['version'] ?? '');
    if (!$ok || $newVersion === '') {
        u_rmtree($pkg);
        u_fail(2, 'The package could not be unpacked.');
    }
    if (version_compare($newVersion, $self_version, '<')) {
        u_rmtree($pkg);
        u_fail(2, 'This package (' . $newVersion . ') is older than the installed version (' . $self_version . '). Downgrading can break the database, so it was not installed.');
    }
    $run['folder'] = $folder;
    $run['to'] = $newVersion;
    $_SESSION['update_run'] = $run;
    u_page('Step 2 of 5 - package checked', '<p>Version <b>' . u_h($newVersion) . '</b> is a valid PHPNuxBill package. Backing up and installing next...</p>', 3);
}

if ($step === 3) {
    $folder = $run['folder'] ?? '';
    if ($folder === '' || !is_dir($folder) || strpos($folder, u_path('system/cache/update_pkg')) !== 0) {
        u_fail(3, 'The unpacked package is missing. Start again.');
    }
    $replaced = ['system/autoload', 'system/vendor', 'ui/ui'];

    // 1. a backup first: when it cannot be made, nothing is touched
    $dir = u_path('system/cache/backups');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents($dir . '/index.html', '');
    }
    $backup = $dir . '/phpnuxbill-before-' . preg_replace('/[^0-9A-Za-z.-]/', '', $run['from']) . '-to-' . preg_replace('/[^0-9A-Za-z.-]/', '', $run['to']) . '-' . date('Ymd-His') . '.zip';
    $z = new ZipArchive();
    if ($z->open($backup, ZipArchive::CREATE) !== true) {
        u_fail(3, 'The backup could not be created, so nothing was changed.');
    }
    foreach ($replaced as $d) {
        if (!is_dir(u_path($d))) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(u_path($d), FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && !$f->isLink()) {
                $z->addFile($f->getPathname(), str_replace('\\', '/', substr($f->getPathname(), strlen(__DIR__) + 1)));
            }
        }
    }
    foreach (['version.json', 'index.php', 'update.php'] as $f) {
        if (is_file(u_path($f))) {
            $z->addFile(u_path($f), $f);
        }
    }
    if (!$z->close() || !is_file($backup)) {
        u_fail(3, 'The backup could not be written, so nothing was changed.');
    }

    // 2. move the old program folders aside, copy the new files in, and put the old ones back if anything fails
    $old = u_path('system/cache/update_old');
    u_rmtree($old);
    mkdir($old, 0755, true);
    $moved = [];
    $failed = 0;
    foreach ($replaced as $d) {
        if (is_dir(u_path($d))) {
            $to = $old . DIRECTORY_SEPARATOR . str_replace('/', '__', $d);
            if (@rename(u_path($d), $to)) {
                $moved[$d] = $to;
            } else {
                $failed++;
            }
        }
    }
    if (!$failed) {
        $failed = u_copytree($folder, __DIR__, ['config.php']);
    }
    $versionNow = (string) (json_decode((string) @file_get_contents(u_path('version.json')), true)['version'] ?? '');
    if ($failed || $versionNow !== $run['to'] || !is_file(u_path('system/boot.php'))) {
        foreach ($replaced as $d) { // roll back
            if (isset($moved[$d])) {
                u_rmtree(u_path($d));
                @rename($moved[$d], u_path($d));
            }
        }
        foreach (['version.json', 'index.php', 'update.php'] as $f) { // restore the entry files from the backup
            $zr = new ZipArchive();
            if ($zr->open($backup) === true) {
                $zr->extractTo(__DIR__, $f);
                $zr->close();
            }
        }
        u_rmtree($old);
        u_fail(3, 'Copying the new files failed (' . $failed . ' file(s)); the previous version was put back. A backup is kept at ' . substr($backup, strlen(__DIR__) + 1) . '.');
    }
    u_rmtree(u_path('install')); // the installer is not needed on a running site
    u_rmtree($folder);
    u_rmtree(u_path('system/cache/update_pkg'));
    $run['backup'] = substr($backup, strlen(__DIR__) + 1);
    unset($run['folder']);
    $_SESSION['update_run'] = $run;
    u_page('Step 3 of 5 - files installed', '<p>The new files are in place. The backup of the old ones is <code>' . u_h($run['backup']) . '</code>.<br>Updating the database next...</p>', 4);
}

if ($step === 4) {
    $problems = [];
    $applied = 0;
    $updatesFile = u_path('system/updates.json');
    if (is_file($updatesFile)) {
        try {
            $db = u_db();
        } catch (Throwable $e) {
            u_fail(4, 'The database could not be reached: ' . $e->getMessage());
        }
        $updates = json_decode((string) file_get_contents($updatesFile), true) ?: [];
        $doneFile = u_path('system/cache/updates.done.json');
        $dones = is_file($doneFile) ? (json_decode((string) file_get_contents($doneFile), true) ?: []) : [];
        // MySQL answers these when a change is already there: harmless on a re-run
        $already = [1050, 1060, 1061, 1062, 1068, 1091];
        foreach ($updates as $version => $queries) {
            if (in_array($version, $dones, true)) {
                continue;
            }
            $clean = true;
            foreach ($queries as $q) {
                try {
                    $db->exec($q);
                    $applied++;
                } catch (PDOException $e) {
                    $code = (int) ($e->errorInfo[1] ?? 0);
                    // the FreeRADIUS tables (nas, rad...) only exist on a site that installed them: a change to one that is not there is skipped
                    $optional = $code === 1146 && preg_match('/\.(nas|rad[a-z_]*)\b/i', (string) $e->getMessage()) === 1;
                    if (!in_array($code, $already, true) && !$optional) {
                        $clean = false;
                        $problems[] = $version . ': ' . $e->getMessage();
                    }
                }
            }
            if ($clean) { // a version with a real error is tried again on the next update
                $dones[] = $version;
            }
        }
        file_put_contents($doneFile, json_encode(array_values($dones)));
    }
    $run['db_problems'] = $problems;
    $run['db_applied'] = $applied;
    $_SESSION['update_run'] = $run;
    u_page('Step 4 of 5 - database', '<p>Database changes applied: <b>' . (int) $applied . '</b>' . ($problems ? ', with <b>' . count($problems) . ' problem(s)</b> (shown at the end)' : '') . '.</p>', 5);
}

// step 5: tidy up and report
foreach (['ui/compiled', 'ui/cache'] as $d) {
    $p = u_path($d);
    foreach (is_dir($p) ? (scandir($p) ?: []) : [] as $f) {
        if (is_file($p . DIRECTORY_SEPARATOR . $f) && $f !== 'index.html') {
            @unlink($p . DIRECTORY_SEPARATOR . $f);
        }
    }
}
u_rmtree(u_path('system/cache/update_old'));
u_rmtree(u_path('system/cache/update_pkg'));
if (function_exists('opcache_reset')) {
    @opcache_reset();
}
$version = (string) (json_decode((string) @file_get_contents(u_path('version.json')), true)['version'] ?? '');
$summary = '<div class="ver">' . u_h($run['from']) . ' <span>to</span> ' . u_h($version) . '</div><p>PHPNuxBill is up to date.</p>';
if (!empty($run['backup'])) {
    $summary .= '<p>Backup of the replaced files: <code>' . u_h($run['backup']) . '</code> (restore it by unzipping over the site folder if something is wrong).</p>';
}
if (!empty($run['db_problems'])) {
    $summary .= '<div class="alert alert-warning"><b>Some database changes failed</b> and will be tried again the next time you update:<ul>';
    foreach (array_slice($run['db_problems'], 0, 10) as $p) {
        $summary .= '<li>' . u_h($p) . '</li>';
    }
    $summary .= '</ul></div>';
}
$summary .= '<p><a href="./?_route=dashboard" class="btn btn-primary">Open the dashboard</a></p>';
u_unlock();
u_page('Update finished', $summary, null, '', ['all_done' => true]);
