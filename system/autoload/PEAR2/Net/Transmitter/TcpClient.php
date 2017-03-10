<?php

/**
 * Wrapper for network stream functionality.
 * 
 * PHP has built in support for various types of network streams, such as HTTP and TCP sockets. One problem that arises with them is the fact that a single fread/fwrite call might not read/write all the data you intended, regardless of whether you're in blocking mode or not. While the PHP manual offers a workaround in the form of a loop with a few variables, using it every single time you want to read/write can be tedious.

This package abstracts this away, so that when you want to get exactly N amount of bytes, you can be sure the upper levels of your app will be dealing with N bytes. Oh, and the functionality is nicely wrapped in an object (but that's just the icing on the cake).
 * 
 * PHP version 5
 * 
 * @category  Net
 * @package   PEAR2_Net_Transmitter
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   1.0.0a5
 * @link      http://pear2.php.net/PEAR2_Net_Transmitter
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Net\Transmitter;

/**
 * Used for managing persistent connections.
 */
use PEAR2\Cache\SHM;

/**
 * Used for matching arbitrary exceptions in
 * {@link TcpClient::createException()} and releasing locks properly.
 */
use Exception as E;

/**
 * A socket transmitter.
 * 
 * This is a convinience wrapper for socket functionality. Used to ensure data
 * integrity.
 * 
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class TcpClient extends NetworkStream
{

    /**
     * @var int The error code of the last error on the socket.
     */
    protected $errorNo = 0;

    /**
     * @var string The error message of the last error on the socket.
     */
    protected $errorStr = null;
    
    /**
     * @var SHM Persistent connection handler. Remains NULL for non-persistent
     *     connections. 
     */
    protected $shmHandler = null;
    
    /**
     * @var array An array with all connections from this PHP request (as keys)
     *     and their lock state (as a value). 
     */
    protected static $lockState = array();
    
    protected static $cryptoScheme = array(
        parent::CRYPTO_OFF => 'tcp',
        parent::CRYPTO_SSL2 => 'sslv2',
        parent::CRYPTO_SSL3 => 'sslv3',
        parent::CRYPTO_SSL => 'ssl',
        parent::CRYPTO_TLS => 'tls'
    );
    
    /**
     * @var string The URI of this connection.
     */
    protected $uri;

    /**
     * Creates a new connection with the specified options.
     * 
     * @param string   $host    Hostname (IP or domain) of the server.
     * @param int      $port    The port on the server.
     * @param bool     $persist Whether or not the connection should be a
     *     persistent one.
     * @param float    $timeout The timeout for the connection.
     * @param string   $key     A string that uniquely identifies the
     *     connection.
     * @param string   $crypto  Encryption setting. Must be one of the
     *     NetworkStream::CRYPTO_* constants. By default, encryption is
     *     disabled. If the setting has an associated scheme for it, it will be
     *     used, and if not, the setting will be adjusted right after the
     *     connection is estabilished.
     * @param resource $context A context for the socket.
     */
    public function __construct(
        $host,
        $port,
        $persist = false,
        $timeout = null,
        $key = '',
        $crypto = parent::CRYPTO_OFF,
        $context = null
    ) {
        $this->streamType = '_CLIENT';

        if (strpos($host, ':') !== false) {
            $host = "[{$host}]";
        }
        $flags = STREAM_CLIENT_CONNECT;
        if ($persist) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }

        $timeout
            = null == $timeout ? ini_get('default_socket_timeout') : $timeout;

        $key = rawurlencode($key);

        if (null === $context) {
            $context = stream_context_get_default();
        } elseif ((!is_resource($context))
            || ('stream-context' !== get_resource_type($context))
        ) {
            throw $this->createException('Invalid context supplied.', 6);
        }
        $hasCryptoScheme = array_key_exists($crypto, static::$cryptoScheme);
        $scheme = $hasCryptoScheme ? static::$cryptoScheme[$crypto] : 'tcp';
        $this->uri = "{$scheme}://{$host}:{$port}/{$key}";
        set_error_handler(array($this, 'handleError'));
        try {
            parent::__construct(
                stream_socket_client(
                    $this->uri,
                    $this->errorNo,
                    $this->errorStr,
                    $timeout,
                    $flags,
                    $context
                )
            );
            restore_error_handler();
        } catch (E $e) {
            restore_error_handler();
            if (0 === $this->errorNo) {
                throw $this->createException(
                    'Failed to initialize socket.',
                    7,
                    $e
                );
            }
            throw $this->createException(
                'Failed to connect with socket.',
                8,
                $e
            );
        }

        if ($hasCryptoScheme) {
            $this->crypto = $crypto;
        } elseif (parent::CRYPTO_OFF !== $crypto) {
            $this->setCrypto($crypto);
        }
        $this->setIsBlocking(parent::CRYPTO_OFF === $crypto);

        if ($persist) {
            $this->shmHandler = SHM::factory(
                __CLASS__ . ' ' . $this->uri . ' '
            );
            self::$lockState[$this->uri] = self::DIRECTION_NONE;
        }
    }

    /**
     * Creates a new exception.
     * 
     * Creates a new exception. Used by the rest of the functions in this class.
     * 
     * @param string                   $message  The exception message.
     * @param int                      $code     The exception code.
     * @param E|null                   $previous Previous exception thrown,
     *     or NULL if there is none.
     * @param int|string|resource|null $fragment The fragment up until the
     *     point of failure.
     *     On failure with sending, this is the number of bytes sent
     *     successfully before the failure.
     *     On failure when receiving, this is a string/stream holding
     *     the contents received successfully before the failure.
     * 
     * @return SocketException The exception to then be thrown.
     */
    protected function createException(
        $message,
        $code = 0,
        E $previous = null,
        $fragment = null
    ) {
        return new SocketException(
            $message,
            $code,
            $previous,
            $fragment,
            $this->errorNo,
            $this->errorStr
        );
    }
    
    /**
     * Locks transmission.
     * 
     * Locks transmission in one or more directions. Useful when dealing with
     * persistent connections. Note that every send/receive call implicitly
     * calls this function and then restores it to the previous state. You only
     * need to call this function if you need to do an uninterrputed sequence of
     * such calls.
     * 
     * @param int  $direction The direction(s) to have locked. Acceptable values
     *     are the DIRECTION_* constants. If a lock for a direction can't be
     *     obtained immediatly, the function will block until one is aquired.
     *     Note that if you specify {@link DIRECTION_ALL}, the sending lock will
     *     be obtained before the receiving one, and if obtaining the receiving
     *     lock afterwards fails, the sending lock will be released too.
     * @param bool $replace   Whether to replace all locks with the specified
     *     ones. Setting this to FALSE will make the function only obtain the
     *     locks which are not already obtained.
     * 
     * @return int|false The previous state or FALSE if the connection is not
     *     persistent or arguments are invalid.
     */
    public function lock($direction = self::DIRECTION_ALL, $replace = false)
    {
        if ($this->persist && is_int($direction)) {
            $old = self::$lockState[$this->uri];

            if ($direction & self::DIRECTION_SEND) {
                if (($old & self::DIRECTION_SEND)
                    || $this->shmHandler->lock(self::DIRECTION_SEND)
                ) {
                    self::$lockState[$this->uri] |= self::DIRECTION_SEND;
                } else {
                    throw new LockException('Unable to obtain sending lock.');
                }
            } elseif ($replace) {
                if (!($old & self::DIRECTION_SEND)
                    || $this->shmHandler->unlock(self::DIRECTION_SEND)
                ) {
                    self::$lockState[$this->uri] &= ~self::DIRECTION_SEND;
                } else {
                    throw new LockException('Unable to release sending lock.');
                }
            }
            
            try {
                if ($direction & self::DIRECTION_RECEIVE) {
                    if (($old & self::DIRECTION_RECEIVE)
                        || $this->shmHandler->lock(self::DIRECTION_RECEIVE)
                    ) {
                        self::$lockState[$this->uri] |= self::DIRECTION_RECEIVE;
                    } else {
                        throw new LockException(
                            'Unable to obtain receiving lock.'
                        );
                    }
                } elseif ($replace) {
                    if (!($old & self::DIRECTION_RECEIVE)
                        || $this->shmHandler->unlock(self::DIRECTION_RECEIVE)
                    ) {
                        self::$lockState[$this->uri]
                            &= ~self::DIRECTION_RECEIVE;
                    } else {
                        throw new LockException(
                            'Unable to release receiving lock.'
                        );
                    }
                }
            } catch (LockException $e) {
                if ($direction & self::DIRECTION_SEND
                    && !($old & self::DIRECTION_SEND)
                ) {
                    $this->shmHandler->unlock(self::DIRECTION_SEND);
                }
                throw $e;
            }
            return $old;
        }
        return false;
    }
    

    /**
     * Sends a string or stream to the server.
     * 
     * Sends a string or stream to the server. If a seekable stream is
     * provided, it will be seeked back to the same position it was passed as,
     * regardless of the $offset parameter.
     * 
     * @param string|resource $contents The string or stream to send.
     * @param int             $offset   The offset from which to start sending.
     *     If a stream is provided, and this is set to NULL, sending will start
     *     from the current stream position.
     * @param int             $length   The maximum length to send. If omitted,
     *     the string/stream will be sent to its end.
     * 
     * @return int The number of bytes sent.
     */
    public function send($contents, $offset = null, $length = null)
    {
        if (false === ($previousState = $this->lock(self::DIRECTION_SEND))
            && $this->persist
        ) {
            throw $this->createException(
                'Unable to obtain sending lock',
                10
            );
        }
        try {
            $result = parent::send($contents, $offset, $length);
        } catch (E $e) {
            $this->lock($previousState, true);
            throw $e;
        }
        $this->lock($previousState, true);
        return $result;
    }

    /**
     * Receives data from the server.
     * 
     * Receives data from the server as a string.
     * 
     * @param int    $length The number of bytes to receive.
     * @param string $what   Descriptive string about what is being received
     *     (used in exception messages).
     * 
     * @return string The received content.
     */
    public function receive($length, $what = 'data')
    {
        if (false === ($previousState = $this->lock(self::DIRECTION_RECEIVE))
            && $this->persist
        ) {
            throw $this->createException(
                'Unable to obtain receiving lock',
                9
            );
        }
        try {
            $result = parent::receive($length, $what);
        } catch (E $e) {
            $this->lock($previousState, true);
            throw $e;
        }
        $this->lock($previousState, true);
        return $result;
    }

    /**
     * Receives data from the server.
     * 
     * Receives data from the server as a stream.
     * 
     * @param int              $length  The number of bytes to receive.
     * @param FilterCollection $filters A collection of filters to apply to the
     *     stream while receiving. Note that the filters will not be present on
     *     the stream after receiving is done.
     * @param string           $what    Descriptive string about what is being
     *     received (used in exception messages).
     * 
     * @return resource The received content.
     */
    public function receiveStream(
        $length,
        FilterCollection $filters = null,
        $what = 'stream data'
    ) {
        if (false === ($previousState = $this->lock(self::DIRECTION_RECEIVE))
            && $this->persist
        ) {
            throw $this->createException(
                'Unable to obtain receiving lock',
                9
            );
        }
        try {
            $result = parent::receiveStream($length, $filters, $what);
        } catch (E $e) {
            $this->lock($previousState, true);
            throw $e;
        }
        $this->lock($previousState, true);
        return $result;
    }
}
