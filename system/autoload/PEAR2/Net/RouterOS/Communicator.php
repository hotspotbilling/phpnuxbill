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
 * Using transmitters.
 */
use PEAR2\Net\Transmitter as T;

/**
 * A RouterOS communicator.
 * 
 * Implementation of the RouterOS API protocol. Unlike the other classes in this
 * package, this class doesn't provide any conviniences beyond the low level
 * implementation details (automatic word length encoding/decoding, charset
 * translation and data integrity), and because of that, its direct usage is
 * strongly discouraged.
 * 
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 * @see      Client
 */
class Communicator
{
    /**
     * Used when getting/setting all (default) charsets.
     */
    const CHARSET_ALL = -1;
    
    /**
     * Used when getting/setting the (default) remote charset.
     * 
     * The remote charset is the charset in which RouterOS stores its data.
     * If you want to keep compatibility with your Winbox, this charset should
     * match the default charset from your Windows' regional settings.
     */
    const CHARSET_REMOTE = 0;
    
    /**
     * Used when getting/setting the (default) local charset.
     * 
     * The local charset is the charset in which the data from RouterOS will be
     * returned as. This charset should match the charset of the place the data
     * will eventually be written to.
     */
    const CHARSET_LOCAL = 1;
    
    /**
     * @var array An array with the default charset types as keys, and the
     *     default charsets as values.
     */
    protected static $defaultCharsets = array(
        self::CHARSET_REMOTE => null,
        self::CHARSET_LOCAL  => null
    );
    
    /**
     * @var array An array with the current charset types as keys, and the
     * current charsets as values.
     */
    protected $charsets = array();

    /**
     * @var T\TcpClient The transmitter for the connection.
     */
    protected $trans;

    /**
     * Creates a new connection with the specified options.
     * 
     * @param string   $host    Hostname (IP or domain) of the RouterOS server.
     * @param int|null $port    The port on which the RouterOS server provides
     *     the API service. You can also specify NULL, in which case the port
     *     will automatically be chosen between 8728 and 8729, depending on the
     *     value of $crypto.
     * @param bool     $persist Whether or not the connection should be a
     *     persistent one.
     * @param float    $timeout The timeout for the connection.
     * @param string   $key     A string that uniquely identifies the
     *     connection.
     * @param string   $crypto  The encryption for this connection. Must be one
     *     of the PEAR2\Net\Transmitter\NetworkStream::CRYPTO_* constants. Off
     *     by default. RouterOS currently supports only TLS, but the setting is
     *     provided in this fashion for forward compatibility's sake. And for
     *     the sake of simplicity, if you specify an encryption, don't specify a
     *     context and your default context uses the value "DEFAULT" for
     *     ciphers, "ADH" will be automatically added to the list of ciphers.
     * @param resource $context A context for the socket.
     * 
     * @see sendWord()
     */
    public function __construct(
        $host,
        $port = 8728,
        $persist = false,
        $timeout = null,
        $key = '',
        $crypto = T\NetworkStream::CRYPTO_OFF,
        $context = null
    ) {
        $isUnencrypted = T\NetworkStream::CRYPTO_OFF === $crypto;
        if (($context === null) && !$isUnencrypted) {
            $context = stream_context_get_default();
            $opts = stream_context_get_options($context);
            if (!isset($opts['ssl']['ciphers'])
                || 'DEFAULT' === $opts['ssl']['ciphers']
            ) {
                stream_context_set_option($context, 'ssl', 'ciphers', 'ADH');
            }
        }
        // @codeCoverageIgnoreStart
        // The $port is customizable in testing.
        if (null === $port) {
            $port = $isUnencrypted ? 8728 : 8729;
        }
        // @codeCoverageIgnoreEnd

        try {
            $this->trans = new T\TcpClient(
                $host,
                $port,
                $persist,
                $timeout,
                $key,
                $crypto,
                $context
            );
        } catch (T\Exception $e) {
            throw new SocketException(
                'Error connecting to RouterOS',
                SocketException::CODE_CONNECTION_FAIL,
                $e
            );
        }
        $this->setCharset(
            self::getDefaultCharset(self::CHARSET_ALL),
            self::CHARSET_ALL
        );
    }
    
