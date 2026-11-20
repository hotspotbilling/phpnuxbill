<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

use PEAR2\Net\RouterOS\Request;
use PEAR2\Net\RouterOS\Response;

/**
 * Small helpers for talking to a MikroTik over its API with a time limit, shared by the Routers pages and the cron (nightly
 * backups). Every request has a deadline: the library's own sendSync() waits for ever once logged in, and its close() waits for
 * a goodbye, so a router that stops answering is dropped with RouterClient::abort() instead.
 */
class RouterApi
{
    /** One answer as plain text (a long value can arrive as a stream). */
    public static function text($v)
    {
        if (is_resource($v)) {
            $v = stream_get_contents($v);
        }
        return $v === null ? null : (string) $v;
    }

    /** Connect; returns [client, null] or [null, the reason it failed]. */
    public static function connect($ip, $user, $pass, $timeout = 5)
    {
        global $_app_stage;
        try {
            // the stage is written "Demo" in some places and "demo" in others: either one keeps real routers untouched
            if (strcasecmp(trim((string) $_app_stage), 'demo') === 0) {
                return [null, Lang::T('Not available in Demo mode')];
            }
            [$host, $port] = RouterAddress::split($ip);
            // the login is read before any deadline can be set on the client, with the socket's time limit, which the connection
            // takes from default_socket_timeout when it opens (60 seconds unless set): for this connection it is the connect time
            // limit, so a router that accepts the connection and then stays silent is given up on in seconds
            $old = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', (string) max(1, (int) ceil($timeout)));
            try {
                return [new RouterClient($host, $user, $pass, $port, false, $timeout), null];
            } finally {
                ini_set('default_socket_timeout', (string) $old);
            }
        } catch (Throwable $e) {
            // the library wraps the reason (refused, timed out, wrong login) in a general message: show both
            $parts = [];
            for ($x = $e; $x && count($parts) < 3; $x = $x->getPrevious()) {
                $m = trim(preg_replace('/\s+/', ' ', strip_tags($x->getMessage())));
                if ($m !== '' && !in_array($m, $parts, true)) {
                    $parts[] = $m;
                }
            }
            $msg = implode(': ', $parts);
            return [null, $msg !== '' ? mb_substr($msg, 0, 160) : Lang::T('Could not connect to the router')];
        }
    }

    /**
     * A client for the device drivers (PPPoE, Hotspot, VPN, Static). Each command waits at most $readTimeout seconds for its
     * answer (the library's own wait has no end), so a router that goes silent fails the command with an exception (code 408)
     * instead of holding the request for ever; the connection is then dropped without the goodbye that would wait too.
     * $connectTimeout: for the connection and the login (null: the same $readTimeout).
     */
    public static function deviceClient($host, $user, $pass, $port = null, $connectTimeout = null, $readTimeout = 30)
    {
        $old = ini_get('default_socket_timeout');
        // the stream takes its read time limit from this when it opens, whatever it is set to (even "never")
        ini_set('default_socket_timeout', (string) $readTimeout);
        try {
            return (new RouterClient($host, $user, $pass, $port, false, $connectTimeout))->limitSync($readTimeout);
        } finally {
            ini_set('default_socket_timeout', (string) $old);
        }
    }

    /**
     * Send one request and wait for its answer until $deadline (a microtime). Finished means the closing reply (!done, or a
     * fatal one) arrived; otherwise the connection is dropped and an exception (code 408) says the router did not answer.
     */
    public static function send($c, $req, $deadline)
    {
        static $n = 0;
        // a deadline shared by several requests that has already passed: nothing more is sent (a command that changes something
        // must not go out once the time for the whole operation is up)
        if ($deadline - microtime(true) <= 0) {
            throw new Exception(Lang::T('The router did not answer in time'), 408);
        }
        $tag = 'nb' . (++$n);
        $req->setTag($tag);
        $c->sendAsync($req);
        $left = $deadline - microtime(true);
        $recvErr = null;
        if ($left > 0) {
            // every read inside the reply (not only the wait for it to start) ends by the deadline
            if (method_exists($c, 'readDeadline')) {
                $c->readDeadline($deadline);
            }
            try {
                // (each wait inside is capped by the deadline set above, not only the total)
                $c->loop((int) floor($left), (int) (($left - floor($left)) * 1000000));
            } catch (\PEAR2\Net\Transmitter\Exception | \PEAR2\Net\RouterOS\SocketException $e) {
                // the reply is incomplete: a read that ran out of time, or the connection closed mid-reply (told apart below)
                $recvErr = $e;
            } finally {
                if (method_exists($c, 'readDeadline')) {
                    $c->readDeadline(null);
                }
            }
        }
        // (isRequestActive() is no test of being finished: it stays true while a finished answer waits to be collected)
        $res = $c->extractNewResponses($tag);
        foreach ($res as $r) {
            if (in_array($r->getType(), [Response::TYPE_FINAL, Response::TYPE_FATAL], true)) {
                return $res;
            }
        }
        // the router ended the connection itself (a !fatal reply, which belongs to no request): a broken connection, not a
        // router that went quiet
        $fatal = method_exists($c, 'fatalReason') ? $c->fatalReason() : null;
        method_exists($c, 'abort') ? $c->abort() : $c->close();
        if ($fatal !== null) {
            throw new Exception(Lang::T('The router closed the connection') . ($fatal !== 'closed' ? ': ' . mb_substr($fatal, 0, 120) : ''), 503);
        }
        // the connection ended before the reply was complete, with time still left: not a router that went quiet
        if ($recvErr !== null && method_exists($c, 'readTimedOut') && !$c->readTimedOut()) {
            $why = trim(preg_replace('/\s+/', ' ', strip_tags($recvErr->getMessage())));
            throw new Exception(Lang::T('The router closed the connection') . ($why !== '' ? ': ' . mb_substr($why, 0, 120) : ''), 503);
        }
        throw new Exception(Lang::T('The router did not answer in time'), 408);
    }

