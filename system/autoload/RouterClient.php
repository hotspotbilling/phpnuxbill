<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * The RouterOS client with a way to give up on a router that stopped answering. The library's close() (also called when the
 * client is destroyed) says goodbye with "/quit" and waits for the reply with no time limit, so closing a stalled connection
 * would hang the request it was meant to end. abort() drops the connection at once, and close() then does nothing more.
 */
class RouterClient extends PEAR2\Net\RouterOS\Client
{
    private $aborted = false;
    /** The deadline readDeadline() last set (the stream keeps its own copy out of reach), or null */
    private $deadline = null;
    /** The !fatal reply the router sent before it closed the connection, if it did (it carries no tag, so no request gets it) */
    private $fatal = null;

    /**
     * As the library's constructor, with the login under a deadline: the connection time limit holds for the whole login, so a
     * router that answers it slowly, a little at a time, is given up on as well as one that never answers. A failed login closes
     * the connection. (Persistent connections are not used here; they are left to the library as they are.)
     */
    public function __construct($host, $username, $password = '', $port = 8728, $persist = false, $timeout = null, $crypto = PEAR2\Net\Transmitter\NetworkStream::CRYPTO_OFF, $context = null)
    {
        if ($persist) {
            parent::__construct($host, $username, $password, $port, $persist, $timeout, $crypto, $context);
            return;
        }
        $this->com = new PEAR2\Net\RouterOS\Communicator($host, $port, false, $timeout, $username . '/' . $password, $crypto, $context);
        // the deadline keeps a fractional time limit as it is (0.5 is half a second, not none and not one); the library reads each
        // login reply with whole seconds, so it is given the limit rounded up (never shorter than the deadline, which ends it)
        $limit = null == $timeout ? max(1, (int) ini_get('default_socket_timeout')) : ((float) $timeout > 0 ? (float) $timeout : 1);
        $this->readDeadline(microtime(true) + $limit);
        try {
            if (!static::login($this->com, $username, $password, (int) ceil($limit))) {
                throw new PEAR2\Net\RouterOS\DataFlowException('Invalid username or password supplied.', PEAR2\Net\RouterOS\DataFlowException::CODE_INVALID_CREDENTIALS);
            }
        } catch (Throwable $e) {
            $this->aborted = true; // no goodbye on a connection that never logged in
            try {
                $this->com->close();
            } catch (Throwable $x) {
            }
            throw $e;
        } finally {
            $this->readDeadline(null);
        }
    }

    protected function dispatchNextResponse($sTimeout = 0, $usTimeout = 0)
    {
        $response = parent::dispatchNextResponse($sTimeout, $usTimeout);
        if ($response->getType() === PEAR2\Net\RouterOS\Response::TYPE_FATAL) {
            $this->fatal = $response;
            $this->aborted = true; // the library has closed the connection: no goodbye on it
        }
        return $response;
    }

    /** Why the router closed the connection (its !fatal reply), or null when it did not */
    public function fatalReason()
    {
        if (!$this->fatal) {
            return null;
        }
        $words = array_filter(array_map('trim', array_map('strval', $this->fatal->getUnrecognizedWords())), 'strlen');
        return $words ? implode(' ', $words) : 'closed';
    }

    /** Seconds sendSync() may wait for a command's answer, or null for the library's own wait (which has no end) */
    private $syncLimit = null;

    /** Every sendSync() from now on gives up after $seconds: the connection is dropped and an exception (code 408) thrown */
    public function limitSync($seconds)
    {
        $this->syncLimit = $seconds;
        return $this;
    }

    public function sendSync(PEAR2\Net\RouterOS\Request $request)
    {
        if ($this->syncLimit === null) {
            return parent::sendSync($request);
        }
        return RouterApi::send($this, $request, microtime(true) + $this->syncLimit);
    }

    public function abort()
    {
        $this->aborted = true;
        $this->callbacks = [];
        $this->responseBuffer = [];
        $this->pendingRequestsCount = 0;
        try {
            $this->com->close();
        } catch (Throwable $e) {
        }
    }

    /**
     * Stop reading from the router at $deadline (a microtime), or never again limit it with NULL. The socket's own time limit
     * starts again with every read, so a reply that keeps trickling in could otherwise run past the caller's deadline; with
     * this, each read waits only for the time left, and a read that runs out closes the connection.
     */
    public function readDeadline($deadline)
    {
        $this->deadline = $deadline === null ? null : (float) $deadline;
        try {
            $this->com->getTransmitter()->setDeadline($deadline);
        } catch (Throwable $e) {
        }
    }

    /** The deadline now set with readDeadline() (a microtime), or null when there is none */
    public function currentDeadline()
    {
        return $this->deadline;
    }

    /** Whether the last read under a deadline ran out of time (false when the connection closed before the reply was complete) */
    public function readTimedOut()
    {
        try {
            return $this->com->getTransmitter()->timedOut();
        } catch (Throwable $e) {
            return true;
        }
    }

    public function close()
    {
        if ($this->aborted) {
            return true;
        }
        // the goodbye ("/quit") waits for the router's reply: with no limit set (a client from RouterApi::connect()) a router that
        // does not answer it would hold the caller for ever, so it gets a few seconds and then the connection is dropped
        if ($this->syncLimit === null) {
            $this->syncLimit = 3;
        }
        try {
            return parent::close();
        } catch (Throwable $e) {
            $this->abort();
            return false;
        }
    }
}
