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
            if ($_app_stage == 'Demo') {
                return [null, Lang::T('Not available in Demo mode')];
            }
            [$host, $port] = RouterAddress::split($ip);
            return [new RouterClient($host, $user, $pass, $port, false, $timeout), null];
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
     * Send one request and wait for its answer until $deadline (a microtime). Finished means the closing reply (!done, or a
     * fatal one) arrived; otherwise the connection is dropped and an exception (code 408) says the router did not answer.
     */
    public static function send($c, $req, $deadline)
    {
        static $n = 0;
        $tag = 'nb' . (++$n);
        $req->setTag($tag);
        $c->sendAsync($req);
        $left = $deadline - microtime(true);
        if ($left > 0) {
            // every read inside the reply (not only the wait for it to start) ends by the deadline
            if (method_exists($c, 'readDeadline')) {
                $c->readDeadline($deadline);
            }
            try {
                $c->loop((int) floor($left), (int) (($left - floor($left)) * 1000000));
            } catch (\PEAR2\Net\Transmitter\Exception $e) {
                // a read that ran out of time mid-reply: the reply is incomplete, handled as not answered below
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
        method_exists($c, 'abort') ? $c->abort() : $c->close();
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
            throw new Exception($err);
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
            throw new Exception($err);
        }
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
        try {
            if (($config['router_backup'] ?? '0') !== '1') {
                return [];
            }
            $today = date('Y-m-d');
            $state = json_decode((string) ($config['router_backup_state'] ?? ''), true);
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
            // one run at a time: a second cron started meanwhile leaves the work to the one already doing it
            $lock = @fopen($CACHE_PATH . DIRECTORY_SEPARATOR . 'router_backup.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
                return [];
            }
            $keep = max(1, min(30, (int) ($config['router_backup_keep'] ?? 7)));
            $name = 'phpnuxbill-auto-' . date('Ymd');
            $autoFiles = function ($c) {
                $files = self::rows($c, '/file/print', ['.id', 'name'], microtime(true) + 15);
                $auto = array_values(array_filter($files, function ($f) {
                    return preg_match('/(^|\/)phpnuxbill-auto-\d{8}\.backup$/', (string) $f['name']);
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
                    $auto = $autoFiles($c);
                    $made = false;
                    foreach ($auto as $f) {
                        $made = $made || basename($f['name']) === $name . '.backup';
                    }
                    if (!$made) {
                        self::run($c, '/system/backup/save', ['name' => $name], microtime(true) + 60);
                        $auto = $autoFiles($c);
                    }
                    foreach (array_slice($auto, $keep) as $old) {
                        self::run($c, '/file/remove', ['numbers' => $old['.id']], microtime(true) + 15);
                    }
                    $state['routers'][$id] = 'ok';
                    $log[] = $r['name'] . ': ' . $name . '.backup' . ($made ? ' (' . Lang::T('already saved') . ')' : '');
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
