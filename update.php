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
    return strpos($url, '..') === false && preg_match('#^https://(?:github\.com|codeload\.github\.com)/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+/(?:archive/refs/(?:heads|tags)/[A-Za-z0-9_./-]+\.zip|archive/[0-9a-f]{7,40}\.zip|zip/refs/heads/[A-Za-z0-9_./-]+)$#', $url) === 1;
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
        ' . ((int) $step <= 3 ? '<p>Nothing was left half-changed.</p>' : '<p>The new files are already installed; run the update again to retry this step.</p>') . '<p><a href="update.php" class="btn">Back</a></p>', null, '', ['bad' => (int) $step, 'done' => max(0, (int) $step - 1)]);
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

/** Every file the package would put in the site folder (relative paths), except config.php at the top. */
function u_pkg_files($folder)
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || $f->isLink()) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($folder) + 1));
        if ($rel === 'config.php' || strpos($rel, '.git/') === 0) {
            continue;
        }
        $out[] = $rel;
    }
    return $out;
}

function u_lock_file()
{
    return u_path('system/cache/update.lock');
}

function u_unlock()
{
    @unlink(u_lock_file());
    // the run's state, and the rollback permission with it: it belongs to this run only
    unset($_SESSION['update_run'], $_SESSION['update_older']);
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
    // The version it came from is not known here. That only matters on a site with no migration record yet, and step 4 refuses to guess it.
    $run = $_SESSION['update_run'] = ['url' => '', 'from' => '', 'started' => time()];
}

