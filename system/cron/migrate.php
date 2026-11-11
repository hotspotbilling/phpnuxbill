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
 *    php system/cron/migrate.php --repair=VERSION
 *                                             when the list of finished updates is damaged (an older updater wrote
 *                                             "the previous version"): start the list again from VERSION
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
$opts = getopt('', ['status', 'baseline:', 'repair:']);

$done = $root . '/system/cache/updates.done.json';
$doneVersions = is_file($done) ? (json_decode((string) file_get_contents($done), true) ?: []) : [];

if (isset($opts['baseline'])) {
    if (!$m->isEmpty()) {
        fwrite(STDERR, "This site already has a migration record; a baseline is only for a site with none.\n");
        exit(1);
    }
    // versions an older updater recorded as done are honoured as well
    echo 'Marked ' . $m->baseline((string) $opts['baseline'], $doneVersions) . " version(s) as applied.\n";
    exit(0);
}

if ($m->isEmpty()) {
    // Which version the database is really at cannot be read from the files (an update may have brought new files and stopped before the
    // database), so it is never guessed: say where to start.
    $installed = (string) (json_decode((string) @file_get_contents($root . '/version.json'), true)['version'] ?? '');
    fwrite(STDERR, "This site has no migration record yet, so it is not known which database changes are already in place.\n"
        . 'Run this first with the version the database was last updated to' . ($installed !== '' ? " (the files say $installed)" : '')
        . ":\n  php system/cron/migrate.php --baseline=VERSION\n");
    exit(isset($opts['status']) ? 0 : 1);
}

if (isset($opts['repair'])) {
    // the same as the Repair button on update.php: only acts while the list of finished updates is damaged
    echo $m->repairBaseline((string) $opts['repair'])
        ? 'Repaired. ' . count($m->pending()) . " database update(s) still to run: php system/cron/migrate.php\n"
        : "The list of finished updates is fine; nothing was changed.\n";
    exit(0);
}

if ($m->badBaseline() !== null) {
    $installed = (string) (json_decode((string) @file_get_contents($root . '/version.json'), true)['version'] ?? '');
    fwrite(STDERR, wordwrap(Migrator::badBaselineHelp($installed), 120) . "\n");
    exit(isset($opts['status']) ? 0 : 1);
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
    foreach ($pf['warnings'] as $warning) {
        echo "  WARNING: $warning\n";
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
