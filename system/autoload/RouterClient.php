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
     * Limit every read from the router to $seconds. The library checks for an answer only before each reply starts; the words
     * after it are read with the socket's own time limit, so the caller sets that to the time left before its deadline.
     */
    public function readTimeout($seconds)
    {
        $seconds = max(0.2, (float) $seconds);
        try {
            $this->com->getTransmitter()->setTimeout((int) floor($seconds), (int) (($seconds - floor($seconds)) * 1000000));
        } catch (Throwable $e) {
        }
    }

    public function close()
    {
        return $this->aborted ? true : parent::close();
    }
}