    /**
     * A shorthand gateway.
     * 
     * This is a magic PHP method that allows you to call the object as a
     * function. Depending on the argument given, one of the other functions in
     * the class is invoked and its returned value is returned by this function.
     * 
     * @param string $string A string of the word to send, or NULL to get the
     *     next word as a string.
     * 
     * @return int|string If a string is provided, returns the number of bytes
     *     sent, otherwise retuns the next word as a string.
     */
    public function __invoke($string = null)
    {
        return null === $string ? $this->getNextWord()
            : $this->sendWord($string);
    }
    
    /**
     * Checks whether a variable is a seekable stream resource.
     * 
     * @param mixed $var The value to check.
     * 
     * @return bool TRUE if $var is a seekable stream, FALSE otherwise.
     */
    public static function isSeekableStream($var)
    {
        if (T\Stream::isStream($var)) {
            $meta = stream_get_meta_data($var);
            return $meta['seekable'];
        }
        return false;
    }
    
    /**
     * Uses iconv to convert a stream from one charset to another.
     * 
     * @param string   $inCharset  The charset of the stream.
     * @param string   $outCharset The desired resulting charset.
     * @param resource $stream     The stream to convert. The stream is assumed
     *     to be seekable, and is read from its current position to its end,
     *     after which, it is seeked back to its starting position.
     * 
     * @return resource A new stream that uses the $out_charset. The stream is a
     *     subset from the original stream, from its current position to its
     *     end, seeked at its start.
     */
    public static function iconvStream($inCharset, $outCharset, $stream)
    {
        $bytes = 0;
        $result = fopen('php://temp', 'r+b');
        $iconvFilter = stream_filter_append(
            $result,
            'convert.iconv.' . $inCharset . '.' . $outCharset,
            STREAM_FILTER_WRITE
        );
        
        flock($stream, LOCK_SH);
        while (!feof($stream)) {
            $bytes += stream_copy_to_stream($stream, $result, 0xFFFFF);
        }
        fseek($stream, -$bytes, SEEK_CUR);
        flock($stream, LOCK_UN);
        
        stream_filter_remove($iconvFilter);
        rewind($result);
        return $result;
    }
    
    /**
     * Sets the default charset(s) for new connections.
     * 
     * @param mixed $charset     The charset to set. If $charsetType is
     *     {@link self::CHARSET_ALL}, you can supply either a string to use for
     *     all charsets, or an array with the charset types as keys, and the
     *     charsets as values.
     * @param int   $charsetType Which charset to set. Valid values are the
     *     CHARSET_* constants. Any other value is treated as
     *     {@link self::CHARSET_ALL}.
     * 
     * @return string|array The old charset. If $charsetType is
     *     {@link self::CHARSET_ALL}, the old values will be returned as an
     *     array with the types as keys, and charsets as values.
     * @see setCharset()
     */
    public static function setDefaultCharset(
        $charset,
        $charsetType = self::CHARSET_ALL
    ) {
        if (array_key_exists($charsetType, self::$defaultCharsets)) {
             $oldCharset = self::$defaultCharsets[$charsetType];
             self::$defaultCharsets[$charsetType] = $charset;
             return $oldCharset;
        } else {
            $oldCharsets = self::$defaultCharsets;
            self::$defaultCharsets = is_array($charset) ? $charset : array_fill(
                0,
                count(self::$defaultCharsets),
                $charset
            );
            return $oldCharsets;
        }
    }
    
    /**
     * Gets the default charset(s).
     * 
     * @param int $charsetType Which charset to get. Valid values are the
     *     CHARSET_* constants. Any other value is treated as
     *     {@link self::CHARSET_ALL}.
     * 
     * @return string|array The current charset. If $charsetType is
     *     {@link self::CHARSET_ALL}, the current values will be returned as an
     *     array with the types as keys, and charsets as values.
     * @see setDefaultCharset()
     */
    public static function getDefaultCharset($charsetType)
    {
        return array_key_exists($charsetType, self::$defaultCharsets)
            ? self::$defaultCharsets[$charsetType] : self::$defaultCharsets;
    }

