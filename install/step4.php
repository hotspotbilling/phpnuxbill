<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

require __DIR__ . '/lib.php';
pnb_guard();

function pnb_back($error, $detail = '')
{
    if ($detail !== '') {
        $_SESSION['pnb_detail'] = $detail;
    }
    header('location: step3.php?_error=' . $error);
    exit;
}

/** Run a script of several SQL statements and surface the first error of ANY of them (a plain exec() only reports the first statement). */
function pnb_run_sql(PDO $db, $sql)
{
    $stmt = $db->query($sql);
    do {
        // each rowset is one statement; with exceptions on, a failing statement throws here
    } while ($stmt->nextRowset());
}

/** A PHP string literal for config.php: every character is escaped, so no value can end the string and add code. */
function pnb_lit($v)
{
    return var_export((string) $v, true);
}

// "Test the connection" on the form: answers with JSON and changes nothing
if (($_GET['action'] ?? '') === 'test') {
    header('Content-Type: application/json');
    $answer = function ($ok, $message = '', $extra = []) {
        echo json_encode(['ok' => $ok, 'message' => $message] + $extra);
        exit;
    };
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pnb_token_ok($_POST['token'] ?? '')) {
        $answer(false, 'The page expired. Reload it and try again.');
    }
    $h = trim((string) ($_POST['dbhost'] ?? ''));
    $n = trim((string) ($_POST['dbname'] ?? ''));
    if ($h === '' || !preg_match('/^[A-Za-z0-9_.:\/-]{1,255}$/', $h) || !preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $n) || trim((string) ($_POST['dbuser'] ?? '')) === '') {
        $answer(false, 'Fill in the host, user and a database name made of letters, digits, _ and -.');
    }
    try {
        $t = new PDO("mysql:host=$h;dbname=$n;charset=utf8mb4", trim((string) $_POST['dbuser']), (string) ($_POST['dbpass'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 6]);
        $answer(true, '', ['existing' => (bool) $t->query("SHOW TABLES LIKE 'tbl\\_users'")->fetchColumn(), 'version' => (string) $t->query('SELECT VERSION()')->fetchColumn()]);
    } catch (PDOException $e) {
        $answer(false, 'Could not connect: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('location: step3.php');
    exit;
}
if (!pnb_token_ok($_POST['token'] ?? '')) {
    pnb_back('token');
}

$db_host = trim((string) ($_POST['dbhost'] ?? ''));
$db_user = trim((string) ($_POST['dbuser'] ?? ''));
$db_pass = (string) ($_POST['dbpass'] ?? '');
$db_name = trim((string) ($_POST['dbname'] ?? ''));
$radius = ($_POST['radius'] ?? '') === 'yes';
$wipe = ($_POST['wipe'] ?? '') === 'yes';
$adminPass = (string) ($_POST['adminpass'] ?? '');
$_SESSION['pnb_form'] = ['dbhost' => $db_host, 'dbuser' => $db_user, 'dbname' => $db_name, 'radius' => $radius];

if ($db_host === '' || $db_user === '' || !preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $db_name) || !preg_match('/^[A-Za-z0-9_.:\/-]{1,255}$/', $db_host)) {
    pnb_back('input');
}
if (strlen($adminPass) < 10 || !hash_equals($adminPass, (string) ($_POST['adminpass2'] ?? ''))) {
    pnb_back('password');
}

try {
    $dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $ex) {
    pnb_back('connect', $ex->getMessage());
}

// the setup script starts with DROP TABLE IF EXISTS: never wipe a database that already holds a PHPNuxBill without being told
$existing = $dbh->query("SHOW TABLES LIKE 'tbl\\_users'")->fetchColumn();
if ($existing && !$wipe) {
    pnb_back('exists');
}

try {
    $setup = (string) file_get_contents(__DIR__ . '/phpnuxbill.sql');
    $radiusSetup = $radius ? (string) file_get_contents(__DIR__ . '/radius.sql') : '';
    // a DROP or CREATE waits for any other connection using the table (a request still running, a cron job, an earlier attempt that timed out):
    // give up after 20 seconds with a clear error instead of hanging until the proxy (Cloudflare allows 100 seconds) answers 524
    @set_time_limit(0);
    $dbh->exec('SET SESSION lock_wait_timeout = 20');
    if ($wipe) {
        // the setup scripts only drop some of the tables they create, so a fresh start drops every one of them first (and nothing else in the database)
        preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i', $setup . "\n" . $radiusSetup, $m);
        $dbh->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (array_unique($m[1]) as $table) {
            $dbh->exec('DROP TABLE IF EXISTS `' . $table . '`');
        }
        $dbh->exec('SET FOREIGN_KEY_CHECKS=1');
    }
    pnb_run_sql($dbh, $setup);
    if ($radius) {
        pnb_run_sql($dbh, $radiusSetup);
    }
    $set = $dbh->prepare("UPDATE tbl_users SET password = ? WHERE username = 'admin'");
    $set->execute([password_hash($adminPass, PASSWORD_BCRYPT)]);
    if ($set->rowCount() !== 1) {
        throw new Exception('The administrator account was not created by the setup script.');
    }
    // the setup script is the current schema: every database change in updates.json is already part of it, so none of them may run again later
    $dbh->exec('DROP TABLE IF EXISTS tbl_migrations');
    require_once dirname(__DIR__) . '/system/autoload/Migrator.php';
    $migrator = new Migrator($dbh, dirname(__DIR__) . '/system/updates.json');
    $versions = $migrator->versions();
    $migrator->baseline($versions ? end($versions) : '');
} catch (Throwable $e) {
    $detail = $e->getMessage();
    if (stripos($detail, 'lock wait timeout') !== false) {
        $detail .= ' Another connection is holding one of the tables open. Stop the cron jobs and anything else using this database, or run SHOW PROCESSLIST in MySQL and KILL the stuck connection, then try again.';
    }
    pnb_back('import', $detail);
}

// config.php is written last: when anything above fails there is nothing half-installed to lock the installer
$config = '<?php
$protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" || $_SERVER["SERVER_PORT"] == 443) ? "https://" : "http://";
$host = $_SERVER["HTTP_HOST"];
$baseDir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\\\");
define("APP_URL", $protocol . $host . $baseDir);

// Live, Dev, Demo
$_app_stage = "Live";

// Database PHPNuxBill
$db_host = ' . pnb_lit($db_host) . ';
$db_user = ' . pnb_lit($db_user) . ';
$db_pass = ' . pnb_lit($db_pass) . ';
$db_name = ' . pnb_lit($db_name) . ';
' . ($radius ? '
// Database Radius
$radius_host = ' . pnb_lit($db_host) . ';
$radius_user = ' . pnb_lit($db_user) . ';
$radius_pass = ' . pnb_lit($db_pass) . ';
$radius_name = ' . pnb_lit($db_name) . ';
' : '') . '
if ($_app_stage != "Live") {
    error_reporting(E_ERROR);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
} else {
    error_reporting(E_ERROR);
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}
';
$written = @file_put_contents(pnb_root() . '/config.php', $config, LOCK_EX);
if ($written === false) {
    $_SESSION['pnb_done'] = time();
    pnb_head('Create config.php', 4, ['bad' => true]);
    echo '<h1>One file to create by hand</h1>
        <div class="banner warn"><span class="led"></span><p><b>The database is ready, but config.php could not be written</b>, because the site folder is not writable.</p></div>
        <p>Create a file named <code>config.php</code> in the site folder with exactly this content, then reload the site:</p>
        <pre class="config">' . pnb_h($config) . '</pre>
        <div class="actions"><a href="../" class="btn">I created it, open the site</a></div>';
    pnb_foot();
    exit;
}
@chmod(pnb_root() . '/config.php', 0640);
$_SESSION['pnb_done'] = time();
unset($_SESSION['pnb_form'], $_SESSION['pnb_detail']);

pnb_head('Installed', 4);
?>
<h1>The database is ready</h1>
<p class="lede">The tables are created, the administrator is set up and <code>config.php</code> is written.</p>
<form action="step5.php" method="post">
    <input type="hidden" name="token" value="<?= pnb_h(pnb_token()) ?>">
    <div class="actions"><button type="submit" class="btn">Continue</button></div>
</form>
<?php pnb_foot();
