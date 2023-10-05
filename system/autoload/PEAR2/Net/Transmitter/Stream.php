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
 * @version   1.0.0b2
 * @link      http://pear2.php.net/PEAR2_Net_Transmitter
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Net\Transmitter;

use Exception as E;

/**
 * A stream transmitter.
 *
 * This is a convenience wrapper for stream functionality. Used to ensure data
 * integrity. Designed for TCP sockets, but it has intentionally been made to
 * accept any stream.
 *
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class Stream
{
    /**
     * Used to stop settings in either direction being applied.
     */
    const DIRECTION_NONE = 0;
    /**
     * Used to apply settings only to receiving.
     */
    const DIRECTION_RECEIVE = 1;
    /**
     * Used to apply settings only to sending.
     */
    const DIRECTION_SEND = 2;
    /**
     * Used to apply settings to both sending and receiving.
     */
    const DIRECTION_ALL = 3;

    /**
     * The stream to wrap around.
     *
     * @var resource
     */
    protected $stream;

    /**
     * Whether to automatically close the stream on object destruction if
     * it's not a persistent one.
     *
     * Setting this to FALSE may be useful if you're only using this class
     * "part time", while setting it to TRUE might be useful if you're doing
     * some "one offs".
     *
     * @var bool
     */
    protected $autoClose = false;

    /**
     * A flag that tells whether or not the stream is persistent.
     *
     * @var bool
     */
    protected $persist;

    /**
     * Whether the wrapped stream is in blocking mode or not.
     *
     * @var bool
     */
    protected $isBlocking = true;

    /**
     * An associative array with the chunk size of each direction.
     *
     * Key is the direction, value is the size in bytes as integer.
     *
     * @var array<int,int>
     */
    protected $chunkSize = array(
        self::DIRECTION_SEND => 0xFFFFF, self::DIRECTION_RECEIVE => 0xFFFFF
    );

    /**
     * Wraps around the specified stream.
     *
     * @param resource $stream    The stream to wrap around.
     * @param bool     $autoClose Whether to automatically close the stream on
     *     object destruction if it's not a persistent one. Setting this to
     *     FALSE may be useful if you're only using this class "part time",
     *     while setting it to TRUE might be useful if you're doing some
     *     "on offs".
     *
     * @see static::isFresh()
     */
    public function __construct($stream, $autoClose = false)
    {
        if (!self::isStream($stream)) {
            throw $this->createException('Invalid stream supplied.', 1);
        }
        $this->stream = $stream;
        $this->autoClose = (bool) $autoClose;
        $this->persist = (bool) preg_match(
            '#\s?persistent\s?#sm',
            get_resource_type($stream)
        );
        $meta = stream_get_meta_data($stream);
        $this->isBlocking = isset($meta['blocked']) ? $meta['blocked'] : true;
    }

    /**
     * PHP error handler for connection errors.
     *
     * @param string $level   Level of PHP error raised. Ignored.
     * @param string $message Message raised by PHP.
     *
     * @return void
     *
     * @throws SocketException That's how the error is handled.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function handleError($level, $message)
    {
        throw $this->createException($message, 0);
    }

    /**
     * Checks if a given variable is a stream resource.
     *
     * @param mixed $var The variable to check.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    public static function isStream($var)
    {
        return is_resource($var)
            && (bool) preg_match('#\s?stream$#sm', get_resource_type($var));
    }

    /**
     * Checks whether the wrapped stream is fresh.
     *
     * Checks whether the wrapped stream is fresh. A stream is considered fresh
     * if there hasn't been any activity on it. Particularly useful for
     * detecting reused persistent connections.
     *
     * @return bool TRUE if the socket is fresh, FALSE otherwise.
     */
    public function isFresh()
    {
        return ftell($this->stream) === 0;
    }

    /**
     * Checks whether the wrapped stream is a persistent one.
     *
     * @return bool TRUE if the stream is a persistent one, FALSE otherwise.
     */
    public function isPersistent()
    {
        return $this->persist;
    }

    /**
     * Checks whether the wrapped stream is a blocking one.
     *
     * @return bool TRUE if the stream is a blocking one, FALSE otherwise.
     */
    public function isBlocking()
    {
        return $this->isBlocking;
    }

    /**
     * Sets blocking mode.
     *
     * @param bool $block Sets whether the stream is in blocking mode.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    public function setIsBlocking($block)
    {
        $block = (bool)$block;
        if (stream_set_blocking($this->stream, (int)$block)) {
            $this->isBlocking = $block;
            return true;
        }
        return false;
    }

    /**
     * Sets the timeout for the stream.
     *
     * @param int $seconds      Timeout in seconds.
     * @param int $microseconds Timeout in microseconds to be added to the
     *     seconds.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    public function setTimeout($seconds, $microseconds = 0)
    {
        return stream_set_timeout($this->stream, $seconds, $microseconds);
    }

    /**
     * Sets the size of a stream's buffer.
     *
     * @param int $size      The desired size of the buffer, in bytes.
     * @param int $direction The buffer of which direction to set. Valid
     *     values are the DIRECTION_* constants.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    public function setBuffer($size, $direction = self::DIRECTION_ALL)
    {
        switch($direction) {
        case self::DIRECTION_SEND:
            return stream_set_write_buffer($this->stream, $size) === 0;
        case self::DIRECTION_RECEIVE:
            return stream_set_read_buffer($this->stream, $size) === 0;
        case self::DIRECTION_ALL:
            return $this->setBuffer($size, self::DIRECTION_RECEIVE)
                && $this->setBuffer($size, self::DIRECTION_SEND);
        }
        return false;
    }

    /**
     * Sets the size of the chunk.
     *
     * To ensure data integrity, as well as to allow for lower memory
     * consumption, data is sent/received in chunks. This function
     * allows you to set the size of each chunk. The default is 0xFFFFF.
     *
     * @param int $size      The desired size of the chunk, in bytes.
     * @param int $direction The chunk of which direction to set. Valid
     *     values are the DIRECTION_* constants.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    public function setChunk($size, $direction = self::DIRECTION_ALL)
    {
        $size = (int) $size;
        if ($size <= 0) {
            return false;
        }
        switch($direction) {
        case self::DIRECTION_SEND:
        case self::DIRECTION_RECEIVE:
            $this->chunkSize[$direction] = $size;
            return true;
        case self::DIRECTION_ALL:
            $this->chunkSize[self::DIRECTION_SEND]
                = $this->chunkSize[self::DIRECTION_RECEIVE] = $size;
            return true;
        }
        return false;
    }

    /**
     * Gets the size of the chunk.
     *
     * @param int $direction The chunk of which direction to get. Valid
     *     values are the DIRECTION_* constants.
     *
     * @return int|array<int,int>|false The chunk size in bytes,
     *     or an array of chunk sizes with the directions as keys.
     *     FALSE on invalid direction.
     */
    public function getChunk($direction = self::DIRECTION_ALL)
    {
        switch($direction) {
        case self::DIRECTION_SEND:
        case self::DIRECTION_RECEIVE:
            return $this->chunkSize[$direction];
        case self::DIRECTION_ALL:
            return $this->chunkSize;
        }
        return false;
    }

    /**
     * Sends a string or stream over the wrapped stream.
     *
     * Sends a string or stream over the wrapped stream. If a seekable stream is
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
        $bytes = 0;
        $chunkSize = $this->chunkSize[self::DIRECTION_SEND];
        $lengthIsNotNull = null !== $length;
        $offsetIsNotNull = null !== $offset;
        if (self::isStream($contents)) {
            if ($offsetIsNotNull) {
                $oldPos = ftell($contents);
                fseek($contents, $offset, SEEK_SET);
            }
            while (!feof($contents)) {
                if ($lengthIsNotNull
                    && 0 === $chunkSize = min($chunkSize, $length - $bytes)
                ) {
                    break;
                }
                $contentsToSend = fread($contents, $chunkSize);
                if ('' != $contentsToSend) {
                    $bytesNow = @fwrite(
                        $this->stream,
                        $contentsToSend
                    );
                    if (0 != $bytesNow) {
                        $bytes += $bytesNow;
                    } elseif ($this->isBlocking || false === $bytesNow) {
                        throw $this->createException(
                            'Failed while sending stream.',
                            2,
                            null,
                            $bytes
                        );
                    }
                }
                $this->isAcceptingData(null);
            }
            if ($offsetIsNotNull) {
                fseek($contents, $oldPos, SEEK_SET);
            } else {
                fseek($contents, -$bytes, SEEK_CUR);
            }
        } else {
            $contents = (string) $contents;
            if ($offsetIsNotNull) {
                $contents = substr($contents, $offset);
            }
            if ($lengthIsNotNull) {
                $contents = substr($contents, 0, $length);
            }
            $bytesToSend = (double) sprintf('%u', strlen($contents));
            while ($bytes < $bytesToSend) {
                $bytesNow = @fwrite(
                    $this->stream,
                    substr($contents, $bytes, $chunkSize)
                );
                if (0 != $bytesNow) {
                    $bytes += $bytesNow;
                } elseif ($this->isBlocking || false === $bytesNow) {
                    throw $this->createException(
                        'Failed while sending string.',
                        3,
                        null,
                        $bytes
                    );
                }
                $this->isAcceptingData(null);
            }
        }
        return $bytes;
    }

    /**
     * Reads from the wrapped stream to receive.
     *
     * Reads from the wrapped stream to receive content as a string.
     *
     * @param int    $length The number of bytes to receive.
     * @param string $what   Descriptive string about what is being received
     *     (used in exception messages).
     *
     * @return string The received content.
     */
    public function receive($length, $what = 'data')
    {
        $result = '';
        $chunkSize = $this->chunkSize[self::DIRECTION_RECEIVE];
        while ($length > 0) {
            while ($this->isAvailable()) {
                $fragment = fread($this->stream, min($length, $chunkSize));
                if ('' != $fragment) {
                    $length -= strlen($fragment);
                    $result .= $fragment;
                    continue 2;
                } elseif (!$this->isBlocking && !(false === $fragment)) {
                    usleep(3000);
                    continue 2;
                }
            }
            throw $this->createException(
                "Failed while receiving {$what}",
                4,
                null,
                $result
            );
        }
        return $result;
    }

    /**
     * Reads from the wrapped stream to receive.
     *
     * Reads from the wrapped stream to receive content as a stream.
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
        $result = fopen('php://temp', 'r+b');
        $appliedFilters = array();
        if (null !== $filters) {
            foreach ($filters as $filterName => $params) {
                $appliedFilters[] = stream_filter_append(
                    $result,
                    $filterName,
                    STREAM_FILTER_WRITE,
                    $params
                );
            }
        }

        $chunkSize = $this->chunkSize[self::DIRECTION_RECEIVE];
        while ($length > 0) {
            while ($this->isAvailable()) {
                $fragment = fread($this->stream, min($length, $chunkSize));
                if ('' != $fragment) {
                    $length -= strlen($fragment);
                    fwrite($result, $fragment);
                    continue 2;
                } elseif (!$this->isBlocking && !(false === $fragment)) {
                    usleep(3000);
                    continue 2;
                }
            }

            foreach ($appliedFilters as $filter) {
                stream_filter_remove($filter);
            }
            rewind($result);
            throw $this->createException(
                "Failed while receiving {$what}",
                5,
                null,
                $result
            );
        }

        foreach ($appliedFilters as $filter) {
            stream_filter_remove($filter);
        }
        rewind($result);
        return $result;
    }

    /**
     * Checks whether the stream is available for operations.
     *
     * For network streams, this means whether the other end has closed the
     * connection.
     *
     * @return bool TRUE if the stream is available, FALSE otherwise.
     */
    public function isAvailable()
    {
        return self::isStream($this->stream) && !feof($this->stream);
    }

    /**
     * Checks whether there is data to be read from the wrapped stream.
     *
     * @param int|null $sTimeout  If there isn't data awaiting currently,
     *     wait for it this many seconds for data to arrive. If NULL is
     *     specified, wait indefinitely for that.
     * @param int      $usTimeout Microseconds to add to the waiting time.
     *
     * @return bool TRUE if there is data to be read, FALSE otherwise.
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function isDataAwaiting($sTimeout = 0, $usTimeout = 0)
    {
        if (self::isStream($this->stream)) {
            if (null === $sTimeout && !$this->isBlocking) {
                $meta = stream_get_meta_data($this->stream);
                return !$meta['eof'];
            }

            $w = $e = null;
            $r = array($this->stream);
            return 1 === @/* due to PHP bug #54563 */stream_select(
                $r,
                $w,
                $e,
                $sTimeout,
                $usTimeout
            );
        }
        return false;
    }

    /**
     * Checks whether the wrapped stream can be written to without a block.
     *
     * @param int|null $sTimeout  If the stream isn't currently accepting data,
     *     wait for it this many seconds to start accepting data. If NULL is
     *     specified, wait indefinitely for that.
     * @param int      $usTimeout Microseconds to add to the waiting time.
     *
     * @return bool TRUE if the wrapped stream would not block on a write,
     *     FALSE otherwise.
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function isAcceptingData($sTimeout = 0, $usTimeout = 0)
    {
        if (self::isStream($this->stream)) {
            if (!$this->isBlocking) {
                $meta = stream_get_meta_data($this->stream);
                return !$meta['eof'];
            } elseif (feof($this->stream)) {
                return false;
            }

            $r = $e = null;
            $w = array($this->stream);
            return 1 === @/* due to PHP bug #54563 */stream_select(
                $r,
                $w,
                $e,
                $sTimeout,
                $usTimeout
            );
        }
        return false;
    }

    /**
     * Closes the opened stream, unless it's a persistent one.
     */
    public function __destruct()
    {
        if ((!$this->persist) && $this->autoClose) {
            $this->close();
        }
    }

    /**
     * Closes the opened stream, even if it is a persistent one.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    public function close()
    {
        return self::isStream($this->stream) && fclose($this->stream);
    }

    /**
     * Creates a new exception.
     *
     * Creates a new exception. Used by the rest of the functions in this class.
     * Override in derived classes for custom exception handling.
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
     * @return StreamException The exception to then be thrown.
     */
    protected function createException(
        $message,
        $code = 0,
        E $previous = null,
        $fragment = null
    ) {
        return new StreamException($message, $code, $previous, $fragment);
    }
}