    /**
     * Gets the length of a seekable stream.
     * 
     * Gets the length of a seekable stream.
     * 
     * @param resource $stream The stream to check. The stream is assumed to be
     *     seekable.
     * 
     * @return double The number of bytes in the stream between its current
     *     position and its end.
     */
    public static function seekableStreamLength($stream)
    {
        $streamPosition = (double) sprintf('%u', ftell($stream));
        fseek($stream, 0, SEEK_END);
        $streamLength = ((double) sprintf('%u', ftell($stream)))
            - $streamPosition;
        fseek($stream, $streamPosition, SEEK_SET);
        return $streamLength;
    }
    
    /**
     * Sets the charset(s) for this connection.
     * 
     * Sets the charset(s) for this connection. The specified charset(s) will be
     * used for all future words. When sending, {@link self::CHARSET_LOCAL} is
     * converted to {@link self::CHARSET_REMOTE}, and when receiving,
     * {@link self::CHARSET_REMOTE} is converted to {@link self::CHARSET_LOCAL}.
     * Setting  NULL to either charset will disable charset convertion, and data
     * will be both sent and received "as is".
     * 
     * @param mixed $charset     The charset to set. If $charsetType is
     *     {@link self::CHARSET_ALL}, you can supply either a string to use for
     *     all charsets, or an array with the charset types as keys, and the
     *     charsets as values.
     * @param int   $charsetType Which charset to set. Valid values are the
     *     CHARSET_* constants. Any other value is treated as
     *     {@link self::CHARSET_ALL}.
     * 
     * @return string|array The old charset. If $charsetType is
     *     {@link self::CHARSET_ALL}, the old values will be returned as an
     *     array with the types as keys, and charsets as values.
     * @see setDefaultCharset()
     */
    public function setCharset($charset, $charsetType = self::CHARSET_ALL)
    {
        if (array_key_exists($charsetType, $this->charsets)) {
             $oldCharset = $this->charsets[$charsetType];
             $this->charsets[$charsetType] = $charset;
             return $oldCharset;
        } else {
            $oldCharsets = $this->charsets;
            $this->charsets = is_array($charset) ? $charset : array_fill(
                0,
                count($this->charsets),
                $charset
            );
            return $oldCharsets;
        }
    }
    
    /**
     * Gets the charset(s) for this connection.
     * 
     * @param int $charsetType Which charset to get. Valid values are the
     *     CHARSET_* constants. Any other value is treated as
     *     {@link self::CHARSET_ALL}.
     * 
     * @return string|array The current charset. If $charsetType is
     *     {@link self::CHARSET_ALL}, the current values will be returned as an
     *     array with the types as keys, and charsets as values.
     * @see getDefaultCharset()
     * @see setCharset()
     */
    public function getCharset($charsetType)
    {
        return array_key_exists($charsetType, $this->charsets)
            ? $this->charsets[$charsetType] : $this->charsets;
    }

    /**
     * Gets the transmitter for this connection.
     * 
     * @return T\TcpClient The transmitter for this connection.
     */
    public function getTransmitter()
    {
        return $this->trans;
    }

    /**
     * Sends a word.
     * 
     * Sends a word and automatically encodes its length when doing so.
     * 
     * @param string $word The word to send.
     * 
     * @return int The number of bytes sent.
     * @see sendWordFromStream()
     * @see getNextWord()
     */
    public function sendWord($word)
    {
        if (null !== ($remoteCharset = $this->getCharset(self::CHARSET_REMOTE))
            && null !== ($localCharset = $this->getCharset(self::CHARSET_LOCAL))
        ) {
            $word = iconv(
                $localCharset,
                $remoteCharset . '//IGNORE//TRANSLIT',
                $word
            );
        }
        $length = strlen($word);
        static::verifyLengthSupport($length);
        if ($this->trans->isPersistent()) {
            $old = $this->trans->lock(T\Stream::DIRECTION_SEND);
            $bytes = $this->trans->send(self::encodeLength($length) . $word);
            $this->trans->lock($old, true);
            return $bytes;
        }
        return $this->trans->send(self::encodeLength($length) . $word);
    }

