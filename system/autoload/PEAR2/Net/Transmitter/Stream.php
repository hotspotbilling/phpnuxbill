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
     * PHPNuxBill: the clock every deadline is made and compared with: monotonic (seconds, as a float), so a change of the system
     * clock (NTP step, manual change) can neither stretch nor cut a time budget. Not a calendar time: never store or print it.
     *
     * @return float
     */
    public static function now()
    {
        return hrtime(true) / 1e9;
    }

    /**
     * PHPNuxBill: the moment after which nothing more is read, or NULL. See setDeadline().
     *
     * @var float|null
     */
    protected $deadline = null;

    /**
     * PHPNuxBill: the stream's ordinary read timeout as [seconds, microseconds]: the default_socket_timeout in force when the
     * stream was opened (callers change that setting around opening a connection and put it back afterwards), or the one
     * setTimeout() gave since. A negative value is no time limit. A deadline narrows the timeout for each read; this is put
     * back when it is cleared.
     *
     * @var array<int,int>
     */
    protected $ordinaryTimeout = array(60, 0);

    /**
     * PHPNuxBill: whether a read under the deadline stopped because time ran out (rather than because the other end closed
     * the connection). Cleared when a new deadline is set.
     *
     * @var bool
     */
    protected $timedOut = false;

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
     * @param array|int|null $timeout PHPNuxBill: the read timeout the wrapped socket really has, as seconds or [seconds,
     *     microseconds] (a negative value is no limit): for a socket the caller opened and configured itself. NULL (sockets
     *     this library opens) takes the default_socket_timeout in force now. A cleared deadline puts this back.
     *
     * @see static::isFresh()
     */
    public function __construct($stream, $autoClose = false, $timeout = null)
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
        if (null !== $timeout) {
            $this->ordinaryTimeout = is_array($timeout)
                ? array((int) ($timeout[0] ?? 0), (int) ($timeout[1] ?? 0))
                : array((int) floor($timeout), (int) round(($timeout - floor($timeout)) * 1000000));
        } else {
            // the stream has just been opened with this as its read timeout
            $this->ordinaryTimeout = array((int) ini_get('default_socket_timeout'), 0);
        }
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
        $this->ordinaryTimeout = array((int) $seconds, (int) $microseconds);
        return stream_set_timeout($this->stream, $seconds, $microseconds);
    }

    /**
     * PHPNuxBill: an optional moment (self::now(), the monotonic clock) after which no more is read. The socket's own time limit starts again
     * with every read, so a reply that keeps trickling in could run far past it; with a deadline, each read only waits for
     * the time left. NULL removes it.
     *
     * @param float|null $deadline
     *
     * @return void
     */
    public function setDeadline($deadline)
    {
        $had = null !== $this->deadline;
        $this->deadline = null === $deadline ? null : (float) $deadline;
        if (null !== $this->deadline) {
            $this->timedOut = false;
        }
        if ($had && null === $this->deadline && self::isStream($this->stream)) {
            // the reads under the deadline narrowed the timeout: back to the ordinary one
            stream_set_timeout($this->stream, $this->ordinaryTimeout[0], $this->ordinaryTimeout[1]);
        }
    }

    /**
     * PHPNuxBill: seconds left before the deadline (may be 0 or negative), or NULL when there is none.
     *
     * @return float|null
     */
    public function timeLeft()
    {
        return null === $this->deadline ? null : $this->deadline - self::now();
    }

    /**
     * PHPNuxBill: whether the last read under a deadline ran out of time (false when it failed because the connection closed).
     *
     * @return bool
     */
    public function timedOut()
    {
        return $this->timedOut;
    }

    /**
     * PHPNuxBill: before a read, limit the socket to the time left before the deadline. FALSE (and the connection closed)
     * when the deadline has passed.
     *
     * @return bool
     */
    protected function limitToDeadline()
    {
        if (null === $this->deadline) {
            return true;
        }
        $left = $this->deadline - self::now();
        if ($left <= 0) {
            $this->timedOut = true;
            $this->close();
            return false;
        }
        // the shorter of the time left and the stream's ordinary timeout: a deadline never makes one read wait longer than usual.
        // A negative ordinary timeout is no limit (the deadline decides); zero is a poll, and stays one
        $t = $this->ordinaryTimeout;
        $ordinary = $t[0] + $t[1] / 1000000;
        if ($ordinary >= 0 && $ordinary < $left) {
            $left = $ordinary;
        }
        stream_set_timeout($this->stream, (int) floor($left), (int) (($left - floor($left)) * 1000000));
        return true;
    }

    /**
     * PHPNuxBill: writes one chunk. Without a deadline as the library always did (one blocking fwrite). Under one, a blocking
     * write would wait inside the system call however long the other end takes to read (the socket's time limit does not
     * apply to writes), so the chunk is written without blocking, waiting between tries only for the time left. The whole
     * chunk is written or an exception is thrown: a wait that ended early (interrupted) tries the unwritten rest again while
     * time is left; when the time runs out, or the other end closed the connection, the connection is closed and the
     * exception carries what was sent (half a request cannot be finished later, so nothing more may go out on it).
     *
     * @param string $data  The chunk.
     * @param double $bytes What was sent before it.
     *
     * @return int|false What was written (without a deadline as fwrite() gives it; under one, always all of it).
     */
    protected function writeChunk($data, $bytes)
    {
        if (null === $this->deadline) {
            return @fwrite($this->stream, $data);
        }
        $done = 0;
        $len = strlen($data);
        while ($done < $len) {
            if ($this->deadline - self::now() <= 0) {
                $this->timedOut = true;
                $this->close();
                throw $this->createException('Ran out of time while sending.', 6, null, $bytes + $done);
            }
            if (!$this->isAvailable()) {
                $this->close();
                throw $this->createException('The connection closed while sending.', 7, null, $bytes + $done);
            }
            stream_set_blocking($this->stream, false);
            $n = @fwrite($this->stream, substr($data, $done));
            stream_set_blocking($this->stream, $this->isBlocking);
            if (false === $n) {
                $this->close();
                throw $this->createException('Failed while sending.', 7, null, $bytes + $done);
            }
            $done += $n;
            if ($done < $len && !$this->waitToSend($bytes + $done)) {
                // the wait ended with time left: the other end closed (found at the top of the loop), or the wait was
                // interrupted, and then the same unwritten rest is tried again (after a moment, so this never spins)
                usleep(1000);
            }
        }
        return $done;
    }

    /**
     * PHPNuxBill: after a chunk, wait until the stream can take more. Without a deadline as before (as long as it takes); under
     * one only for the time left: a peer that stops reading cannot hold the send past it. The connection is closed when the
     * time runs out.
     *
     * @param double $bytes What was sent so far.
     *
     * @return bool Whether the stream can take more.
     */
    protected function waitToSend($bytes)
    {
        if (null === $this->deadline) {
            return $this->isAcceptingData(null);
        }
        $left = $this->deadline - self::now();
        if ($left > 0) {
            $sTimeout = (int) floor($left);
            $usTimeout = min(999999, max(0, (int) (($left - $sTimeout) * 1000000)));
            // a non-blocking stream (TLS) is never asked to wait by isAcceptingData(), so a write that took nothing would be
            // tried again at once until the deadline: wait here until it can take more, for the time left at most
            if ($this->isBlocking
                ? $this->isAcceptingData($sTimeout, $usTimeout)
                : $this->isAcceptingData() && $this->waitWritable($sTimeout, $usTimeout)
            ) {
                return true;
            }
        }
        if (self::now() >= $this->deadline) {
            $this->timedOut = true;
            $this->close();
            throw $this->createException('Ran out of time while sending.', 6, null, $bytes);
        }
        // not accepting data for another reason (the other end closed): as before, the next write reports it
        return false;
    }

    /**
     * PHPNuxBill: waits until the stream can be written to, for the given time at most.
     *
     * @param int $sTimeout  Seconds.
     * @param int $usTimeout Microseconds to add.
     *
     * @return bool Whether it can be written to.
     */
    protected function waitWritable($sTimeout, $usTimeout)
    {
        if (!self::isStream($this->stream)) {
            return false;
        }
        $r = $e = null;
        $w = array($this->stream);
        return 1 === @stream_select($r, $w, $e, $sTimeout, $usTimeout);
    }

    /**
     * PHPNuxBill: whether the last read ran out of time; the connection is closed when it did.
     *
     * @return bool
     */
    protected function readTimedOut()
    {
        if (!self::isStream($this->stream)) {
            // already closed: report what was recorded when it closed (a deadline expiry sets the flag first), so a
            // connection the other end dropped early is not mistaken for a time-out
            return $this->timedOut;
        }
        $meta = stream_get_meta_data($this->stream);
        if (empty($meta['timed_out'])) {
            return false;
        }
        $this->timedOut = true;
        $this->close();
        return true;
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
            // PHPNuxBill: a seekable stream goes back to where it was however the send ends (also when it fails or runs out
            // of time); a stream that cannot seek is left as before
            $contentsMeta = stream_get_meta_data($contents);
            $seekable = !empty($contentsMeta['seekable']);
            // under a deadline the source must be one whose reads cannot block (a local, seekable file or memory stream): a
            // socket or pipe could hold fread() past it, so it is refused before anything is written
            if (null !== $this->deadline && !($seekable && stream_is_local($contents))) {
                throw $this->createException('A deadline needs a local, seekable source stream.', 8, null, 0);
            }
            $oldPos = ftell($contents);
            try {
                if ($offsetIsNotNull) {
                    fseek($contents, $offset, SEEK_SET);
                }
                $sentChunk = false;
                while (!feof($contents)) {
                    if ($lengthIsNotNull
                        && 0 === $chunkSize = min($chunkSize, $length - $bytes)
                    ) {
                        break;
                    }
                    // PHPNuxBill: under a deadline the time is checked before each read of the source too, so a source that
                    // blocks, or keeps answering with nothing, cannot hold the send past it
                    if (null !== $this->deadline && $this->deadline - self::now() <= 0) {
                        $this->timedOut = true;
                        $this->close();
                        throw $this->createException('Ran out of time while sending.', 6, null, $bytes);
                    }
                    $contentsToSend = fread($contents, $chunkSize);
                    if (false === $contentsToSend || '' === $contentsToSend) {
                        // nothing came and the source is not at its end: not a busy loop (the deadline above ends it; without one
                        // the pause keeps the wait cheap, as the blocking read of an ordinary file never gets here)
                        if (!feof($contents)) {
                            usleep(3000);
                        }
                        continue;
                    }
                    if ('' != $contentsToSend) {
                        // PHPNuxBill: the wait for the stream to take more comes before each chunk after the first, so it runs
                        // only while more is to be sent (never after the last chunk, even before feof() has noticed the end)
                        if ($sentChunk) {
                            $this->waitToSend($bytes);
                        }
                        $sentChunk = true;
                        $bytesNow = $this->writeChunk($contentsToSend, $bytes);
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
                }
                if (!$seekable) {
                    if ($offsetIsNotNull) {
                        fseek($contents, $oldPos, SEEK_SET);
                    } else {
                        fseek($contents, -$bytes, SEEK_CUR);
                    }
                }
            } finally {
                if ($seekable && false !== $oldPos) {
                    fseek($contents, $oldPos, SEEK_SET);
                }
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
                $bytesNow = $this->writeChunk(substr($contents, $bytes, $chunkSize), $bytes);
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
                // PHPNuxBill: wait for the stream to take more only while more is to be sent (not after the last chunk)
                if ($bytes < $bytesToSend) {
                    $this->waitToSend($bytes);
                }
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
                if (!$this->limitToDeadline()) {
                    break;
                }
                $fragment = fread($this->stream, min($length, $chunkSize));
                if ('' != $fragment) {
                    $length -= strlen($fragment);
                    $result .= $fragment;
                    continue 2;
                } elseif (!$this->isBlocking && !(false === $fragment)) {
                    usleep(3000);
                    continue 2;
                }
                // PHPNuxBill: a read that ran out of the socket's time limit stops here (the "failed while receiving" below)
                // instead of trying again for ever while the other end stays connected but silent. The connection is closed
                // too: half a reply is still on the way, so nothing more can be read from it, and the goodbye a client sends
                // when it is closed or destroyed must fail at once instead of waiting for an answer.
                if ($this->readTimedOut()) {
                    break;
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
                if (!$this->limitToDeadline()) {
                    break;
                }
                $fragment = fread($this->stream, min($length, $chunkSize));
                if ('' != $fragment) {
                    $length -= strlen($fragment);
                    fwrite($result, $fragment);
                    continue 2;
                } elseif (!$this->isBlocking && !(false === $fragment)) {
                    usleep(3000);
                    continue 2;
                }
                // PHPNuxBill: a read that ran out of the socket's time limit stops here (the "failed while receiving" below)
                // instead of trying again for ever while the other end stays connected but silent. The connection is closed
                // too: half a reply is still on the way, so nothing more can be read from it, and the goodbye a client sends
                // when it is closed or destroyed must fail at once instead of waiting for an answer.
                if ($this->readTimedOut()) {
                    break;
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
            // a deadline that has passed ends the wait before the shortcut for a non-blocking stream is taken
            if (null !== $this->deadline && $this->deadline - self::now() <= 0) {
                return false;
            }
            if (null === $sTimeout && !$this->isBlocking) {
                $meta = stream_get_meta_data($this->stream);
                return !$meta['eof'];
            }
            // PHPNuxBill: under a deadline, no wait for data goes past it (the library asks for the full time again for each
            // reply, so a login challenge or a reply that is not the last would otherwise start the wait over)
            if (null !== $this->deadline) {
                $left = $this->deadline - self::now();
                if ($left <= 0) {
                    return false;
                }
                if (null !== $sTimeout) {
                    // seconds and microseconds as stream_select() takes them: nonnegative, microseconds under a second
                    $wait = max(0, (int) $sTimeout + (int) $usTimeout / 1000000);
                    $sTimeout = (int) floor($wait);
                    $usTimeout = min(999999, max(0, (int) round(($wait - $sTimeout) * 1000000)));
                }
                if (null === $sTimeout || $sTimeout + $usTimeout / 1000000 > $left) {
                    $sTimeout = (int) floor($left);
                    $usTimeout = min(999999, max(0, (int) (($left - $sTimeout) * 1000000)));
                }
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
