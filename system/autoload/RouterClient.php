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
        $limit = null == $timeout ? (int) ini_get('default_socket_timeout') : (int) $timeout;
        $this->readDeadline(microtime(true) + max(1, $limit));
        try {
            if (!static::login($this->com, $username, $password, $limit)) {
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
        try {
            $this->com->getTransmitter()->setDeadline($deadline);
        } catch (Throwable $e) {
        }
    }

    public function close()
    {
        return $this->aborted ? true : parent::close();
    }
}
