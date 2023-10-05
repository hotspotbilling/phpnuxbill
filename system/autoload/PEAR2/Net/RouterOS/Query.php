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
 * Represents a query for RouterOS requests.
 *
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Query
{

    /**
     * Checks if the property exists.
     */
    const OP_EX = '';

    /**
     * Checks if the property does not exist.
     */
    const OP_NEX = '-';

    /**
     * Checks if the property equals a certain value.
     */
    const OP_EQ = '=';

    /**
     * Checks if the property is less than a certain value.
     */
    const OP_LT = '<';

    /**
     * Checks if the property is greater than a certain value.
     */
    const OP_GT = '>';

    /**
     * An array of the words forming the query.
     *
     * Each value is an array with the first member being the predicate
     * (operator and name), and the second member being the value
     * for the predicate.
     *
     * @var array<string,string|null>[]
     */
    protected $words = array();

    /**
     * This class is not to be instantiated normally, but by static methods
     * instead. Use {@link static::where()} to create an instance of it.
     */
    protected function __construct()
    {

    }

    /**
     * Sanitizes the operator of a condition.
     *
     * @param string $operator The operator to sanitize.
     *
     * @return string The sanitized operator.
     */
    protected static function sanitizeOperator($operator)
    {
        $operator = (string) $operator;
        switch ($operator) {
        case Query::OP_EX:
        case Query::OP_NEX:
        case Query::OP_EQ:
        case Query::OP_LT:
        case Query::OP_GT:
            return $operator;
        default:
            throw new UnexpectedValueException(
                'Unknown operator specified',
                UnexpectedValueException::CODE_ACTION_UNKNOWN,
                null,
                $operator
            );
        }
    }

    /**
     * Creates a new query with an initial condition.
     *
     * @param string               $name     The name of the property to test.
     * @param string|resource|null $value    Value of the property as a string
     *     or seekable stream. Not required for existence tests.
     *     If a seekable stream is provided, it is sent from its current
     *     position to its end, and the pointer is seeked back to its current
     *     position after sending.
     *     Non seekable streams, as well as all other types, are casted to a
     *     string.
     * @param string               $operator One of the OP_* constants.
     *     Describes the operation to perform.
     *
     * @return static A new query object.
     */
    public static function where(
        $name,
        $value = null,
        $operator = self::OP_EX
    ) {
        $query = new static;
        return $query->addWhere($name, $value, $operator);
    }

    /**
     * Negates the query.
     *
     * @return $this The query object.
     */
    public function not()
    {
        $this->words[] = array('#!', null);
        return $this;
    }

    /**
     * Adds a condition as an alternative to the query.
     *
     * @param string               $name     The name of the property to test.
     * @param string|resource|null $value    Value of the property as a string
     *     or seekable stream. Not required for existence tests.
     *     If a seekable stream is provided, it is sent from its current
     *     position to its end, and the pointer is seeked back to its current
     *     position after sending.
     *     Non seekable streams, as well as all other types, are casted to a
     *     string.
     * @param string               $operator One of the OP_* constants.
     *     Describes the operation to perform.
     *
     * @return $this The query object.
     */
    public function orWhere($name, $value = null, $operator = self::OP_EX)
    {
        $this->addWhere($name, $value, $operator)->words[] = array('#|', null);
        return $this;
    }

    /**
     * Adds a condition in addition to the query.
     *
     * @param string               $name     The name of the property to test.
     * @param string|resource|null $value    Value of the property as a string
     *     or seekable stream. Not required for existence tests.
     *     If a seekable stream is provided, it is sent from its current
     *     position to its end, and the pointer is seeked back to its current
     *     position after sending.
     *     Non seekable streams, as well as all other types, are casted to a
     *     string.
     * @param string               $operator One of the OP_* constants.
     *     Describes the operation to perform.
     *
     * @return $this The query object.
     */
    public function andWhere($name, $value = null, $operator = self::OP_EX)
    {
        $this->addWhere($name, $value, $operator)->words[] = array('#&', null);
        return $this;
    }

    /**
     * Sends the query over a communicator.
     *
     * @param Communicator $com The communicator to send the query over.
     *
     * @return int The number of bytes sent.
     */
    public function send(Communicator $com)
    {
        if ($com->getTransmitter()->isPersistent()) {
            $old = $com->getTransmitter()->lock(T\Stream::DIRECTION_SEND);
            $bytes = $this->_send($com);
            $com->getTransmitter()->lock($old, true);
            return $bytes;
        }
        return $this->_send($com);
    }

    /**
     * Sends the query over a communicator.
     *
     * The only difference with the non private equivalent is that this one does
     * not do locking.
     *
     * @param Communicator $com The communicator to send the query over.
     *
     * @return int The number of bytes sent.
     */
    private function _send(Communicator $com)
    {
        if (!$com->getTransmitter()->isAcceptingData()) {
            throw new SocketException(
                'Transmitter is invalid. Sending aborted.',
                SocketException::CODE_QUERY_SEND_FAIL
            );
        }
        $bytes = 0;
        foreach ($this->words as $queryWord) {
            list($predicate, $value) = $queryWord;
            $prefix = '?' . $predicate;
            if (null === $value) {
                $bytes += $com->sendWord($prefix);
            } else {
                $prefix .= '=';
                if (is_string($value)) {
                    $bytes += $com->sendWord($prefix . $value);
                } else {
                    $bytes += $com->sendWordFromStream($prefix, $value);
                }
            }
        }
        return $bytes;
    }

    /**
     * Verifies the query.
     *
     * Verifies the query against a communicator, i.e. whether the query
     * could successfully be sent (assuming the connection is still opened).
     *
     * @param Communicator $com The Communicator to check against.
     *
     * @return $this The query object itself.
     *
     * @throws LengthException If the resulting length of an API word is not
     *     supported.
     */
    public function verify(Communicator $com)
    {
        foreach ($this->words as $queryWord) {
            list($predicate, $value) = $queryWord;
            if (null === $value) {
                $com::verifyLengthSupport(strlen('?' . $predicate));
            } elseif (is_string($value)) {
                $com::verifyLengthSupport(
                    strlen('?' . $predicate . '=' . $value)
                );
            } else {
                $com::verifyLengthSupport(
                    strlen('?' . $predicate . '=') +
                    $com::seekableStreamLength($value)
                );
            }
        }
        return $this;
    }

    /**
     * Adds a condition.
     *
     * @param string               $name     The name of the property to test.
     * @param string|resource|null $value    Value of the property as a string
     *     or seekable stream. Not required for existence tests.
     *     If a seekable stream is provided, it is sent from its current
     *     position to its end, and the pointer is seeked back to its current
     *     position after sending.
     *     Non seekable streams, as well as all other types, are casted to a
     *     string.
     * @param string               $operator One of the ACTION_* constants.
     *     Describes the operation to perform.
     *
     * @return $this The query object.
     */
    protected function addWhere($name, $value, $operator)
    {
        $this->words[] = array(
            static::sanitizeOperator($operator)
            . Message::sanitizeAttributeName($name),
            (null === $value ? null : Message::sanitizeAttributeValue($value))
        );
        return $this;
    }
}
