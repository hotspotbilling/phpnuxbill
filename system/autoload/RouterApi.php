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
    /**
     * The clock of every deadline here and in RouterClient and the transmitter: monotonic seconds, so a change of the system
     * clock cannot stretch or cut a time budget. Build a deadline as RouterApi::now() + seconds; it is not a calendar time.
     */
    public static function now()
    {
        return \PEAR2\Net\Transmitter\Stream::now();
    }

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
                return [self::newClient($host, $user, $pass, $port, $timeout), null];
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
        global $_app_stage;
        // the guard sits here, where a real connection is made, so no caller can reach a saved router address in Demo mode
        // (the stage is written "Demo" in some places and "demo" in others)
        if (strcasecmp(trim((string) $_app_stage), 'demo') === 0) {
            throw new Exception(Lang::T('Not available in Demo mode'));
        }
        $old = ini_get('default_socket_timeout');
        // the stream takes its read time limit from this when it opens, whatever it is set to (even "never")
        ini_set('default_socket_timeout', (string) $readTimeout);
        try {
            return self::newClient($host, $user, $pass, $port, $connectTimeout)->limitSync($readTimeout);
        } finally {
            ini_set('default_socket_timeout', (string) $old);
        }
    }

    /**
     * The client for a host and port (as RouterAddress::split() gives them). The api-ssl port (8729, however it is written:
     * "08729" too) is spoken over TLS, with the certificate checked. A context of our own is always passed: given none, the
     * library switches PHP's default context to unverified anonymous ciphers for everything in this request.
     */
    private static function newClient($host, $user, $pass, $port, $timeout)
    {
        if ($port !== null && (int) $port === 8729) {
            $tls = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
            return new RouterClient($host, $user, $pass, 8729, false, $timeout, \PEAR2\Net\Transmitter\NetworkStream::CRYPTO_TLS, $tls);
        }
        return new RouterClient($host, $user, $pass, $port, false, $timeout);
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
        if ($deadline - self::now() <= 0) {
            throw new Exception(Lang::T('The router did not answer in time'), 408);
        }
        // a deadline already set on the client (this request is nested in another one's work) is never extended: the request ends
        // by whichever deadline comes first, and the outer one is put back afterwards
        $prior = method_exists($c, 'currentDeadline') ? $c->currentDeadline() : null;
        if ($prior !== null) {
            $deadline = min($deadline, $prior);
            if ($deadline - self::now() <= 0) {
                throw new Exception(Lang::T('The router did not answer in time'), 408);
            }
        }
        // a tag already in use on this client (a /listen of the caller's own, say) is skipped: its replies stay with its callback
        do {
            $tag = 'nb' . (++$n);
        } while (method_exists($c, 'isRequestActive') && $c->isRequestActive($tag));
        $req = clone $req; // the tag goes on a copy: the caller's request keeps its own tag, sent or not
        $req->setTag($tag);
        self::transmit($c, $req, $deadline);
        $recvErr = null;
        $recvTimedOut = null;
        $got = [];
        $done = false;
        // whether these (newly collected) replies hold the closing one: only the new ones are looked at, not everything so far
        $closes = function (array $replies) {
            foreach ($replies as $r) {
                if (in_array($r->getType(), [Response::TYPE_FINAL, Response::TYPE_FATAL], true)) {
                    return true;
                }
            }
            return false;
        };
        if ($deadline - self::now() > 0) {
            // every read inside the reply (not only the wait for it to start) ends by the deadline
            if (method_exists($c, 'readDeadline')) {
                $c->readDeadline($deadline);
            }
            try {
                // one reply at a time, and this request's replies collected after each: the library's loop() runs until every
                // pending request is done (a /listen on the same client never is), so this wait ends as soon as this request is
                // done (or the deadline passes), and replies to other requests stay buffered (or go to their callbacks)
                $next = \Closure::bind(function ($s, $us) {
                    try {
                        $this->dispatchNextResponse($s, $us);
                    } catch (\PEAR2\Net\RouterOS\SocketException $e) {
                        if ($e->getCode() !== \PEAR2\Net\RouterOS\SocketException::CODE_NO_DATA) {
                            throw $e; // (no reply within the time is not an error here: the deadline is checked below)
                        }
                    }
                }, $c, \PEAR2\Net\RouterOS\Client::class);
                while (($left = $deadline - self::now()) > 0) {
                    $next((int) floor($left), (int) (($left - floor($left)) * 1000000));
                    // (isRequestActive() is no test of being finished: it stays true while a finished answer waits to be
                    // collected; once the closing reply is collected the request is gone, so nothing is asked for after it)
                    if ($c->isRequestActive($tag)) {
                        $new = $c->extractNewResponses($tag)->toArray();
                        array_push($got, ...$new);
                        $done = $done || $closes($new);
                    }
                    if ($done || $c->getPendingRequestsCount() === 0) {
                        break; // done, or nothing is pending any more (the connection ended): no reply can still come
                    }
                }
            } catch (\PEAR2\Net\Transmitter\Exception | \PEAR2\Net\RouterOS\SocketException | \UnexpectedValueException $e) {
                // (a reply the library could not parse is the same: the connection is dropped below, no more is read from it)
                // the reply is incomplete: a read that ran out of time, or the connection closed mid-reply (told apart below)
                $recvErr = $e;
                // told now: putting an outer deadline back below starts the time-out record again
                $recvTimedOut = method_exists($c, 'readTimedOut') ? $c->readTimedOut() : null;
            } catch (\Throwable $cbErr) {
                // anything else (a callback of another request threw): this request is retired before the error goes on. Its closing
                // reply is collected if it came; otherwise replies could still arrive for a tag nobody reads, so the connection is
                // dropped (abort() keeps the completed replies of other requests)
                try {
                    if ($c->isRequestActive($tag)) {
                        array_push($got, ...$c->extractNewResponses($tag)->toArray());
                    }
                    if (!$closes($got)) {
                        method_exists($c, 'abort') ? $c->abort() : $c->close();
                    }
                } catch (\Throwable $ignored) {
                }
                throw $cbErr;
            } finally {
                if (method_exists($c, 'readDeadline')) {
                    $c->readDeadline($prior); // (null for a top-level request: the ordinary time limit)
                }
            }
        }
        if (!$done && $c->isRequestActive($tag)) {
            $new = $c->extractNewResponses($tag)->toArray();
            array_push($got, ...$new);
            $done = $closes($new);
        }
        if ($done) {
            $res = new \PEAR2\Net\RouterOS\ResponseCollection($got);
            // at the first reply, so a caller's $res->getProperty() (which reads the current reply) sees it
            $res->rewind();
            return $res;
        }
        // the router ended the connection itself (a !fatal reply, which belongs to no request): a broken connection, not a
        // router that went quiet
        $fatal = method_exists($c, 'fatalReason') ? $c->fatalReason() : null;
        method_exists($c, 'abort') ? $c->abort() : $c->close();
        if ($fatal !== null) {
            throw new Exception(Lang::T('The router closed the connection') . ($fatal !== 'closed' ? ': ' . mb_substr($fatal, 0, 120) : ''), 503);
        }
        // the connection ended before the reply was complete, with time still left: not a router that went quiet
        if ($recvErr !== null && $recvTimedOut === false) {
            $why = trim(preg_replace('/\s+/', ' ', strip_tags($recvErr->getMessage())));
            throw new Exception(Lang::T('The router closed the connection') . ($why !== '' ? ': ' . mb_substr($why, 0, 120) : ''), 503);
        }
        throw new Exception(Lang::T('The router did not answer in time'), 408);
    }

    /**
     * Send a request by the deadline. The library writes with no limit of its own (a router that stops taking data would hold the
     * request for ever), so the wait for the router to take data and each write are held to the time left: a request that cannot
     * go out in time is not sent, and one cut off part way drops the connection (code 408, or 503 when the connection closed).
     */
    private static function transmit($c, $req, $deadline)
    {
        $t = self::transmitter($c);
        if ($t === null || !method_exists($c, 'readDeadline')) {
            // a client without the deadline support would send and then wait for the reply with no limit: not used
            throw new Exception(Lang::T('The router connection cannot be time-limited, so nothing was sent'), 500);
        }
        $fail = function ($e = null) use ($c, $t, $deadline) {
            // out of time: the deadline passed, or a write ran past the socket's own time limit (which the deadline narrowed)
            $timedOut = self::now() >= $deadline || $t->timedOut() || \Closure::bind(function () {
                return $this->readTimedOut();
            }, $t, \PEAR2\Net\Transmitter\Stream::class)();
            $c->readDeadline(null);
            method_exists($c, 'abort') ? $c->abort() : $c->close();
            if ($timedOut) {
                return new Exception(Lang::T('The router did not answer in time'), 408);
            }
            $why = $e ? trim(preg_replace('/\s+/', ' ', strip_tags($e->getMessage()))) : '';
            return new Exception(Lang::T('The router closed the connection') . ($why !== '' ? ': ' . mb_substr($why, 0, 120) : ''), 503);
        };
        // the deadline set before anything is written: the socket's time limit is narrowed to the time left (and put back when
        // the deadline is cleared), so a write that stalls ends by it too
        $prior = $c->currentDeadline();
        $c->readDeadline($deadline);
        try {
            $left = $deadline - self::now();
            $narrowed = $left > 0 && \Closure::bind(function () {
                return $this->limitToDeadline();
            }, $t, \PEAR2\Net\Transmitter\Stream::class)();
            if (!$narrowed || !$t->isAcceptingData((int) floor($left), (int) (($left - floor($left)) * 1000000))) {
                throw $fail();
            }
            try {
                $c->sendAsync($req);
            } catch (\PEAR2\Net\Transmitter\Exception | \PEAR2\Net\RouterOS\SocketException $e) {
                throw $fail($e);
            }
        } finally {
            $c->readDeadline($prior);
        }
    }

    /** The connection's transmitter (the library keeps it out of reach), or null for a client that is not a RouterClient */
    private static function transmitter($c)
    {
        if (!($c instanceof RouterClient)) {
            return null;
        }
        try {
            $com = \Closure::bind(function () {
                return $this->com;
            }, $c, \PEAR2\Net\RouterOS\Client::class)();
            $t = $com ? $com->getTransmitter() : null;
            return $t instanceof \PEAR2\Net\Transmitter\Stream ? $t : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /** The first error the router sent back for a request (no permission, unknown command), or null. */
    public static function error($res)
    {
        $err = null;
        foreach ($res as $r) {
            if ($r->getType() === Response::TYPE_ERROR) {
                $err = (string) self::text($r->getProperty('message')) ?: Lang::T('The router refused the command');
                break;
            }
        }
        // the loop leaves the collection's pointer past its end, and a later $res->getProperty() (which reads the current
        // reply) would fail: put back at the first reply, where it was
        if ($res instanceof \PEAR2\Net\RouterOS\ResponseCollection) {
            $res->rewind();
        }
        return $err;
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
     * Whether the backup password may be sent over this connection: null when it may, otherwise the reason it may not. The
     * /system/backup/save command carries the password as typed, so by default it goes only over API-SSL with the router's
     * certificate checked. Plain API is allowed only when the operator has said so (router_backup_plain_ok = 1) and then
     * still only to a private address (the same network, or a VPN tunnel), checked on the open connection itself (the address
     * it really reached, not the saved name): a private address alone proves nothing about who else is on that network.
     * Encrypting the backup file protects the file, not the way there.
     */
    public static function backupTransportError($c)
    {
        global $config;
        $t = self::transmitter($c);
        if ($t === null) {
            return Lang::T('The connection to the router could not be checked, so the backup password was not sent.');
        }
        try {
            $stream = \Closure::bind(function () {
                return $this->stream;
            }, $t, \PEAR2\Net\Transmitter\Stream::class)();
            if ($t instanceof \PEAR2\Net\Transmitter\NetworkStream && $t->getCrypto() !== \PEAR2\Net\Transmitter\NetworkStream::CRYPTO_OFF) {
                // encrypted: only with the certificate and its name checked (PHP checks both unless told not to)
                $ssl = is_resource($stream) ? (stream_context_get_options($stream)['ssl'] ?? []) : null;
                if (is_array($ssl) && ($ssl['verify_peer'] ?? true) !== false && ($ssl['verify_peer_name'] ?? true) !== false
                    && empty($ssl['allow_self_signed'])) {
                    return null;
                }
                return Lang::T('The backup was not made: the API-SSL connection to this router does not check its certificate, so the backup password was not sent.');
            }
            $peer = is_resource($stream) ? (string) @stream_socket_get_name($stream, true) : '';
        } catch (Throwable $e) {
            $peer = '';
        }
        if (($config['router_backup_plain_ok'] ?? '0') !== '1') {
            return Lang::T('The backup was not made: the connection to this router is plain API (not encrypted), so the backup password was not sent. Use API-SSL (port 8729) with a certificate this server trusts, or, if the router is reached only over a network you trust (LAN or VPN), turn on Allow backups over plain API in Network > Routers > Settings.');
        }
        // "1.2.3.4:8728", "[::1]:8728" or "::1:8728": the port is after the last colon
        $ip = trim(($p = strrpos($peer, ':')) !== false ? substr($peer, 0, $p) : $peer, '[]');
        if (stripos($ip, '::ffff:') === 0 && filter_var(substr($ip, 7), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = substr($ip, 7); // an IPv4 address written as IPv6: judged as the IPv4 address it is
        }
        $flags = defined('FILTER_FLAG_GLOBAL_RANGE') ? FILTER_FLAG_GLOBAL_RANGE : (FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false && filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return null; // a private, loopback, link-local or carrier (100.64/10) address: not across the internet
        }
        return Lang::T('The backup was not made: the connection to this router is not encrypted and crosses the internet, so the backup password would be readable on the way. Use API-SSL (port 8729) or reach the router over a VPN or private address.');
    }

    /** The router's row and saved address as one key ("id@host:port"), so progress kept for it ends when it is moved elsewhere */
    private static function backupKey($r)
    {
        [$host, $port] = RouterAddress::split((string) $r['ip_address']);
        $host = strtolower(trim($host));
        if (($bin = @inet_pton($host)) !== false) {
            $host = inet_ntop($bin); // one spelling for each IP address (IPv6 can be written many ways)
        }
        return (string) $r['id'] . '@' . (strpos($host, ':') !== false ? '[' . $host . ']' : $host) . ':' . ($port !== null ? (int) $port : 8728);
    }

    /**
     * Nightly backups, from the cron: once a day, each enabled router saves "phpnuxbill-auto-<date>.backup" on itself, and only
     * the newest few of those are kept (older ones are removed from the router). Only files this code confirmed it saved (kept
     * per router in the state's "made" list, with the times and size the router listed for each) are ever removed, and only
     * while the file is still that one: backups made by hand, nightly-named files saved before that list existed and files
     * replaced since are left alone (they are counted in the output so they can be removed by hand). Today's confirmed backup
     * always keeps one of the kept places.
     * A router that fails, or whose connection is not protected, is tried again on the next runs that day (up to 3 times); one
     * that is done is not touched again. Progress is kept per router row and address, so a router moved to another address
     * starts again. A save counts as made only once the router confirmed it and the file has something in it; that is recorded
     * before older backups are removed, so a retry after a failed clean-up only cleans up, as long as today's file is still
     * listed as saved with something in it (otherwise it is saved again). A save never writes over a file (RouterOS replaces a file of the same name and has no way to refuse): each attempt saves to a name of its own, today's name with a random suffix (-r and 8 hex digits). The password is sent only over a protected connection (see backupTransportError()).
     * Never throws; returns lines for the cron's output.
     */
    public static function nightlyBackups()
    {
        global $config, $CACHE_PATH;
        $log = [];
        $lock = null;
        // a time budget of its own, not what the monitoring pass before it left over (each router gets its own again below)
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
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
            // which files nightly backups made on each router (under backupKey()): kept from day to day, unlike the progress
            $made = is_array($state['made'] ?? null) ? $state['made'] : [];
            if (!is_array($state) || ($state['date'] ?? '') !== $today || !is_array($state['routers'] ?? null)) {
                $state = ['date' => $today, 'routers' => []];
            }
            $state['made'] = $made;
            // each router's progress, under backupKey(): ['tries' => failures, 'saved' => today's save confirmed, 'ok' => done,
            // 'blocked' => the last try was not sent over an unprotected connection (it counts as a try)]
            $entry = function ($key) use (&$state) {
                $s = $state['routers'][$key] ?? null;
                return is_array($s) ? $s + ['tries' => 0, 'saved' => false, 'ok' => false, 'blocked' => false, 'pending' => '', 'attempt' => '']
                    : ['tries' => 0, 'saved' => false, 'ok' => false, 'blocked' => false, 'pending' => '', 'attempt' => ''];
            };
            $pending = function ($key) use ($entry) {
                $s = $entry($key);
                return !$s['ok'] && (int) $s['tries'] < 3;
            };
            $usable = function ($r) {
                return $r && (int) $r['enabled'] === 1 && trim((string) $r['ip_address']) !== '' && strcasecmp((string) $r['name'], 'radius') !== 0;
            };
            $routers = [];
            foreach (ORM::for_table('tbl_routers')->where('enabled', 1)->find_many() as $r) {
                if (!$usable($r)) {
                    continue;
                }
                // a saved address that cannot be read is that router's problem only: reported, and the others go on
                try {
                    $rkey = self::backupKey($r);
                } catch (Throwable $e) {
                    $log[] = $r['name'] . ': ' . Lang::T('the saved address is not valid, skipped') . ' (' . $e->getMessage() . ')';
                    continue;
                }
                if ($pending($rkey)) {
                    $routers[] = (int) $r['id'];
                }
            }
            if (!$routers) {
                return [];
            }
            $keep = max(1, min(30, (int) ($config['router_backup_keep'] ?? 7)));
            $name = 'phpnuxbill-auto-' . str_replace('-', '', $today); // the same day as the progress kept above, across midnight too
            $autoFiles = function ($c, &$folder = null) {
                // the times and size are what tells a file the nightly backup made from another later given the same name
                $files = self::rows($c, '/file/print', ['.id', 'name', 'type', 'size', 'creation-time', 'last-modified'], self::now() + 15);
                $folder = self::backupFolder($files);
                // only in the folder nightly backups are saved to on this router (flash/ or the root folder): a file of the same
                // name anywhere else (a USB disk, a folder of copies) was not made here and is never removed
                $own = '/^' . preg_quote($folder, '/') . 'phpnuxbill-auto-\d{8}(?:-\d{1,2}|-r[0-9a-f]{8})?\.backup$/';
                $auto = array_values(array_filter($files, function ($f) use ($own) {
                    return preg_match($own, (string) $f['name']);
                }));
                usort($auto, function ($a, $b) {
                    return strcmp(self::backupDate($b['name']), self::backupDate($a['name'])); // newest first, by the name
                });
                return $auto;
            };
            foreach ($routers as $rid) {
                if (function_exists('set_time_limit')) {
                    @set_time_limit(120); // each router gets its own time, so a slow one does not stop the rest
                }
                // the row read again right before its turn: an earlier router can take minutes, and meanwhile this one may have been
                // removed, disabled or given another address or login. A router no longer to be backed up is skipped (no try used)
                $r = ORM::for_table('tbl_routers')->find_one($rid);
                if (!$usable($r)) {
                    continue;
                }
                try {
                    $key = self::backupKey($r);
                } catch (Throwable $e) {
                    $log[] = $r['name'] . ': ' . Lang::T('the saved address is not valid, skipped') . ' (' . $e->getMessage() . ')';
                    continue;
                }
                if (!$pending($key)) {
                    continue; // moved to an address already done (or out of tries) today
                }
                $s = $entry($key);
                [$c, $err] = self::connect($r['ip_address'], $r['username'], $r['password'], 8);
                if (!$c) {
                    $s['tries']++;
                    $state['routers'][$key] = $s;
                    $log[] = $r['name'] . ': ' . $err;
                    self::saveBackupState($state);
                    continue;
                }
                try {
                    $folder = '';
                    $auto = $autoFiles($c, $folder);
                    // what nightly backups made on this router: each file with what identified it when it was saved (a name
                    // recorded before identities were kept has none, so it never matches and is never removed)
                    $records = [];
                    $pendingPath = is_string($s['pending'] ?? null) ? $s['pending'] : '';
                    foreach ((is_array($state['made'][$key] ?? null) ? $state['made'][$key] : []) as $m) {
                        $m = is_array($m) ? $m : ['name' => (string) $m];
                        if (isset($m['name'])) {
                            $records[(string) $m['name']] = $m;
                        }
                    }
                    // a listed file is ours only while it is still the very file recorded (same name, times and size): one
                    // removed and replaced by another of the same name is not
                    $isOurs = function ($f) use (&$records) {
                        $m = $records[(string) $f['name']] ?? null;
                        $now = self::fileIdentity($f);
                        return is_array($m) && $now !== null && is_string($m['id'] ?? null) && $m['id'] === $now;
                    };
                    $find = function ($path) use (&$auto) {
                        foreach ($auto as $f) {
                            if ((string) $f['name'] === $path) {
                                return $f;
                            }
                        }
                        return null;
                    };
                    // confirmed by an earlier run today, whose clean-up then failed: trusted only while that very file is still
                    // listed with something in it (removed, emptied or replaced since, it is saved again). ('saved' was true
                    // before it held the file's name: then today's plain name)
                    $savedPath = $s['saved'] === true ? $folder . $name . '.backup' : (is_string($s['saved']) ? $s['saved'] : '');
                    $f = $savedPath !== '' ? $find($savedPath) : null;
                    $made = $f !== null && $isOurs($f) && self::hasData($f['size'] ?? null);
                    // A save the router acknowledged whose file could not be listed afterwards (the listing failed): its name was kept
                    // before listing. Used only once that very file is listed, with something in it and an identity; otherwise
                    // saved again (an unacknowledged save is never taken as made)
                    if (!$made && $pendingPath !== '') {
                        // The file's identity was not recorded with the save, so a file at that path now cannot be proven to be the one
                        // saved (a same-named file, even of the same size, may be someone else's). It is not adopted as ours: it stays
                        // unowned (never removed), and a new backup is saved and confirmed before anything is pruned.
                        $s['pending'] = '';
                    }
                    if (!$made) {
                        $s['saved'] = false;
                        $savedPath = '';
                        // a save replaces a file of the same name, and RouterOS has no way to refuse that, so each attempt gets a name of
                        // its own (today's name with a random suffix, still a nightly name so it is kept and removed like the
                        // others): a file made under it by anyone else meanwhile cannot be written over
                        $base = $name . '-r' . bin2hex(random_bytes(4));
                        // checked on this very connection, right before the password would go out on it
                        if (($why = self::backupTransportError($c)) !== null) {
                            // counts as a try: a later run (up to the limit) looks again, in case the connection or the
                            // operator's settings changed
                            $s['blocked'] = true;
                            $s['tries']++;
                            $state['routers'][$key] = $s;
                            $log[] = $r['name'] . ': ' . $why;
                            self::saveBackupState($state);
                            continue; // (the connection is dropped in finally)
                        }
                        $s['blocked'] = false;
                        // A save that fails or times out may still have made the file. The name tried is kept before the command goes
                        // out, apart from confirmed backups: it is reported by a later run and never counts as made or removed.
                        if (($s['attempt'] ?? '') !== '') {
                            $log[] = $r['name'] . ': ' . Lang::T('an earlier backup attempt was not confirmed; the file may exist on the router') . ' (' . $s['attempt'] . ')';
                        }
                        $s['attempt'] = $folder . $base . '.backup';
                        $state['routers'][$key] = $s;
                        self::saveBackupState($state);
                        // encrypted with the backup password (never written to the log or the output)
                        self::run($c, '/system/backup/save', ['name' => $folder . $base, 'password' => $password], self::now() + 60);
                        $savedPath = $folder . $base . '.backup';
                        // the router acknowledged the save: its name is kept before the files are listed again, so a failed listing
                        // does not lose it (the next try confirms that file instead of saving another)
                        $s['pending'] = $savedPath;
                        $state['routers'][$key] = $s;
                        self::saveBackupState($state);
                        $auto = $autoFiles($c);
                        $f = $find($savedPath);
                        // the router said the save is done: the file must be there, with something in it, before older ones go
                        if ($f === null || !self::hasData($f['size'] ?? null)) {
                            throw new Exception(Lang::T('The backup file was not saved on the router'));
                        }
                        // recorded before the clean-up, so a retry after a failed clean-up does not save again; and the file
                        // goes on this router's list of files nightly backups made (with what identifies it), the only ones
                        // ever removed
                        // identity first: without one the file is not recorded as saved (pending stays), so it never justifies pruning
                        $ident = self::fileIdentity($f);
                        if ($ident === null) {
                            throw new Exception(Lang::T('The backup file was not saved on the router')); // (no file ID: it could not be told apart later)
                        }
                        $s['saved'] = $savedPath;
                        $s['pending'] = '';
                        $s['attempt'] = '';
                        $state['routers'][$key] = $s;
                        $records[$savedPath] = ['name' => $savedPath, 'id' => $ident];
                        $state['made'][$key] = array_values($records);
                        self::saveBackupState($state);
                    }
                    // removal candidates: only files still identified as the ones recorded (newest first, by the name); a file of
                    // a nightly-looking name that is not (made by hand, before the list existed, or replaced since) is left
                    // alone. The file confirmed today always keeps its place, and the other places go to the newest of the rest
                    $ours = array_values(array_filter($auto, $isOurs));
                    $older = array_values(array_filter($ours, function ($f) use ($savedPath) {
                        return (string) $f['name'] !== $savedPath;
                    }));
                    $foreign = count($auto) - count($ours);
                    foreach (array_slice($older, $keep - 1) as $old) {
                        // listed again right before the removal: a file written over the listed one since (same name, other times,
                        // size or ID) is not ours any more and is left alone; an unchanged one is removed by its current ID
                        $current = null;
                        $todays = null;
                        $fresh = $autoFiles($c);
                        foreach ($fresh as $f) {
                            if ((string) $f['name'] === (string) $old['name']) {
                                $current = $f;
                            }
                            if ((string) $f['name'] === $savedPath) {
                                $todays = $f;
                            }
                        }
                        // nothing older goes unless today's backup is still, in this fresh listing, the very file recorded and not empty
                        if ($todays === null || !$isOurs($todays) || !self::hasData($todays['size'] ?? null)) {
                            throw new Exception(Lang::T('The backup file was not saved on the router'));
                        }
                        // the places kept are worked out again from this fresh listing: files that came or went since the first one change
                        // which are the newest, and a file that is among them now is not removed
                        $freshOlder = array_values(array_filter(array_filter($fresh, $isOurs), function ($f) use ($savedPath) {
                            return (string) $f['name'] !== $savedPath;
                        }));
                        if (in_array((string) $old['name'], array_map('strval', array_column(array_slice($freshOlder, 0, $keep - 1), 'name')), true)) {
                            continue;
                        }
                        if ($current === null) {
                            unset($records[(string) $old['name']]); // already gone
                            $state['made'][$key] = array_values($records);
                            continue;
                        }
                        if (!$isOurs($current)) {
                            continue;
                        }
                        self::run($c, '/file/remove', ['numbers' => $current['.id']], self::now() + 15);
                        unset($records[(string) $old['name']]);
                        $state['made'][$key] = array_values($records);
                        self::saveBackupState($state);
                    }
                    // records of files no longer on the router as they were saved (removed or replaced by hand) leave the list,
                    // so it stays as short as what is kept
                    $kept = [];
                    foreach ($ours as $f) {
                        if (isset($records[(string) $f['name']])) {
                            $kept[] = $records[(string) $f['name']];
                        }
                    }
                    $state['made'][$key] = $kept;
                    $s['ok'] = true;
                    $state['routers'][$key] = $s;
                    $log[] = $r['name'] . ': ' . $savedPath . ($made ? ' (' . Lang::T('already saved') . ')' : '')
                        . ($foreign > 0 ? ' (' . $foreign . ' ' . Lang::T('nightly-named backup files not recorded as made by the nightly backup are kept; remove them by hand if not needed') . ')' : '');
                } catch (Throwable $e) {
                    $s['tries']++; // 'saved' is kept: the next try resumes with the clean-up
                    $state['routers'][$key] = $s;
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

    /** What identifies a file the router lists (its times and size): another file later saved under the same name differs */
    private static function fileIdentity(array $f)
    {
        // the router's own ID of the file comes first: with none there is no identity (null), so nothing is recorded as made or
        // removed on it. A record saved before the ID was kept holds the three-part form, which never equals this four-part one
        $rid = trim((string) ($f['.id'] ?? ''));
        if ($rid === '') {
            return null;
        }
        return $rid . '|' . (string) ($f['creation-time'] ?? '') . '|' . (string) ($f['last-modified'] ?? '') . '|' . (string) ($f['size'] ?? '');
    }

    /** A nightly backup file name as a key that sorts by its date, then by its -2 to -9 suffix (yyyymmddnn), or "" */
    private static function backupDate($name)
    {
        if (preg_match('/phpnuxbill-auto-(\d{8})-r([0-9a-f]{8})\.backup$/', (string) $name, $m)) {
            return $m[1] . '99' . $m[2]; // (a per-attempt name: after the plain and -2 to -9 names of its day)
        }
        return preg_match('/phpnuxbill-auto-(\d{8})(?:-(\d{1,2}))?\.backup$/', (string) $name, $m)
            ? $m[1] . sprintf('%02d', isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 1) : '';
    }

    /** Whether a listed size is more than nothing ("0", "0 B" and a missing size are not) */
    private static function hasData($size)
    {
        return $size !== null && preg_match('/^\s*0*[1-9]|^\s*0*\.\d*[1-9]/', (string) $size) === 1;
    }

    /** Remember which routers are done today (or how many times each has failed), saved after every router. */
    private static function saveBackupState(array $state)
    {
        global $config;
        $json = json_encode($state);
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'router_backup_state')->find_one() ?: ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'router_backup_state';
        $d->value = $json;
        if ($d->save() === false) {
            throw new Exception('router backup state not saved'); // never report progress that was not kept
        }
        $config['router_backup_state'] = $json;
    }
}
