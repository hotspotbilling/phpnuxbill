<?php

/**
 * RouterOS API client implementation.
 * 
 * RouterOS is the flag product of the company MikroTik and is a powerful router software. One of its many abilities is to allow control over it via an API. This package provides a client for that API, in turn allowing you to use PHP to control RouterOS hosts.
 * 
 * PHP version 5
 * 
 * @category  Net
 * @package   PEAR2_Net_RouterOS
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   1.0.0b5
 * @link      http://pear2.php.net/PEAR2_Net_RouterOS
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Net\RouterOS;

/**
 * Refers to transmitter direction constants.
 */
use PEAR2\Net\Transmitter\Stream as S;

/**
 * Refers to the cryptography constants.
 */
use PEAR2\Net\Transmitter\NetworkStream as N;

/**
 * Catches arbitrary exceptions at some points.
 */
use Exception as E;

/**
 * A RouterOS client.
 * 
 * Provides functionality for easily communicating with a RouterOS host.
 * 
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Client
{
    /**
     * Used in {@link static::isRequestActive()} to limit search only to
     * requests that have a callback.
     */
    const FILTER_CALLBACK = 1;
    /**
     * Used in {@link static::isRequestActive()} to limit search only to
     * requests that use the buffer.
     */
    const FILTER_BUFFER = 2;
    /**
     * Used in {@link static::isRequestActive()} to indicate no limit in search.
     */
    const FILTER_ALL = 3;

    /**
     * @var Communicator The communicator for this client.
     */
    protected $com;

    /**
     * @var int The number of currently pending requests.
     */
    protected $pendingRequestsCount = 0;

    /**
     * @var array An array of responses that have not yet been extracted or
     *     passed to a callback. Key is the tag of the request, and the value
     *     is an array of associated responses.
     */
    protected $responseBuffer = array();

    /**
     * @var array An array of callbacks to be executed as responses come.
     *     Key is the tag of the request, and the value is the callback for it.
     */
    protected $callbacks = array();
    
    /**
     * @var Registry A registry for the operations. Particularly helpful at
     *     persistent connections.
     */
    protected $registry = null;

    /**
     * @var bool Whether to stream future responses.
     */
    private $_streamingResponses = false;

    /**
     * Creates a new instance of a RouterOS API client.
     * 
     * Creates a new instance of a RouterOS API client with the specified
     * settings.
     * 
     * @param string   $host     Hostname (IP or domain) of the RouterOS server.
     * @param string   $username The RouterOS username.
     * @param string   $password The RouterOS password.
     * @param int|null $port     The port on which the RouterOS server provides
     *     the API service. You can also specify NULL, in which case the port
     *     will automatically be chosen between 8728 and 8729, depending on the
     *     value of $crypto.
     * @param bool     $persist  Whether or not the connection should be a
     *     persistent one.
     * @param float    $timeout  The timeout for the connection.
     * @param string   $crypto   The encryption for this connection. Must be one
     *     of the PEAR2\Net\Transmitter\NetworkStream::CRYPTO_* constants. Off
     *     by default. RouterOS currently supports only TLS, but the setting is
     *     provided in this fashion for forward compatibility's sake. And for
     *     the sake of simplicity, if you specify an encryption, don't specify a
     *     context and your default context uses the value "DEFAULT" for
     *     ciphers, "ADH" will be automatically added to the list of ciphers.
     * @param resource $context  A context for the socket.
     * 
     * @see sendSync()
     * @see sendAsync()
     */
    public function __construct(
        $host,
        $username,
        $password = '',
        $port = 8728,
        $persist = false,
        $timeout = null,
        $crypto = N::CRYPTO_OFF,
        $context = null
    ) {
        $this->com = new Communicator(
            $host,
            $port,
            $persist,
            $timeout,
            $username . '/' . $password,
            $crypto,
            $context
        );
        $timeout = null == $timeout
            ? ini_get('default_socket_timeout')
            : (int) $timeout;
        //Login the user if necessary
        if ((!$persist
            || !($old = $this->com->getTransmitter()->lock(S::DIRECTION_ALL)))
            && $this->com->getTransmitter()->isFresh()
        ) {
            if (!static::login($this->com, $username, $password, $timeout)) {
                $this->com->close();
                throw new DataFlowException(
                    'Invalid username or password supplied.',
                    DataFlowException::CODE_INVALID_CREDENTIALS
                );
            }
        }
        
        if (isset($old)) {
            $this->com->getTransmitter()->lock($old, true);
        }
        
        if ($persist) {
            $this->registry = new Registry("{$host}:{$port}/{$username}");
        }
    }
    
    /**
     * A shorthand gateway.
     * 
     * This is a magic PHP method that allows you to call the object as a
     * function. Depending on the argument given, one of the other functions in
     * the class is invoked and its returned value is returned by this function.
     * 
     * @param mixed $arg Value can be either a {@link Request} to send, which
     *     would be sent asynchoniously if it has a tag, and synchroniously if
     *     not, a number to loop with or NULL to complete all pending requests.
     *     Any other value is converted to string and treated as the tag of a
     *     request to complete.
     * 
     * @return mixed Whatever the long form function would have returned.
     */
    public function __invoke($arg = null)
    {
        if (is_int($arg) || is_double($arg)) {
            return $this->loop($arg);
        } elseif ($arg instanceof Request) {
            return '' == $arg->getTag() ? $this->sendSync($arg)
                : $this->sendAsync($arg);
        } elseif (null === $arg) {
            return $this->completeRequest();
        }
        return $this->completeRequest((string) $arg);
    }

    /**
     * Login to a RouterOS connection.
     * 
     * @param Communicator $com      The communicator to attempt to login to.
     * @param string       $username The RouterOS username.
     * @param string       $password The RouterOS password.
     * @param int|null     $timeout  The time to wait for each response. NULL
     *     waits indefinetly.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public static function login(
        Communicator $com,
        $username,
        $password = '',
        $timeout = null
    ) {
        if (null !== ($remoteCharset = $com->getCharset($com::CHARSET_REMOTE))
            && null !== ($localCharset = $com->getCharset($com::CHARSET_LOCAL))
        ) {
            $password = iconv(
                $localCharset,
                $remoteCharset . '//IGNORE//TRANSLIT',
                $password
            );
        }
        $old = null;
        try {
            if ($com->getTransmitter()->isPersistent()) {
                $old = $com->getTransmitter()->lock(S::DIRECTION_ALL);
                $result = self::_login($com, $username, $password, $timeout);
                $com->getTransmitter()->lock($old, true);
                return $result;
            }
            return self::_login($com, $username, $password, $timeout);
        } catch (E $e) {
            if ($com->getTransmitter()->isPersistent() && null !== $old) {
                $com->getTransmitter()->lock($old, true);
            }
            throw ($e instanceof NotSupportedException
            || $e instanceof UnexpectedValueException
            || !$com->getTransmitter()->isDataAwaiting()) ? new SocketException(
                'This is not a compatible RouterOS service',
                SocketException::CODE_SERVICE_INCOMPATIBLE,
                $e
            ) : $e;
        }
    }

    /**
     * Login to a RouterOS connection.
     * 
     * This is the actual login procedure, applied regardless of persistence and
     * charset settings.
     * 
     * @param Communicator $com      The communicator to attempt to login to.
     * @param string       $username The RouterOS username.
     * @param string       $password The RouterOS password. Potentially parsed
     *     already by iconv.
     * @param int|null     $timeout  The time to wait for each response. NULL
     *     waits indefinetly.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    private static function _login(
        Communicator $com,
        $username,
        $password = '',
        $timeout = null
    ) {
        $request = new Request('/login');
        $request->send($com);
        $response = new Response($com, false, $timeout);
        $request->setArgument('name', $username);
        $request->setArgument(
            'response',
            '00' . md5(
                chr(0) . $password
                . pack('H*', $response->getProperty('ret'))
            )
        );
        $request->send($com);
        $response = new Response($com, false, $timeout);
        return $response->getType() === Response::TYPE_FINAL
            && null === $response->getProperty('ret');
    }
    
    /**
     * Sets the charset(s) for this connection.
     * 
     * Sets the charset(s) for this connection. The specified charset(s) will be
     * used for all future requests and responses. When sending,
     * {@link Communicator::CHARSET_LOCAL} is converted to
     * {@link Communicator::CHARSET_REMOTE}, and when receiving,
     * {@link Communicator::CHARSET_REMOTE} is converted to
     * {@link Communicator::CHARSET_LOCAL}. Setting NULL to either charset will
     * disable charset convertion, and data will be both sent and received "as
     * is".
     * 
     * @param mixed $charset     The charset to set. If $charsetType is
     *     {@link Communicator::CHARSET_ALL}, you can supply either a string to
     *     use for all charsets, or an array with the charset types as keys, and
     *     the charsets as values.
     * @param int   $charsetType Which charset to set. Valid values are the
     *     Communicator::CHARSET_* constants. Any other value is treated as
     *     {@link Communicator::CHARSET_ALL}.
     * 
     * @return string|array The old charset. If $charsetType is
     *     {@link Communicator::CHARSET_ALL}, the old values will be returned as
     *     an array with the types as keys, and charsets as values.
     * @see Communicator::setDefaultCharset()
     */
    public function setCharset(
        $charset,
        $charsetType = Communicator::CHARSET_ALL
    ) {
        return $this->com->setCharset($charset, $charsetType);
    }
    
    /**
     * Gets the charset(s) for this connection.
     * 
     * @param int $charsetType Which charset to get. Valid values are the
     *     Communicator::CHARSET_* constants. Any other value is treated as
     *     {@link Communicator::CHARSET_ALL}.
     * 
     * @return string|array The current charset. If $charsetType is
     *     {@link Communicator::CHARSET_ALL}, the current values will be
     *     returned as an array with the types as keys, and charsets as values.
     * @see setCharset()
     */
    public function getCharset($charsetType)
    {
        return $this->com->getCharset($charsetType);
    }

    /**
     * Sends a request and waits for responses.
     * 
     * @param Request  $request  The request to send.
     * @param callback $callback Optional. A function that is to be executed
     *     when new responses for this request are available. The callback takes
     *     two parameters. The {@link Response} object as the first, and the
     *     {@link Client} object as the second one. If the function returns
     *     TRUE, the request is canceled. Note that the callback may be executed
     *     one last time after that with a response that notifies about the
     *     canceling.
     * 
     * @return $this The client object.
     * @see completeRequest()
     * @see loop()
     * @see cancelRequest()
     */
    public function sendAsync(Request $request, $callback = null)
    {
        //Error checking
        $tag = $request->getTag();
        if ('' == $tag) {
            throw new DataFlowException(
                'Asynchonous commands must have a tag.',
                DataFlowException::CODE_TAG_REQUIRED
            );
        }
        if ($this->isRequestActive($tag)) {
            throw new DataFlowException(
                'There must not be multiple active requests sharing a tag.',
                DataFlowException::CODE_TAG_UNIQUE
            );
        }
        if (null !== $callback && !is_callable($callback, true)) {
            throw new UnexpectedValueException(
                'Invalid callback provided.',
                UnexpectedValueException::CODE_CALLBACK_INVALID
            );
        }
        
        $this->send($request);

        if (null === $callback) {
            //Register the request at the buffer
            $this->responseBuffer[$tag] = array();
        } else {
            //Prepare the callback
            $this->callbacks[$tag] = $callback;
        }
        return $this;
    }

    /**
     * Checks if a request is active.
     * 
     * Checks if a request is active. A request is considered active if it's a
     * pending request and/or has responses that are not yet extracted.
     * 
     * @param string $tag    The tag of the request to look for.
     * @param int    $filter One of the FILTER_* consntants. Limits the search
     *     to the specified places.
     * 
     * @return bool TRUE if the request is active, FALSE otherwise.
     * @see getPendingRequestsCount()
     * @see completeRequest()
     */
    public function isRequestActive($tag, $filter = self::FILTER_ALL)
    {
        $result = 0;
        if ($filter & self::FILTER_CALLBACK) {
            $result |= (int) array_key_exists($tag, $this->callbacks);
        }
        if ($filter & self::FILTER_BUFFER) {
            $result |= (int) array_key_exists($tag, $this->responseBuffer);
        }
        return 0 !== $result;
    }

    /**
     * Sends a request and gets the full response.
     * 
     * @param Request $request The request to send.
     * 
     * @return ResponseCollection The received responses as a collection.
     * @see sendAsync()
     * @see close()
     */
    public function sendSync(Request $request)
    {
        $tag = $request->getTag();
        if ('' == $tag) {
            $this->send($request);
        } else {
            $this->sendAsync($request);
        }
        return $this->completeRequest($tag);
    }

    /**
     * Completes a specified request.
     * 
     * Starts an event loop for the RouterOS callbacks and finishes when a
     * specified request is completed.
     * 
     * @param string $tag The tag of the request to complete. Setting NULL
     *     completes all requests.
     * 
     * @return ResponseCollection A collection of {@link Response} objects that
     *     haven't been passed to a callback function or previously extracted
     *     with {@link static::extractNewResponses()}. Returns an empty
     *     collection when $tag is set to NULL (responses can still be
     *     extracted).
     */
    public function completeRequest($tag = null)
    {
        $hasNoTag = '' == $tag;
        $result = $hasNoTag ? array()
            : $this->extractNewResponses($tag)->toArray();
        while ((!$hasNoTag && $this->isRequestActive($tag))
        || ($hasNoTag && 0 !== $this->getPendingRequestsCount())
        ) {
            $newReply = $this->dispatchNextResponse(null);
            if ($newReply->getTag() === $tag) {
                if ($hasNoTag) {
                    $result[] = $newReply;
                }
                if ($newReply->getType() === Response::TYPE_FINAL) {
                    if (!$hasNoTag) {
                        $result = array_merge(
                            $result,
                            $this->isRequestActive($tag)
                            ? $this->extractNewResponses($tag)->toArray()
                            : array()
                        );
                    }
                    break;
                }
            }
        }
        return new ResponseCollection($result);
    }

    /**
     * Extracts responses for a request.
     * 
     * Gets all new responses for a request that haven't been passed to a
     * callback and clears the buffer from them.
     * 
     * @param string $tag The tag of the request to extract new responses for.
     *     Specifying NULL with extract new responses for all requests.
     * 
     * @return ResponseCollection A collection of {@link Response} objects for
     *     the specified request.
     * @see loop()
     */
    public function extractNewResponses($tag = null)
    {
        if (null === $tag) {
            $result = array();
            foreach (array_keys($this->responseBuffer) as $tag) {
                $result = array_merge(
                    $result,
                    $this->extractNewResponses($tag)->toArray()
                );
            }
            return new ResponseCollection($result);
        } elseif ($this->isRequestActive($tag, self::FILTER_CALLBACK)) {
            return new ResponseCollection(array());
        } elseif ($this->isRequestActive($tag, self::FILTER_BUFFER)) {
            $result = $this->responseBuffer[$tag];
            if (!empty($result)) {
                if (end($result)->getType() === Response::TYPE_FINAL) {
                    unset($this->responseBuffer[$tag]);
                } else {
                    $this->responseBuffer[$tag] = array();
                }
            }
            return new ResponseCollection($result);
        } else {
            throw new DataFlowException(
                'No such request, or the request has already finished.',
                DataFlowException::CODE_UNKNOWN_REQUEST
            );
        }
    }

    /**
     * Starts an event loop for the RouterOS callbacks.
     * 
     * Starts an event loop for the RouterOS callbacks and finishes when there
     * are no more pending requests or when a specified timeout has passed
     * (whichever comes first).
     * 
     * @param int $sTimeout  Timeout for the loop. If NULL, there is no time
     *     limit.
     * @param int $usTimeout Microseconds to add to the time limit.
     * 
     * @return bool TRUE when there are any more pending requests, FALSE
     *     otherwise.
     * @see extractNewResponses()
     * @see getPendingRequestsCount()
     */
    public function loop($sTimeout = null, $usTimeout = 0)
    {
        try {
            if (null === $sTimeout) {
                while ($this->getPendingRequestsCount() !== 0) {
                    $this->dispatchNextResponse(null);
                }
            } else {
                list($usStart, $sStart) = explode(' ', microtime());
                while ($this->getPendingRequestsCount() !== 0
                    && ($sTimeout >= 0 || $usTimeout >= 0)
                ) {
                    $this->dispatchNextResponse($sTimeout, $usTimeout);
                    list($usEnd, $sEnd) = explode(' ', microtime());

                    $sTimeout -= $sEnd - $sStart;
                    $usTimeout -= $usEnd - $usStart;
                    if ($usTimeout <= 0) {
                        if ($sTimeout > 0) {
                            $usTimeout = 1000000 + $usTimeout;
                            $sTimeout--;
                        }
                    }

                    $sStart = $sEnd;
                    $usStart = $usEnd;
                }
            }
        } catch (SocketException $e) {
            if ($e->getCode() !== SocketException::CODE_NO_DATA) {
                // @codeCoverageIgnoreStart
                // It's impossible to reliably cause any other SocketException.
                // This line is only here in case the unthinkable happens:
                // The connection terminates just after it was supposedly
                // about to send back some data.
                throw $e;
                // @codeCoverageIgnoreEnd
            }
        }
        return $this->getPendingRequestsCount() !== 0;
    }

    /**
     * Gets the number of pending requests.
     * 
     * @return int The number of pending requests.
     * @see isRequestActive()
     */
    public function getPendingRequestsCount()
    {
        return $this->pendingRequestsCount;
    }

    /**
     * Cancels a request.
     * 
     * Cancels an active request. Using this function in favor of a plain call
     * to the "/cancel" command is highly reccomended, as it also updates the
     * counter of pending requests properly. Note that canceling a request also
     * removes any responses for it that were not previously extracted with
     * {@link static::extractNewResponses()}.
     * 
     * @param string $tag Tag of the request to cancel. Setting NULL will cancel
     *     all requests.
     * 
     * @return $this The client object.
     * @see sendAsync()
     * @see close()
     */
    public function cancelRequest($tag = null)
    {
        $cancelRequest = new Request('/cancel');
        $hasTag = !('' == $tag);
        $hasReg = null !== $this->registry;
        if ($hasReg && !$hasTag) {
            $tags = array_merge(
                array_keys($this->responseBuffer),
                array_keys($this->callbacks)
            );
            $this->registry->setTaglessMode(true);
            foreach ($tags as $t) {
                $cancelRequest->setArgument(
                    'tag',
                    $this->registry->getOwnershipTag() . $t
                );
                $this->sendSync($cancelRequest);
            }
            $this->registry->setTaglessMode(false);
        } else {
            if ($hasTag) {
                if ($this->isRequestActive($tag)) {
                    if ($hasReg) {
                        $this->registry->setTaglessMode(true);
                        $cancelRequest->setArgument(
                            'tag',
                            $this->registry->getOwnershipTag() . $tag
                        );
                    } else {
                        $cancelRequest->setArgument('tag', $tag);
                    }
                } else {
                    throw new DataFlowException(
                        'No such request. Canceling aborted.',
                        DataFlowException::CODE_CANCEL_FAIL
                    );
                }
            }
            $this->sendSync($cancelRequest);
            if ($hasReg) {
                $this->registry->setTaglessMode(false);
            }
        }

        if ($hasTag) {
            if ($this->isRequestActive($tag, self::FILTER_BUFFER)) {
                $this->responseBuffer[$tag] = $this->completeRequest($tag);
            } else {
                $this->completeRequest($tag);
            }
        } else {
            $this->loop();
        }
        return $this;
    }

    /**
     * Sets response streaming setting.
     * 
     * Sets whether future responses are streamed. If responses are streamed,
     * the argument values are returned as streams instead of strings. This is
     * particularly useful if you expect a response that may contain one or more
     * very large words.
     * 
     * @param bool $streamingResponses Whether to stream future responses.
     * 
     * @return bool The previous value of the setting.
     * @see isStreamingResponses()
     */
    public function setStreamingResponses($streamingResponses)
    {
        $oldValue = $this->_streamingResponses;
        $this->_streamingResponses = (bool) $streamingResponses;
        return $oldValue;
    }

    /**
     * Gets response streaming setting.
     * 
     * Gets whether future responses are streamed.
     * 
     * @return bool The value of the setting.
     * @see setStreamingResponses()
     */
    public function isStreamingResponses()
    {
        return $this->_streamingResponses;
    }

    /**
     * Closes the opened connection, even if it is a persistent one.
     * 
     * Closes the opened connection, even if it is a persistent one. Note that
     * {@link static::extractNewResponses()} can still be used to extract
     * responses collected prior to the closing.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function close()
    {
        $result = true;
        /*
         * The check below is done because for some unknown reason
         * (either a PHP or a RouterOS bug) calling "/quit" on an encrypted
         * connection makes one end hang.
         * 
         * Since encrypted connections only appeared in RouterOS 6.1, and
         * the "/quit" call is needed for all <6.0 versions, problems due
         * to its absence should be limited to some earlier 6.* versions
         * on some RouterBOARD devices.
         */
        if ($this->com->getTransmitter()->getCrypto() === N::CRYPTO_OFF) {
            if (null !== $this->registry) {
                $this->registry->setTaglessMode(true);
            }
            try {
                $response = $this->sendSync(new Request('/quit'));
                $result = $response[0]->getType() === Response::TYPE_FATAL;
            } catch (SocketException $e) {
                $result
                    = $e->getCode() === SocketException::CODE_REQUEST_SEND_FAIL;
            } catch (E $e) {
                //Ignore unknown errors.
            }
            if (null !== $this->registry) {
                $this->registry->setTaglessMode(false);
            }
        }
        $result = $result && $this->com->close();
        $this->callbacks = array();
        $this->pendingRequestsCount = 0;
        return $result;
    }
    
    /**
     * Closes the connection, unless it's a persistent one.
     */
    public function __destruct()
    {
        if ($this->com->getTransmitter()->isPersistent()) {
            if (0 !== $this->pendingRequestsCount) {
                $this->cancelRequest();
            }
        } else {
            $this->close();
        }
    }

    /**
     * Sends a request to RouterOS.
     * 
     * @param Request $request The request to send.
     * 
     * @return $this The client object.
     * @see sendSync()
     * @see sendAsync()
     */
    protected function send(Request $request)
    {
        $request->send($this->com, $this->registry);
        $this->pendingRequestsCount++;
        return $this;
    }

    /**
     * Dispatches the next response in queue.
     * 
     * Dispatches the next response in queue, i.e. it executes the associated
     * callback if there is one, or places the response in the response buffer.
     * 
     * @param int $sTimeout  If a response is not immediatly available, wait
     *     this many seconds. If NULL, wait indefinetly.
     * @param int $usTimeout Microseconds to add to the waiting time.
     * 
     * @throws SocketException When there's no response within the time limit.
     * @return Response The dispatched response.
     */
    protected function dispatchNextResponse($sTimeout = 0, $usTimeout = 0)
    {
        $response = new Response(
            $this->com,
            $this->_streamingResponses,
            $sTimeout,
            $usTimeout,
            $this->registry
        );
        if ($response->getType() === Response::TYPE_FATAL) {
            $this->pendingRequestsCount = 0;
            $this->com->close();
            return $response;
        }

        $tag = $response->getTag();
        $isLastForRequest = $response->getType() === Response::TYPE_FINAL;
        if ($isLastForRequest) {
            $this->pendingRequestsCount--;
        }

        if ('' != $tag) {
            if ($this->isRequestActive($tag, self::FILTER_CALLBACK)) {
                if ($this->callbacks[$tag]($response, $this)) {
                    $this->cancelRequest($tag);
                } elseif ($isLastForRequest) {
                    unset($this->callbacks[$tag]);
                }
            } else {
                $this->responseBuffer[$tag][] = $response;
            }
        }
        return $response;
    }
}