    /** The first error the router sent back for a request (no permission, unknown command), or null. */
    public static function error($res)
    {
        foreach ($res as $r) {
            if ($r->getType() === Response::TYPE_ERROR) {
                return (string) self::text($r->getProperty('message')) ?: Lang::T('The router refused the command');
            }
        }
        return null;
    }

    /**
     * Run a print (or any command) and return its data items as arrays of the given properties. A refusal is thrown, so the
     * caller can tell "none" from "not allowed". $args are extra arguments, $where is [property => value] to filter on.
     */
    public static function rows($c, $command, array $props, $deadline, array $args = [], array $where = [])
    {
        $q = null;
        foreach ($where as $k => $v) {
            $q = $q ? $q->andWhere($k, $v) : \PEAR2\Net\RouterOS\Query::where($k, $v);
        }
        $req = new Request($command, $q);
        if ($props) {
            $req->setArgument('.proplist', implode(',', $props));
        }
        foreach ($args as $k => $v) {
            $req->setArgument($k, $v);
        }
        $res = self::send($c, $req, $deadline);
        if (($err = self::error($res)) !== null) {
            throw new Exception($err, 403); // the router answered and refused: 403 tells it from no answer (408) and a broken connection
        }
        $out = [];
        foreach ($res as $r) {
            if ($r->getType() !== Response::TYPE_DATA) {
                continue;
            }
            $row = [];
            foreach ($props as $p) {
                $row[$p] = self::text($r->getProperty($p));
            }
            $out[] = $row;
        }
        return $out;
    }

    /** Run a command that changes something; throws when the router refuses it or does not answer. */
    public static function run($c, $command, array $args, $deadline)
    {
        $req = new Request($command);
        foreach ($args as $k => $v) {
            $req->setArgument($k, $v);
        }
        if (($err = self::error(self::send($c, $req, $deadline))) !== null) {
            throw new Exception($err, 403);
        }
    }

    /**
     * Where a backup is saved on this router: "flash/" on boards whose root folder is in memory (it is emptied on every restart,
     * and the board keeps its files in a "flash" folder), the root folder otherwise. From a /file/print list with name and type.
     */
    public static function backupFolder(array $files)
    {
        foreach ($files as $f) {
            if ((string) ($f['name'] ?? '') === 'flash' && in_array((string) ($f['type'] ?? ''), ['disk', 'directory'], true)) {
                return 'flash/';
            }
        }
        return '';
    }

    /**
     * The password backups are encrypted with (Network > Routers > Settings). RouterOS saves a backup without a password
     * unencrypted, with every login and key on the router readable by anyone who gets the file, so no backup is made without one.
     */
    public static function backupPassword()
    {
        global $config;
        return (string) ($config['router_backup_password'] ?? '');
    }

