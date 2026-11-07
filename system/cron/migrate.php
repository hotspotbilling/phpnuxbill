<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 *  Apply the database changes from the command line. Use it when the database is large (a table change can take
 *  minutes, longer than a web request may run), or to finish an update that stopped.
 *
 *    php system/cron/migrate.php              apply what is pending
 *    php system/cron/migrate.php --status     show what is pending and what failed, change nothing
 *    php system/cron/migrate.php --baseline=VERSION
 *                                             on a site with no record yet: mark everything up to VERSION as applied
 **/

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
if (!is_file($root . '/config.php')) {
    fwrite(STDERR, "config.php not found: PHPNuxBill is not installed here.\n");
    exit(1);
}
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? 80;
include $root . '/config.php';
if (!empty($db_password) && empty($db_pass)) {
    $db_pass = $db_password;
}
require_once __DIR__ . '/../autoload/Migrator.php';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    fwrite(STDERR, 'The database could not be reached: ' . $e->getMessage() . "\n");
    exit(1);
}

$m = new Migrator($db, $root . '/system/updates.json');
$m->ensureTable();
$opts = getopt('', ['status', 'baseline:']);

if (isset($opts['baseline'])) {
    if (!$m->isEmpty()) {
        fwrite(STDERR, "This site already has a migration record; a baseline is only for a site with none.\n");
        exit(1);
    }
    echo 'Marked ' . $m->baseline((string) $opts['baseline']) . " version(s) as applied.\n";
    exit(0);
}

if ($m->isEmpty()) {
    $installed = (string) (json_decode((string) @file_get_contents($root . '/version.json'), true)['version'] ?? '');
    $done = $root . '/system/cache/updates.done.json';
    $doneVersions = is_file($done) ? (json_decode((string) file_get_contents($done), true) ?: []) : [];
    // the site is taken to be at the version in version.json; if an update brought new files but stopped before the database, pass
    // --baseline=<the version you came from> first
    echo 'No migration record yet: everything up to ' . ($installed ?: '?') . " counts as applied (use --baseline=VERSION to choose another).\n";
    if (isset($opts['status'])) {
        echo "Nothing is changed by --status; run without it to record that starting point and apply anything newer.\n";
        exit(0);
    }
    $m->baseline($installed, $doneVersions);
}

$pending = $m->pending();
foreach ($m->failures() as $f) {
    echo 'FAILED  ' . $f['version'] . ' #' . ((int) $f['position'] + 1) . ': ' . $f['error'] . "\n";
}
if (isset($opts['status'])) {
    echo count($pending) . " statement(s) pending.\n";
    $pf = $m->preflight();
    foreach (array_slice($pf['tables'], 0, 5, true) as $t => [$rows, $mb]) {
        echo "  touches $t: about " . number_format($rows) . " rows, $mb MB\n";
    }
    if ($pf['free_mb'] !== null) {
        echo '  free disk where the database lives: ' . $pf['free_mb'] . " MB\n";
    }
    foreach ($pf['problems'] as $problem) {
        echo "  PROBLEM: $problem\n";
    }
    foreach (array_slice($pending, 0, 50) as [$v, $pos, $sql]) {
        echo '  ' . $v . ' #' . ($pos + 1) . '  ' . mb_strimwidth(preg_replace('/\s+/', ' ', $sql), 0, 100, '...') . "\n";
    }
    exit(0);
}
if (!$pending) {
    echo "Nothing to do: the database is up to date.\n";
    exit(0);
}

$m->pause();
$res = $m->run(null, function ($v, $pos, $sql, $result) {
    echo str_pad(strtoupper($result), 8) . $v . ' #' . ($pos + 1) . '  ' . mb_strimwidth(preg_replace('/\s+/', ' ', $sql), 0, 90, '...') . "\n";
});
if ($res['locked']) {
    fwrite(STDERR, "Another database update is running.\n");
    exit(1);
}
if ($res['failed']) {
    fwrite(STDERR, "\nStopped at version " . $res['failed'][0] . ': ' . $res['failed'][3] . "\nNothing after it was run. Customers still see the maintenance page; fix the cause and run this command again, which lifts it when done.\n");
    exit(1);
}
$m->resume();
echo "\nDone: {$res['applied']} applied, {$res['skipped']} already in place.\n";