    /**
     * Sends a word based on a stream.
     * 
     * Sends a word based on a stream and automatically encodes its length when
     * doing so. The stream is read from its current position to its end, and
     * then returned to its current position. Because of those operations, the
     * supplied stream must be seekable.
     * 
     * @param string   $prefix A string to prepend before the stream contents.
     * @param resource $stream The seekable stream to send.
     * 
     * @return int The number of bytes sent.
     * @see sendWord()
     */
    public function sendWordFromStream($prefix, $stream)
    {
        if (!self::isSeekableStream($stream)) {
            throw new InvalidArgumentException(
                'The stream must be seekable.',
                InvalidArgumentException::CODE_SEEKABLE_REQUIRED
            );
        }
        if (null !== ($remoteCharset = $this->getCharset(self::CHARSET_REMOTE))
            && null !== ($localCharset = $this->getCharset(self::CHARSET_LOCAL))
        ) {
            $prefix = iconv(
                $localCharset,
                $remoteCharset . '//IGNORE//TRANSLIT',
                $prefix
            );
            $stream = self::iconvStream(
                $localCharset,
                $remoteCharset . '//IGNORE//TRANSLIT',
                $stream
            );
        }
        
        flock($stream, LOCK_SH);
        $totalLength = strlen($prefix) + self::seekableStreamLength($stream);
        static::verifyLengthSupport($totalLength);

        $bytes = $this->trans->send(self::encodeLength($totalLength) . $prefix);
        $bytes += $this->trans->send($stream);
        
        flock($stream, LOCK_UN);
        return $bytes;
    }

    /**
     * Verifies that the length is supported.
     * 
     * Verifies if the specified length is supported by the API. Throws a
     * {@link LengthException} if that's not the case. Currently, RouterOS
     * supports words up to 0xFFFFFFFF in length, so that's the only check
     * performed.
     * 
     * @param int $length The length to verify.
     * 
     * @return void
     */
    protected static function verifyLengthSupport($length)
    {
        if ($length > 0xFFFFFFFF) {
            throw new LengthException(
                'Words with length above 0xFFFFFFFF are not supported.',
                LengthException::CODE_UNSUPPORTED,
                null,
                $length
            );
        }
    }

    /**
     * Encodes the length as requred by the RouterOS API.
     * 
     * @param int $length The length to encode.
     * 
     * @return string The encoded length.
     */
    public static function encodeLength($length)
    {
        if ($length < 0) {
            throw new LengthException(
                'Length must not be negative.',
                LengthException::CODE_INVALID,
                null,
                $length
            );
        } elseif ($length < 0x80) {
            return chr($length);
        } elseif ($length < 0x4000) {
            return pack('n', $length |= 0x8000);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            return pack('n', $length >> 8) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            return pack('N', $length |= 0xE0000000);
        } elseif ($length <= 0xFFFFFFFF) {
            return chr(0xF0) . pack('N', $length);
        } elseif ($length <= 0x7FFFFFFFF) {
            $length = 'f' . base_convert($length, 10, 16);
            return chr(hexdec(substr($length, 0, 2))) .
                pack('N', hexdec(substr($length, 2)));
        }
        throw new LengthException(
            'Length must not be above 0x7FFFFFFFF.',
            LengthException::CODE_BEYOND_SHEME,
            null,
            $length
        );
    }

