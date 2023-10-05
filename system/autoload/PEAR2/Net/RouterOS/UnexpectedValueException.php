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
 * The base for this exception.
 */
use UnexpectedValueException as U;

/**
 * Used in $previous.
 */
use Exception as E;

/**
 * Exception thrown when encountering an invalid value in a function argument.
 *
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class UnexpectedValueException extends U implements Exception
{
    const CODE_CALLBACK_INVALID = 10502;
    const CODE_ACTION_UNKNOWN = 30100;
    const CODE_RESPONSE_TYPE_UNKNOWN = 50100;

    /**
     * The unexpected value.
     *
     * @var mixed
     */
    private $_value;

    /**
     * Creates a new UnexpectedValueException.
     *
     * @param string $message  The Exception message to throw.
     * @param int    $code     The Exception code.
     * @param E|null $previous The previous exception used for the exception
     *     chaining.
     * @param mixed  $value    The unexpected value.
     */
    public function __construct(
        $message,
        $code = 0,
        E $previous = null,
        $value = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->_value = $value;
    }

    /**
     * Gets the unexpected value.
     *
     * @return mixed The unexpected value.
     */
    public function getValue()
    {
        return $this->_value;
    }

    // @codeCoverageIgnoreStart
    // String representation is not reliable in testing

    /**
     * Returns a string representation of the exception.
     *
     * @return string The exception as a string.
     */
    public function __toString()
    {
        return parent::__toString() . "\nValue:{$this->_value}";
    }

    // @codeCoverageIgnoreEnd
}
