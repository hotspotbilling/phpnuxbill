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
 * @version   1.0.0b6
 * @link      http://pear2.php.net/PEAR2_Net_RouterOS
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Net\RouterOS;

/**
 * Refers to transmitter direction constants.
 */
use PEAR2\Net\Transmitter as T;

/**
 * Locks are released upon any exception from anywhere.
 */
use Exception as E;

/**
 * Represents a RouterOS response.
 *
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Response extends Message
{

    /**
     * The last response for a request.
     */
    const TYPE_FINAL = '!done';

    /**
     * A response with data.
     */
    const TYPE_DATA = '!re';

    /**
     * A response signifying error.
     */
    const TYPE_ERROR = '!trap';

    /**
     * A response signifying a fatal error, due to which the connection would be
     * terminated.
     */
    const TYPE_FATAL = '!fatal';

    /**
     * An array of unrecognized words in network order.
     *
     * @var string[]
     */
    protected $unrecognizedWords = array();

    /**
     * The response type.
     *
     * @var string
     */
    private $_type;

    /**
     * Extracts a new response from a communicator.
     *
     * @param Communicator  $com       The communicator from which to extract
     *     the new response.
     * @param bool          $asStream  Whether to populate the argument values
     *     with streams instead of strings.
     * @param int           $sTimeout  If a response is not immediately
     *     available, wait this many seconds. If NULL, wait indefinitely.
     * @param int|null      $usTimeout Microseconds to add to the waiting time.
     * @param Registry|null $reg       An optional registry to sync the
     *     response with.
     *
     * @see getType()
     * @see getArgument()
     */
    public function __construct(
        Communicator $com,
        $asStream = false,
        $sTimeout = 0,
        $usTimeout = null,
        Registry $reg = null
    ) {
        if (null === $reg) {
            if ($com->getTransmitter()->isPersistent()) {
                $old = $com->getTransmitter()
                    ->lock(T\Stream::DIRECTION_RECEIVE);
                try {
                    $this->_receive($com, $asStream, $sTimeout, $usTimeout);
                } catch (E $e) {
                    $com->getTransmitter()->lock($old, true);
                    throw $e;
                }
                $com->getTransmitter()->lock($old, true);
            } else {
                $this->_receive($com, $asStream, $sTimeout, $usTimeout);
            }
        } else {
            while (null === ($response = $reg->getNextResponse())) {
                $newResponse = new self($com, true, $sTimeout, $usTimeout);
                $tagInfo = $reg::parseTag($newResponse->getTag());
                $newResponse->setTag($tagInfo[1]);
                if (!$reg->add($newResponse, $tagInfo[0])) {
                    $response = $newResponse;
                    break;
                }
            }

            $this->_type = $response->_type;
            $this->attributes = $response->attributes;
            $this->unrecognizedWords = $response->unrecognizedWords;
            $this->setTag($response->getTag());

            if (!$asStream) {
                foreach ($this->attributes as $name => $value) {
                    $this->setAttribute(
                        $name,
                        stream_get_contents($value)
                    );
                }
                foreach ($response->unrecognizedWords as $i => $value) {
                    $this->unrecognizedWords[$i] = stream_get_contents($value);
                }
            }
        }
    }

    /**
     * Extracts a new response from a communicator.
     *
     * This is the function that performs the actual receiving, while the
     * constructor is also involved in locks and registry sync.
     *
     * @param Communicator $com       The communicator from which to extract
     *     the new response.
     * @param bool         $asStream  Whether to populate the argument values
     *     with streams instead of strings.
     * @param int          $sTimeout  If a response is not immediately
     *     available, wait this many seconds. If NULL, wait indefinitely.
     *     Note that if an empty sentence is received, the timeout will be
     *     reset for another sentence receiving.
     * @param int|null     $usTimeout Microseconds to add to the waiting time.
     *
     * @return void
     */
    private function _receive(
        Communicator $com,
        $asStream = false,
        $sTimeout = 0,
        $usTimeout = null
    ) {
        do {
            if (!$com->getTransmitter()->isDataAwaiting(
                $sTimeout,
                $usTimeout
            )) {
                throw new SocketException(
                    'No data within the time limit',
                    SocketException::CODE_NO_DATA
                );
            }
            $type = $com->getNextWord();
        } while ('' === $type);
        $this->setType($type);
        if ($asStream) {
            for ($word = $com->getNextWordAsStream(), fseek($word, 0, SEEK_END);
                ftell($word) !== 0;
                $word = $com->getNextWordAsStream(), fseek(
                    $word,
                    0,
                    SEEK_END
                )) {
                rewind($word);
                $ind = fread($word, 1);
                if ('=' === $ind || '.' === $ind) {
                    $prefix = stream_get_line($word, null, '=');
                }
                if ('=' === $ind) {
                    $value = fopen('php://temp', 'r+b');
                    $bytesCopied = ftell($word);
                    while (!feof($word)) {
                        $bytesCopied += stream_copy_to_stream(
                            $word,
                            $value,
                            0xFFFFF,
                            $bytesCopied
                        );
                    }
                    rewind($value);
                    $this->setAttribute($prefix, $value);
                    continue;
                }
                if ('.' === $ind && 'tag' === $prefix) {
                    $this->setTag(stream_get_contents($word, -1, -1));
                    continue;
                }
                rewind($word);
                $this->unrecognizedWords[] = $word;
            }
        } else {
            for ($word = $com->getNextWord(); '' !== $word;
                $word = $com->getNextWord()) {
                if (preg_match('/^=([^=]+)=(.*)$/sS', $word, $matches)) {
                    $this->setAttribute($matches[1], $matches[2]);
                } elseif (preg_match('/^\.tag=(.*)$/sS', $word, $matches)) {
                    $this->setTag($matches[1]);
                } else {
                    $this->unrecognizedWords[] = $word;
                }
            }
        }
    }

    /**
     * Sets the response type.
     *
     * Sets the response type. Valid values are the TYPE_* constants.
     *
     * @param string $type The new response type.
     *
     * @return $this The response object.
     *
     * @see getType()
     */
    protected function setType($type)
    {
        switch ($type) {
        case self::TYPE_FINAL:
        case self::TYPE_DATA:
        case self::TYPE_ERROR:
        case self::TYPE_FATAL:
            $this->_type = $type;
            return $this;
        default:
            throw new UnexpectedValueException(
                'Unrecognized response type.',
                UnexpectedValueException::CODE_RESPONSE_TYPE_UNKNOWN,
                null,
                $type
            );
        }
    }

    /**
     * Gets the response type.
     *
     * @return string The response type.
     *
     * @see setType()
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Gets the value of an argument.
     *
     * @param string $name The name of the argument.
     *
     * @return string|resource|null The value of the specified argument.
     *     Returns NULL if such an argument is not set.
     *
     * @deprecated         1.0.0b5 Use {@link static::getProperty()} instead.
     *     This method will be removed upon final release, and is currently
     *     left standing merely because it can't be easily search&replaced in
     *     existing code, due to the fact the name "getArgument()" is shared
     *     with {@link Request::getArgument()}, which is still valid.
     * @codeCoverageIgnore
     */
    public function getArgument($name)
    {
        trigger_error(
            'Response::getArgument() is deprecated in favor of ' .
            'Response::getProperty() (but note that Request::getArgument() ' .
            'is still valid)',
            E_USER_DEPRECATED
        );
        return $this->getAttribute($name);
    }

    /**
     * Gets the value of a property.
     *
     * @param string $name The name of the property.
     *
     * @return string|resource|null The value of the specified property.
     *     Returns NULL if such a property is not set.
     */
    public function getProperty($name)
    {
        return parent::getAttribute($name);
    }

    /**
     * Gets a list of unrecognized words.
     *
     * @return string[] The list of unrecognized words.
     */
    public function getUnrecognizedWords()
    {
        return $this->unrecognizedWords;
    }
}