    /**
     * Nightly backups, from the cron: once a day, each enabled router saves "phpnuxbill-auto-<date>.backup" on itself, and only
     * the newest few of those are kept (older ones are removed from the router). Backups made by hand are never removed.
     * A router that fails is tried again on the next runs that day (up to 3 times); one that is done is not touched again. A
     * retry first looks for today's file, so a save that worked but answered late is not made twice, and the clean-up still runs.
     * Never throws; returns lines for the cron's output.
     */
    public static function nightlyBackups()
    {
        global $config, $CACHE_PATH;
        $log = [];
        $lock = null;
        // a time budget of its own, not what the monitoring pass before it left over (each router gets its own again below)
        @set_time_limit(120);
        try {
            if (($config['router_backup'] ?? '0') !== '1') {
                return [];
            }
            $password = self::backupPassword();
            if ($password === '') {
                return [Lang::T('No backup password is set in Network > Routers > Settings, so no backup was made.')];
            }
            // one run at a time: a second cron started meanwhile leaves the work to the one already doing it
            $lock = @fopen($CACHE_PATH . DIRECTORY_SEPARATOR . 'router_backup.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
                return [];
            }
            // today's progress read again under the lock: $config was loaded when this cron started, and a run that finished
            // since may have done routers (or used up their tries) that the snapshot does not know about
            $today = date('Y-m-d');
            $saved = ORM::for_table('tbl_appconfig')->where('setting', 'router_backup_state')->find_one();
            $state = json_decode($saved ? (string) $saved['value'] : '', true);
            if (!is_array($state) || ($state['date'] ?? '') !== $today || !is_array($state['routers'] ?? null)) {
                $state = ['date' => $today, 'routers' => []];
            }
            $pending = function ($id) use (&$state) {
                $s = $state['routers'][$id] ?? 0;
                return $s !== 'ok' && (int) $s < 3;
            };
            $routers = [];
            foreach (ORM::for_table('tbl_routers')->where('enabled', 1)->find_many() as $r) {
                if (trim((string) $r['ip_address']) !== '' && strcasecmp($r['name'], 'radius') !== 0 && $pending((string) $r['id'])) {
                    $routers[] = $r;
                }
            }
            if (!$routers) {
                return [];
            }
            $keep = max(1, min(30, (int) ($config['router_backup_keep'] ?? 7)));
            $name = 'phpnuxbill-auto-' . str_replace('-', '', $today); // the same day as the progress kept above, across midnight too
            $autoFiles = function ($c, &$folder = null) {
                $files = self::rows($c, '/file/print', ['.id', 'name', 'type'], microtime(true) + 15);
                $folder = self::backupFolder($files);
                // only in the folder nightly backups are saved to on this router (flash/ or the root folder): a file of the same
                // name anywhere else (a USB disk, a folder of copies) was not made here and is never removed
                $own = '/^' . preg_quote($folder, '/') . 'phpnuxbill-auto-\d{8}\.backup$/';
                $auto = array_values(array_filter($files, function ($f) use ($own) {
                    return preg_match($own, (string) $f['name']);
                }));
                usort($auto, function ($a, $b) {
                    return strcmp(basename($b['name']), basename($a['name'])); // newest first (the date is in the name)
                });
                return $auto;
            };
            foreach ($routers as $r) {
                $id = (string) $r['id'];
                @set_time_limit(120); // each router gets its own time, so a slow one does not stop the rest
                [$c, $err] = self::connect($r['ip_address'], $r['username'], $r['password'], 8);
                if (!$c) {
                    $state['routers'][$id] = (int) ($state['routers'][$id] ?? 0) + 1;
                    $log[] = $r['name'] . ': ' . $err;
                    self::saveBackupState($state);
                    continue;
                }
                try {
                    $folder = '';
                    $auto = $autoFiles($c, $folder);
                    $made = false;
                    foreach ($auto as $f) {
                        $made = $made || basename($f['name']) === $name . '.backup';
                    }
                    if (!$made) {
                        // encrypted with the backup password (never written to the log or the output)
                        self::run($c, '/system/backup/save', ['name' => $folder . $name, 'password' => $password], microtime(true) + 60);
                        $auto = $autoFiles($c);
                    }
                    foreach (array_slice($auto, $keep) as $old) {
                        self::run($c, '/file/remove', ['numbers' => $old['.id']], microtime(true) + 15);
                    }
                    $state['routers'][$id] = 'ok';
                    $log[] = $r['name'] . ': ' . $folder . $name . '.backup' . ($made ? ' (' . Lang::T('already saved') . ')' : '');
                } catch (Throwable $e) {
                    $state['routers'][$id] = (int) ($state['routers'][$id] ?? 0) + 1;
                    $log[] = $r['name'] . ': ' . $e->getMessage();
                } finally {
                    // done with this router either way: drop the connection without the library's goodbye, which can wait for ever
                    method_exists($c, 'abort') ? $c->abort() : null;
                }
                self::saveBackupState($state);
            }
        } catch (Throwable $e) {
            $log[] = Lang::T('Backups stopped') . ': ' . $e->getMessage();
        } finally {
            if ($lock) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
        return $log;
    }

    /** Remember which routers are done today (or how many times each has failed), saved after every router. */
    private static function saveBackupState(array $state)
    {
        global $config;
        $json = json_encode($state);
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'router_backup_state')->find_one() ?: ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'router_backup_state';
        $d->value = $json;
        $d->save();
        $config['router_backup_state'] = $json;
    }
}
