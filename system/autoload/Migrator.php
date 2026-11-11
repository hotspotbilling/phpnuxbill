<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Applies the database changes in system/updates.json and remembers, in the database itself, which ones are done.
 *
 * - The record is the table tbl_migrations (one row per statement), so restoring a database backup restores its own record, and clearing
 *   the cache folder cannot make the history run again.
 * - A new install and an existing site are given a baseline: everything up to the version they are on counts as applied, so only newer
 *   changes run.
 * - Each statement is recorded as it finishes, so a request that dies halfway resumes where it stopped.
 * - The run stops at the first real error. Nothing after it runs until it is fixed, because later changes may depend on it.
 *
 * Plain PDO only: it has to work while the rest of the program is being replaced (update.php) and from the command line.
 */
class Migrator
{
    /** MySQL answers these when a change is already in place: harmless on a re-run. */
    const ALREADY = [1050, 1060, 1061, 1068, 1091];

    private $db;
    private $updates = [];
    private $locked = false;

    public function __construct(PDO $db, $updatesFile)
    {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // an EXECUTE of a guarded statement can answer with a result set (the "nothing to do" branch is a SELECT); it must not block the next query
        $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $data = is_file($updatesFile) ? json_decode((string) file_get_contents($updatesFile), true) : [];
        $this->updates = is_array($data) ? $data : [];
        uksort($this->updates, 'version_compare'); // oldest first, whatever order the file is in
    }