if ($step === 9) {
    // Repair the list of finished database updates (the Repair button on the first page). Only acts while the list is damaged.
    require_once u_path('system/autoload/Migrator.php');
    try {
        $mg = new Migrator(u_db(), u_path('system/updates.json'));
        $mg->ensureTable();
        $repairedTo = trim((string) ($_POST['db_version'] ?? ''));
        $mg->repairBaseline($repairedTo);
        $left = count($mg->pending());
        // the database step and the summary that follow work from this run
        $_SESSION['update_run'] = ['url' => '', 'from' => $repairedTo, 'started' => time()];
    } catch (Throwable $e) {
        u_page('Repair', '<div class="alert alert-danger">' . u_h($e->getMessage()) . '</div><p><a href="update.php" class="btn">Back</a></p>');
    }
    $body = '<div class="alert alert-success"><b>The list is repaired.</b></div>';
    if ($left > 0) {
        $body .= '<p>' . $left . ' database update(s) for this version still need to run. Finish them now; it takes a moment and customers see
            the maintenance page only until it is done.</p>
            <form method="post" action="update.php"><input type="hidden" name="token" value="' . u_h(u_token()) . '"><input type="hidden" name="step" value="4">
            <button type="submit" class="btn btn-primary">Finish the database update</button> <a href="update.php" class="btn">Not now</a></form>';
    } else {
        $body .= '<p>Nothing else needs to run. <a href="./?_route=dashboard" class="btn">Back to the dashboard</a></p>';
    }
    u_page('Repair', $body);
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
    // changes from an earlier update that stopped halfway run first
    try {
        require_once u_path('system/autoload/Migrator.php');
        $mg = new Migrator(u_db(), u_path('system/updates.json'));
        $mg->ensureTable();
        if ($mg->badBaseline() !== null) {
            $problems[] = 'The update cannot start until the list below is repaired.';
            $repair_note = '<div class="alert alert-warning"><b>The list of finished database updates needs a repair</b><br>
                An earlier version of this updater lost track of which database updates this site already has, so it now thinks old updates
                still need to run. Running them would undo newer changes. Repairing the list fixes this; your data is not touched.</div>
                <form method="post" action="update.php"><input type="hidden" name="token" value="' . u_h(u_token()) . '"><input type="hidden" name="step" value="9">
                <div class="form-group"><label for="db_version">The version this database was last fully updated to</label>
                    <input type="text" class="form-control" id="db_version" name="db_version" value="" placeholder="for example 2026.10.23" required pattern="[0-9]+(\.[0-9]+)*">
                    <span class="help-block">The version the site ran before the update that went wrong. The program files are now ' . u_h($self_version) . ', but
                    if their database step did not finish, the database is still on the older version: entering ' . u_h($self_version) . ' then would mark
                    its unfinished changes as done. Updates the list shows as already run are kept either way.</span></div>
                <button type="submit" class="btn btn-primary">Repair the list</button></form><hr>';
        } elseif (!$mg->isEmpty() && ($left = count($mg->pending())) > 0) {
            $problems_note = '<div class="alert alert-warning">' . $left . ' database change(s) from an earlier update are still waiting and will run first.</div>';
        }
    } catch (Throwable $e) {
        // not important here: the update itself reports database problems
    }
    $urlNow = $_SESSION['update_url'] ?? $update_url;
    // The old-version list links here with the address to fill in. It only fills the box (the address is checked again when the button is
    // pressed, and only GitHub zips are accepted); nothing is downloaded until the button.
    $askedUrl = trim((string) ($_GET['update_url'] ?? ''));
    $older = !empty($_GET['older']);
    if ($askedUrl !== '' && u_url_ok($askedUrl, $update_url)) {
        $urlNow = $askedUrl;
    }
    $body = '<p>You are running version <b>' . u_h($self_version) . '</b>. Nothing changes until you press the button.</p>';
    foreach ($problems as $p) {
        $body .= '<div class="alert alert-danger">' . u_h($p) . '</div>';
    }
    $body .= $repair_note ?? '';
    $body .= $problems_note ?? '';
    $body .= '<form method="post" action="update.php">
        <input type="hidden" name="token" value="' . u_h(u_token()) . '"><input type="hidden" name="step" value="1">
        <div class="form-group"><label for="update_url">Download address</label>
            <input type="text" class="form-control" id="update_url" name="update_url" value="' . u_h($urlNow) . '">
            <span class="help-block">An https GitHub branch zip (a fork works too), or the address set as <code>$update_url</code> in config.php.</span></div>
        <div class="form-group"><label><input type="checkbox" name="allow_older" value="1"' . ($older ? ' checked' : '') . '> Allow an older version (rollback)</label>
            <span class="help-block">Without this, a package older than the version you run is refused. A rollback puts the old program files back; database changes are not undone, so back up your database first.</span></div>
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
    $_SESSION['update_older'] = !empty($_POST['allow_older']);
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
    if ($err !== '' || $http !== 200 || $size < 10240 || $magic !== 'PK') {
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
    if (version_compare($newVersion, $self_version, '<') && empty($_SESSION['update_older'])) {
        u_rmtree($pkg);
        u_fail(2, 'This package (' . $newVersion . ') is older than the installed version (' . $self_version . '). Going back is a rollback: tick "Allow an older version" on the first page if you mean it.');
    }
    // look at the database work before any file is replaced, so a refusal here leaves the site exactly as it was
    $look = '';
    try {
        require_once u_path('system/autoload/Migrator.php');
        $pf = (new Migrator(u_db(), u_path('system/updates.json')))->preflight($folder . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'updates.json', $self_version);
        if ($pf['problems']) {
            u_rmtree($pkg);
            u_fail(2, implode(' ', $pf['problems']) . ' No program file was replaced.');
        }
        foreach ($pf['warnings'] as $w) {
            $look .= '<div class="alert alert-warning">' . u_h($w) . '</div>';
        }
        if ($pf['count'] > 0) {
            $look .= '<p>Database changes to apply: <b>' . (int) $pf['count'] . '</b>.';
            $big = array_slice($pf['tables'], 0, 3, true);
            if ($big) {
                $parts = [];
                foreach ($big as $t => [$rows, $mb]) {
                    $parts[] = u_h($t) . ' (about ' . number_format($rows) . ' rows, ' . $mb . ' MB)';
                }
                $look .= ' Largest tables touched: ' . implode(', ', $parts) . '.';
            }
            $look .= '</p>';
        }
    } catch (Throwable $e) {
        // the check could not be completed (database unreachable, unreadable package): no file is replaced on a guess
        u_rmtree($pkg);
        u_fail(2, 'The database work could not be checked: ' . $e->getMessage() . ' No program file was replaced.');
    }
    $run['folder'] = $folder;
    $run['to'] = $newVersion;
    $_SESSION['update_run'] = $run;
    u_page('Step 2 of 5 - package checked', '<p>Version <b>' . u_h($newVersion) . '</b> is a valid PHPNuxBill package. Backing up and installing next...</p>' . $look, 3);
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
    $pkgFiles = u_pkg_files($folder);
    $created = [];
    foreach ($pkgFiles as $f) { // everything else the package overwrites is backed up too, and what it adds is remembered for a rollback
        $inReplaced = false;
        foreach ($replaced as $d) {
            if (strpos($f, $d . '/') === 0) {
                $inReplaced = true;
            }
        }
        if (is_file(u_path($f))) {
            if (!$inReplaced) {
                $z->addFile(u_path($f), $f);
            }
        } else {
            $created[] = $f;
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
    foreach ($failed ? [] : $pkgFiles as $f) { // the copy must match the package, file by file
        if (!is_file(u_path($f)) || filesize(u_path($f)) !== filesize($folder . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $f))) {
            $failed++;
        }
    }
    $versionNow = (string) (json_decode((string) @file_get_contents(u_path('version.json')), true)['version'] ?? '');
    if ($failed || $versionNow !== $run['to'] || !is_file(u_path('system/boot.php'))) {
        foreach ($replaced as $d) { // roll back
            if (isset($moved[$d])) {
                u_rmtree(u_path($d));
                @rename($moved[$d], u_path($d));
            }
        }
        foreach ($created as $f) { // files the package added
            @unlink(u_path($f));
        }
        $zr = new ZipArchive(); // every file the package overwrote comes back from the backup
        if ($zr->open($backup) === true) {
            $zr->extractTo(__DIR__);
            $zr->close();
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
    // The database changes. They are recorded in the database as they run (system/autoload/Migrator.php), so a request that is cut off
    // resumes here, and the run stops at the first real error instead of piling later changes on a broken one.
    require_once u_path('system/autoload/Migrator.php');
    try {
        $db = u_db();
        $migrator = new Migrator($db, u_path('system/updates.json'));
        $migrator->ensureTable();
        if ($migrator->badBaseline() !== null) {
            u_fail(4, Migrator::badBaselineHelp($self_version));
        }
        if ($migrator->isEmpty()) {
            // first run on this site: everything up to the version it was on counts as applied (an older record is honoured too)
            $doneFile = u_path('system/cache/updates.done.json');
            $dones = is_file($doneFile) ? (json_decode((string) file_get_contents($doneFile), true) ?: []) : [];
            // Only entries that are real versions count: the older updater wrote the keys of updates.json here, as strings. Anything else
            // (true, a number, junk) marks nothing as done, yet a non-empty list would skip the stop below and let every change since the
            // first one run again; with no real entry left, the step stops and asks instead.
            $known = array_map('strval', array_keys((array) json_decode((string) @file_get_contents(u_path('system/updates.json')), true)));
            $dones = array_values(array_filter(is_array($dones) ? $dones : [], function ($v) use ($known) {
                return is_string($v) && in_array($v, $known, true);
            }));
            $from = (string) ($run['from'] ?? '');
            // An update started by an older copy of this page can leave text here ("the previous version") in the session that
            // carries over once the files are replaced: that is not a starting point, so it counts as unknown and only the older
            // record ($dones) is used; with neither, the step stops below instead of guessing.
            if (!preg_match('/^\d+(\.\d+)*$/', $from)) {
                $from = '';
            }
            if ($from === '' && !$dones) {
                // Without the version the database is at, every change since the first one would run again, and old ones undo newer ones
                // (they shrink lists such as the staff roles and plan types). The same rule as the command line: never guess.
                u_fail(4, 'It is not known which version this database was last updated to, so the database step was not run. '
                    . 'From the command line, run: php system/cron/migrate.php --baseline=VERSION (with the version the database is at; '
                    . 'the files say ' . $self_version . '), then run php system/cron/migrate.php or this step again.');
            }
            $migrator->baseline($from, $dones);
        }
        if ($migrator->pending()) {
            $migrator->pause(); // customers see the maintenance page while tables change; staff pages keep working
        }
        $result = $migrator->run(20);
        if (!$result['failed'] && !$result['locked'] && $result['remaining'] === 0) {
            $migrator->resume();
        }
    } catch (Throwable $e) {
        u_fail(4, 'The database could not be updated: ' . $e->getMessage());
    }
    if ($result['locked']) {
        u_fail(4, 'Another database update is running. Wait for it to finish, then try again.');
    }
    $run['db_applied'] = (int) ($run['db_applied'] ?? 0) + $result['applied'];
    $run['db_skipped'] = (int) ($run['db_skipped'] ?? 0) + $result['skipped'];
    $_SESSION['update_run'] = $run;
    if ($result['failed']) {
        [$fv, $fp, $fsql, $ferr] = $result['failed'];
        $body = '<div class="alert alert-danger"><b>Version ' . u_h($fv) . ', statement ' . ((int) $fp + 1) . '</b><br>' . u_h($ferr) . '<br><code>' . u_h(mb_strimwidth($fsql, 0, 300, '...')) . '</code></div>
            <p>The database changes were stopped here and nothing after this one was run. The new program files are already installed. Fix the cause
            (permissions, a full disk, data that blocks a unique key), then run this step again: it carries on from this statement.</p>
            <p>Customers see the maintenance page until the database is finished. Once it is, it is lifted by itself.</p>
            <form method="post" action="update.php"><input type="hidden" name="token" value="' . u_h(u_token()) . '"><input type="hidden" name="step" value="4">
            <button type="submit" class="btn btn-primary">Try this step again</button> <a href="./?_route=dashboard" class="btn">Leave it for now</a></form>
            <p class="help-block">From the command line: <code>php system/cron/migrate.php</code></p>';
        u_page('Database update stopped', $body, null, '', ['bad' => 4, 'done' => 3]);
    }
    if ($result['remaining'] > 0) {
        u_page('Step 4 of 5 - database', '<p>Database changes applied so far: <b>' . (int) $run['db_applied'] . '</b>. ' . (int) $result['remaining'] . ' still to go; this page continues by itself.</p>', 4);
    }
    u_page('Step 4 of 5 - database', '<p>Database changes applied: <b>' . (int) $run['db_applied'] . '</b>' . ($run['db_skipped'] ? ' (' . (int) $run['db_skipped'] . ' were already in place)' : '') . '.</p>', 5);
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
$from = (string) ($run['from'] ?? '');
// the version line only when the version changed (finishing the database alone, after a repair, keeps it)
$summary = ($from !== '' && $from !== $version ? '<div class="ver">' . u_h($from) . ' <span>to</span> ' . u_h($version) . '</div>' : '')
    . '<p>PHPNuxBill is up to date.</p>';
if (!empty($run['backup'])) {
    $summary .= '<p>Backup of the replaced files: <code>' . u_h($run['backup']) . '</code> (restore it by unzipping over the site folder if something is wrong).</p>';
}
if (!empty($run['db_applied']) || !empty($run['db_skipped'])) {
    $summary .= '<p>Database changes applied: <b>' . (int) ($run['db_applied'] ?? 0) . '</b>' . (!empty($run['db_skipped']) ? ' (' . (int) $run['db_skipped'] . ' were already in place)' : '') . '.</p>';
}
$summary .= '<p><a href="./?_route=dashboard" class="btn btn-primary">Open the dashboard</a></p>';
u_unlock();
u_page('Update finished', $summary, null, '', ['all_done' => true]);
