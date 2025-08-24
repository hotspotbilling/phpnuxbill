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

/**
 * A network transmitter.
 *
 * This is a convenience wrapper for network streams. Used to ensure data
 * integrity.
 *
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
abstract class NetworkStream extends Stream
{
    /**
     * Used in {@link setCrypto()} to disable encryption.
     */
    const CRYPTO_OFF = '';

    /**
     * Used in {@link setCrypto()} to set encryption to either SSLv2 or SSLv3,
     * depending on what the other end supports.
     */
    const CRYPTO_SSL = 'SSLv23';

    /**
     * Used in {@link setCrypto()} to set encryption to SSLv2.
     */
    const CRYPTO_SSL2 = 'SSLv2';

    /**
     * Used in {@link setCrypto()} to set encryption to SSLv3.
     */
    const CRYPTO_SSL3 = 'SSLv3';

    /**
     * Used in {@link setCrypto()} to set encryption to TLS (exact version
     * negotiated between 1.0 and 1.2).
     */
    const CRYPTO_TLS = 'TLS';

    /**
     * The type of stream. Can be either "_CLIENT" or "_SERVER".
     *
     * Used to complement the encryption type. Must be set by child classes
     * for {@link setCrypto()} to work properly.
     *
     * @var string
     */
    protected $streamType = '';

    /**
     * The current cryptography setting.
     *
     * @var string
     */
    protected $crypto = '';

    /**
     * Wraps around the specified stream.
     *
     * @param resource $stream The stream to wrap around.
     */
    public function __construct($stream)
    {
        parent::__construct($stream, true);
    }

    /**
     * Gets the current cryptography setting.
     *
     * @return string One of this class' CRYPTO_* constants.
     */
    public function getCrypto()
    {
        return $this->crypto;
    }

    /**
     * Sets the current connection's cryptography setting.
     *
     * @param string $type The encryption type to set. Must be one of this
     *     class' CRYPTO_* constants.
     *
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function setCrypto($type)
    {
        if (self::CRYPTO_OFF === $type) {
            $result = stream_socket_enable_crypto($this->stream, false);
        } else {
            $result = stream_socket_enable_crypto(
                $this->stream,
                true,
                constant("STREAM_CRYPTO_METHOD_{$type}{$this->streamType}")
            );
        }

        if ($result) {
            $this->crypto = $type;
        }
        return $result;
    }

    /**
     * Checks whether the stream is available for operations.
     *
     * @return bool TRUE if the stream is available, FALSE otherwise.
     */
    public function isAvailable()
    {
        if ($this->isStream($this->stream)) {
            if ($this->isBlocking && feof($this->stream)) {
                return false;
            }
            $meta = stream_get_meta_data($this->stream);
            return !$meta['eof'];
        }
        return false;
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
        $result = parent::setBuffer($size, $direction);
        if (self::DIRECTION_SEND === $direction
            && function_exists('stream_set_chunk_size') && !$result
        ) {
            return false !== @stream_set_chunk_size($this->stream, $size);
        }
        return $result;
    }

    /**
     * Shutdown a full-duplex connection
     *
     * Shutdowns (partially or not) a full-duplex connection.
     *
     * @param int $direction The direction for which to disable further
     *     communications.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    public function shutdown($direction = self::DIRECTION_ALL)
    {
        $directionMap = array(
            self::DIRECTION_ALL => STREAM_SHUT_RDWR,
            self::DIRECTION_SEND => STREAM_SHUT_WR,
            self::DIRECTION_RECEIVE => STREAM_SHUT_RD
        );
        return array_key_exists($direction, $directionMap)
            && stream_socket_shutdown($this->stream, $directionMap[$direction]);
    }
}