    /**
     * Get the next word in queue as a string.
     * 
     * Get the next word in queue as a string, after automatically decoding its
     * length.
     * 
     * @return string The word.
     * @see close()
     */
    public function getNextWord()
    {
        if ($this->trans->isPersistent()) {
            $old = $this->trans->lock(T\Stream::DIRECTION_RECEIVE);
            $word = $this->trans->receive(
                self::decodeLength($this->trans),
                'word'
            );
            $this->trans->lock($old, true);
        } else {
            $word = $this->trans->receive(
                self::decodeLength($this->trans),
                'word'
            );
        }
        
        if (null !== ($remoteCharset = $this->getCharset(self::CHARSET_REMOTE))
            && null !== ($localCharset = $this->getCharset(self::CHARSET_LOCAL))
        ) {
            $word = iconv(
                $remoteCharset,
                $localCharset . '//IGNORE//TRANSLIT',
                $word
            );
        }
        
        return $word;
    }

    /**
     * Get the next word in queue as a stream.
     * 
     * Get the next word in queue as a stream, after automatically decoding its
     * length.
     * 
     * @return resource The word, as a stream.
     * @see close()
     */
    public function getNextWordAsStream()
    {
        $filters = new T\FilterCollection();
        if (null !== ($remoteCharset = $this->getCharset(self::CHARSET_REMOTE))
            && null !== ($localCharset = $this->getCharset(self::CHARSET_LOCAL))
        ) {
            $filters->append(
                'convert.iconv.' .
                $remoteCharset . '.' . $localCharset . '//IGNORE//TRANSLIT'
            );
        }
        
        if ($this->trans->isPersistent()) {
            $old = $this->trans->lock(T\Stream::DIRECTION_RECEIVE);
            $stream = $this->trans->receiveStream(
                self::decodeLength($this->trans),
                $filters,
                'stream word'
            );
            $this->trans->lock($old, true);
        } else {
            $stream = $this->trans->receiveStream(
                self::decodeLength($this->trans),
                $filters,
                'stream word'
            );
        }
        
        return $stream;
    }

    /**
     * Decodes the lenght of the incoming message.
     * 
     * Decodes the lenght of the incoming message, as specified by the RouterOS
     * API.
     * 
     * @param T\Stream $trans The transmitter from which to decode the length of
     * the incoming message.
     * 
     * @return int The decoded length.
     */
    public static function decodeLength(T\Stream $trans)
    {
        if ($trans->isPersistent() && $trans instanceof T\TcpClient) {
            $old = $trans->lock($trans::DIRECTION_RECEIVE);
            $length = self::_decodeLength($trans);
            $trans->lock($old, true);
            return $length;
        }
        return self::_decodeLength($trans);
    }

    /**
     * Decodes the lenght of the incoming message.
     * 
     * Decodes the lenght of the incoming message, as specified by the RouterOS
     * API.
     * 
     * Difference with the non private function is that this one doesn't perform
     * locking if the connection is a persistent one.
     * 
     * @param T\Stream $trans The transmitter from which to decode the length of
     *     the incoming message.
     * 
     * @return int The decoded length.
     */
    private static function _decodeLength(T\Stream $trans)
    {
        $byte = ord($trans->receive(1, 'initial length byte'));
        if ($byte & 0x80) {
            if (($byte & 0xC0) === 0x80) {
                return (($byte & 077) << 8 ) + ord($trans->receive(1));
            } elseif (($byte & 0xE0) === 0xC0) {
                $rem = unpack('n~', $trans->receive(2));
                return (($byte & 037) << 16 ) + $rem['~'];
            } elseif (($byte & 0xF0) === 0xE0) {
                $rem = unpack('n~/C~~', $trans->receive(3));
                return (($byte & 017) << 24 ) + ($rem['~'] << 8) + $rem['~~'];
            } elseif (($byte & 0xF8) === 0xF0) {
                $rem = unpack('N~', $trans->receive(4));
                return (($byte & 007) * 0x100000000/* '<< 32' or '2^32' */)
                    + (double) sprintf('%u', $rem['~']);
            }
            throw new NotSupportedException(
                'Unknown control byte encountered.',
                NotSupportedException::CODE_CONTROL_BYTE,
                null,
                $byte
            );
        } else {
            return $byte;
        }
    }

    /**
     * Closes the opened connection, even if it is a persistent one.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function close()
    {
        return $this->trans->close();
    }
}