    public function ensureTable()
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS tbl_migrations (
            id int(11) NOT NULL AUTO_INCREMENT,
            version varchar(32) NOT NULL,
            position int(11) NOT NULL,
            stmt_hash char(40) NOT NULL,
            status enum('ok','skipped','baseline','failed') NOT NULL,
            error text DEFAULT NULL,
            applied_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY version_position (version, position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function versions()
    {
        return array_keys($this->updates);
    }

    /** True when nothing has ever been recorded: a site that has not used this record yet. */
    public function isEmpty()
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM tbl_migrations')->fetchColumn() === 0;
    }

    /**
     * Give a site its starting point. Versions in $doneVersions (an older record) and every version up to $upToVersion count as applied.
     * Only does anything while the record is empty, so it is safe to call before every run.
     *
     * @return int number of versions marked
     */
    public function baseline($upToVersion, array $doneVersions = [])
    {
        // Creating the record table is DDL and commits implicitly: inside a transaction of the caller's it would commit their work. Refuse before
        // any DDL runs.
        if ($this->db->inTransaction()) {
            throw new LogicException('Migrator::baseline() cannot be called inside a database transaction, because creating the record table would commit it.');
        }
        // a starting point that is not a version number ("the previous version") would cover nothing, so the whole history would run again
        if ($upToVersion !== '' && !preg_match('/^\d+(\.\d+)*$/', (string) $upToVersion)) {
            throw new InvalidArgumentException('Migrator::baseline() needs a version number such as 2026.11.7, not "' . $upToVersion . '".');
        }
        $this->ensureTable(); // DDL commits implicitly, so it stays outside the transaction below
        // all or nothing: a baseline cut off halfway would leave the record non-empty, so a later call would never complete it
        $own = !$this->db->inTransaction();
        if ($own) {
            $this->db->beginTransaction();
        }
        try {
            if (!$this->isEmpty()) {
                if ($own) {
                    $this->db->commit();
                }
                return 0;
            }
            $n = 0;
            // A marker row, so that the record is no longer empty even when no version is covered (an unknown starting point, or one older than
            // every migration): the next run then knows a starting point was chosen. Statement positions start at 0, so -1 never matches one.
            $this->record(substr(($upToVersion !== '' ? $upToVersion : 'none'), 0, 32), -1, 'baseline', 'baseline', null);
            foreach ($this->updates as $version => $queries) {
                $isDone = in_array((string) $version, array_map('strval', $doneVersions), true)
                    || ($upToVersion !== '' && preg_match('/^\d/', $upToVersion) && version_compare((string) $version, $upToVersion, '<='));
                if ($isDone) {
                    foreach (array_values($queries) as $pos => $q) {
                        $this->record($version, $pos, $q, 'baseline', null);
                    }
                    $n++;
                }
            }
            if ($own) {
                $this->db->commit();
            }
            return $n;
        } catch (Throwable $e) {
            if ($own && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * The starting point written by an older updater as text ("the previous version") instead of a version number. It covered no version,
     * so the whole history counts as pending; running it would let old changes undo newer ones. Returns that text, or null when all is well.
     */
    public function badBaseline()
    {
        $st = $this->db->query("SELECT version FROM tbl_migrations WHERE status = 'baseline' AND position = -1");
        foreach ($st ? $st->fetchAll(PDO::FETCH_COLUMN) : [] as $v) {
            if ($v !== 'none' && !preg_match('/^\d+(\.\d+)*$/', (string) $v)) {
                return (string) $v;
            }
        }
        return null;
    }

    /** How to repair a bad starting point (the update page has a Repair button that does the same). */
    public static function badBaselineHelp($filesVersion)
    {
        return 'The list of database updates already done is damaged, so old updates look unfinished. Open update.php in the browser and '
            . 'press "Repair the list", or from the command line run: php system/cron/migrate.php --repair'
            . ($filesVersion !== '' ? '=' . $filesVersion : '=VERSION') . ' (the version this site was running).';
    }

    /**
     * Repair a bad starting point: forget the record and start again from $version (everything up to it counts as done).
     * Only acts while the starting point is bad, so it can never wipe a good record.
     */
    public function repairBaseline($version)
    {
        if ($this->badBaseline() === null) {
            return false;
        }
        if (!preg_match('/^\d+(\.\d+)*$/', (string) $version)) {
            throw new InvalidArgumentException('Enter a version number such as 2026.11.7.');
        }
        $this->db->exec('DELETE FROM tbl_migrations');
        $this->baseline((string) $version);
        return true;
    }

    /** @return array of [version, position, sql] not yet applied, in the order they will run */
    public function pending()
    {
        $this->ensureTable();
        $have = [];
        foreach ($this->db->query("SELECT version, position, stmt_hash FROM tbl_migrations WHERE status <> 'failed'") as $r) {
            $have[$r['version'] . '#' . $r['position']] = $r['stmt_hash'];
        }
        $out = [];
        foreach ($this->updates as $version => $queries) {
            $queries = array_values($queries);
            foreach (self::groups($queries) as $group) {
                // an edited statement counts as new: it runs again (the "already there" codes keep that harmless)
                $todo = false;
                foreach ($group as $pos) {
                    if (($have[$version . '#' . $pos] ?? null) !== sha1($queries[$pos])) {
                        $todo = true;
                    }
                }
                // a SET @s / PREPARE / EXECUTE / DEALLOCATE group lives in one connection, so it runs again as a whole, never from the middle
                foreach ($todo ? $group : [] as $pos) {
                    $out[] = [(string) $version, $pos, $queries[$pos]];
                }
            }
        }
        return $out;
    }

    /** Positions of a version's statements, grouped: a SET @var ... DEALLOCATE PREPARE run is one group, anything else stands alone. */
    private static function groups(array $queries)
    {
        $groups = [];
        $cur = null;
        foreach ($queries as $pos => $q) {
            if ($cur === null && preg_match('/^\s*SET\s+@\w+\s*=/i', $q) && isset($queries[$pos + 1]) && preg_match('/^\s*PREPARE\b/i', $queries[$pos + 1])) {
                $cur = [];
            }
            if ($cur !== null) {
                $cur[] = $pos;
                if (preg_match('/^\s*(DEALLOCATE|DROP)\s+PREPARE\b/i', $q)) {
                    $groups[] = $cur;
                    $cur = null;
                }
                continue;
            }
            $groups[] = [$pos];
        }
        if ($cur !== null) {
            $groups[] = $cur; // an unfinished group at the end of a version
        }
        return $groups;
    }

    /**
     * Put the customer side into maintenance mode (the setting the admin can switch in Settings) while tables change, and remember that
     * this was done here so resume() only undoes what pause() did. Staff pages are not affected. Does nothing if the mode is already on.
     */
    public function pause()
    {
        $cfg = $this->setting('maintenance_mode');
        if ($cfg === '1' || $this->setting('update_set_maintenance') === '1') {
            return;
        }
        $this->setSetting('maintenance_mode', '1');
        $this->setSetting('update_set_maintenance', '1');
    }

    /** Lift the maintenance mode set by pause(); a mode the admin switched on themselves is left alone. */
    public function resume()
    {
        if ($this->setting('update_set_maintenance') === '1') {
            $this->setSetting('maintenance_mode', '0');
            $this->db->exec("DELETE FROM tbl_appconfig WHERE setting = 'update_set_maintenance'");
        }
    }

    private function setting($name)
    {
        $st = $this->db->prepare('SELECT value FROM tbl_appconfig WHERE setting = ?');
        $st->execute([$name]);
        $v = $st->fetchColumn();
        return $v === false ? null : (string) $v;
    }

    private function setSetting($name, $value)
    {
        if ($this->setting($name) === null) {
            $this->db->prepare('INSERT INTO tbl_appconfig (setting, value) VALUES (?, ?)')->execute([$name, $value]);
        } else {
            $this->db->prepare('UPDATE tbl_appconfig SET value = ? WHERE setting = ?')->execute([$value, $name]);
        }
    }

    /**
     * What a run would do, for a look before anything changes: the statements that would run, the tables they touch with their size,
     * whether the database account may change tables, and the free disk space where the data lives.
     *
     * @param string|null $updatesFile an updates.json to look at instead of the installed one (the package that is about to be installed)
     * @param string|null $from        the version the site is on, for a site with no record yet
     * @return array count, tables [name => [rows, mb]], problems (strings that should stop the update), warnings (worth reading, never stop it), free_mb (null when unknown)
     */
    public function preflight($updatesFile = null, $from = null)
    {
        $other = $updatesFile ? new self($this->db, $updatesFile) : $this;
        $out = ['count' => 0, 'tables' => [], 'problems' => [], 'warnings' => [], 'free_mb' => null];
        try {
            $grants = implode(' ', $this->db->query('SHOW GRANTS FOR CURRENT_USER()')->fetchAll(PDO::FETCH_COLUMN));
            $all = (bool) preg_match('/\\bALL PRIVILEGES\\b/i', $grants);
            $missing = [];
            foreach (['ALTER', 'CREATE', 'DROP', 'INDEX'] as $priv) {
                if (!$all && !preg_match('/\\b' . $priv . '\\b/i', $grants)) {
                    $missing[] = $priv;
                }
            }
            // only a warning: SHOW GRANTS does not show roles or privileges held through other accounts, so it can be wrong
            if ($missing) {
                $out['warnings'][] = 'The database account does not appear to have ' . implode(', ', $missing) . ' permission, which the changes need.';
            }
        } catch (PDOException $e) {
            // grants cannot be read on this server: the run itself will report a refused statement
        }
        try {
            $this->ensureTable();
        } catch (PDOException $e) {
            // without its record table the run cannot start, and what is pending is unknown: the check is incomplete, so it must stop the update
            $out['problems'][] = 'The migration record table cannot be created or read, so the database changes cannot be checked or applied: ' . $e->getMessage();
            return $out;
        }
        $names = [];
        foreach ($other->pending() as [$v, $pos, $sql]) {
            // a site with no record yet starts from the version it is on
            if ($this->isEmpty() && $from !== null && $from !== '' && version_compare($v, $from, '<=')) {
                continue;
            }
            $out['count']++;
            if (preg_match('/^\s*(?:ALTER\s+TABLE|UPDATE|DELETE\s+FROM|INSERT\s+(?:IGNORE\s+)?INTO|CREATE\s+(?:UNIQUE\s+)?INDEX\s+\S+\s+ON|TRUNCATE\s+TABLE|RENAME\s+TABLE)\s+`?(\w+)`?/i', $sql, $m)) {
                $names[strtolower($m[1])] = true;
            }
        }
        if ($names) {
            $in = implode(',', array_fill(0, count($names), '?'));
            $st = $this->db->prepare("SELECT TABLE_NAME, TABLE_ROWS, (DATA_LENGTH + INDEX_LENGTH) / 1048576 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) IN ($in)");
            $st->execute(array_keys($names));
            foreach ($st->fetchAll(PDO::FETCH_NUM) as [$t, $rows, $mb]) {
                $out['tables'][$t] = [(int) $rows, round((float) $mb, 1)];
            }
            uasort($out['tables'], function ($a, $b) {
                return $b[1] <=> $a[1];
            });
        }
        if ($out['count'] > 0) {
            try {
                $dir = (string) $this->db->query('SELECT @@datadir')->fetchColumn();
                $free = $dir !== '' && is_dir($dir) ? @disk_free_space($dir) : false;
                if ($free !== false) {
                    $out['free_mb'] = (int) round($free / 1048576);
                    // changing a table rewrites it, so the biggest table has to fit again beside the original
                    $big = $out['tables'] ? max(array_column($out['tables'], 1)) : 0;
                    if ($out['free_mb'] < $big * 1.2 + 50) {
                        $out['problems'][] = 'Free disk space where the database lives (' . $out['free_mb'] . ' MB) is less than the largest table to change needs (about ' . (int) ceil($big * 1.2 + 50) . ' MB).';
                    }
                }
            } catch (PDOException $e) {
                // the data folder is not visible from here (a remote database): skip the check
            }
        }
        return $out;
    }

    /** The statements that failed last time, for the report. */
    public function failures()
    {
        $this->ensureTable();
        return $this->db->query("SELECT version, position, error FROM tbl_migrations WHERE status = 'failed' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Run the pending statements.
     *
     * @param int|null $seconds stop after the version that is running when this many seconds have passed (a web request must not run
     *                          for ever); a version is never cut in half, because its statements can depend on each other
     * @param callable|null $progress called with (version, position, sql, result) after each statement
     * @return array applied, skipped, remaining, failed (null or [version, position, sql, error]), locked
     */
    public function run($seconds = null, $progress = null)
    {
        $res = ['applied' => 0, 'skipped' => 0, 'remaining' => 0, 'failed' => null, 'locked' => false];
        $this->ensureTable();
        if (!$this->lock()) {
            $res['locked'] = true;
            return $res;
        }
        try {
            $started = time();
            $pending = $this->pending();
            $byVersion = [];
            foreach ($pending as $p) {
                $byVersion[$p[0]][] = $p;
            }
            foreach ($byVersion as $version => $items) {
                if ($seconds !== null && time() - $started >= $seconds) {
                    break;
                }
                foreach ($items as [$v, $pos, $sql]) {
                    try {
                        $r = $this->db->query($sql);
                        if ($r) {
                            $r->closeCursor();
                        }
                        $this->record($v, $pos, $sql, 'ok', null);
                        $res['applied']++;
                        $result = 'ok';
                    } catch (PDOException $e) {
                        $why = $this->ignorable($sql, $e);
                        if ($why === '') {
                            $this->record($v, $pos, $sql, 'failed', $e->getMessage());
                            $res['failed'] = [$v, $pos, $sql, $e->getMessage()];
                            if ($progress) {
                                $progress($v, $pos, $sql, 'failed');
                            }
                            break 2; // nothing after a real error runs
                        }
                        $this->record($v, $pos, $sql, 'skipped', $why);
                        $res['skipped']++;
                        $result = 'skipped';
                    }
                    if ($progress) {
                        $progress($v, $pos, $sql, $result);
                    }
                }
            }
        } finally {
            $this->unlock();
        }
        $res['remaining'] = count($this->pending());
        return $res;
    }

    /** '' when the error is a real one, otherwise why it can be skipped. */
    private function ignorable($sql, PDOException $e)
    {
        $code = (int) ($e->errorInfo[1] ?? 0);
        if (in_array($code, self::ALREADY, true)) {
            return 'already applied';
        }
        // dropping what is already gone
        if ($code === 1051 && preg_match('/^\s*DROP\s+TABLE\b/i', $sql)) {
            return 'already applied';
        }
        // a repeated INSERT; for an ALTER a duplicate means real duplicate data that blocks a unique key, which must not be hidden
        if ($code === 1062 && preg_match('/^\s*INSERT\b/i', $sql)) {
            return 'already applied';
        }
        // the FreeRADIUS tables (nas, rad...) exist only on a site that installed them
        if ($code === 1146 && preg_match('/\.(nas|rad[a-z_]*)\b/i', $e->getMessage())) {
            return 'optional FreeRADIUS table is not installed';
        }
        return '';
    }

    private function record($version, $pos, $sql, $status, $error)
    {
        $st = $this->db->prepare('INSERT INTO tbl_migrations (version, position, stmt_hash, status, error, applied_at) VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE stmt_hash = VALUES(stmt_hash), status = VALUES(status), error = VALUES(error), applied_at = VALUES(applied_at)');
        $st->execute([(string) $version, (int) $pos, sha1($sql), $status, $error]);
    }

    /** Only one run at a time, from any entry point (the web updater, the command line). */
    private function lockName()
    {
        // GET_LOCK is server-wide, so the name carries the schema: sites sharing one MySQL server can migrate at the same time (name <= 64 chars)
        return 'pnb_migrate_' . md5((string) $this->db->query('SELECT DATABASE()')->fetchColumn());
    }

    private function lock()
    {
        $this->locked = (int) $this->db->query("SELECT GET_LOCK('" . $this->lockName() . "', 0)")->fetchColumn() === 1;
        return $this->locked;
    }

    private function unlock()
    {
        if ($this->locked) {
            $this->db->query("SELECT RELEASE_LOCK('" . $this->lockName() . "')")->fetchColumn();
            $this->locked = false;
        }
    }
}
