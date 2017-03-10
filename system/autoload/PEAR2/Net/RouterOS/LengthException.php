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
 * Base of this class.
 */
use LengthException as L;

/**
 * Exception thrown when there is a problem with a word's length.
 * 
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class LengthException extends L implements Exception
{
    
    const CODE_UNSUPPORTED = 1200;
    const CODE_INVALID = 1300;
    const CODE_BEYOND_SHEME = 1301;

    /**
     *
     * @var mixed The problematic length.
     */
    private $_length;

    /**
     * Creates a new LengthException.
     * 
     * @param string     $message  The Exception message to throw.
     * @param int        $code     The Exception code.
     * @param \Exception $previous The previous exception used for the exception
     *     chaining.
     * @param number     $length   The length.
     */
    public function __construct(
        $message,
        $code = 0,
        $previous = null,
        $length = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->_length = $length;
    }

    /**
     * Gets the length.
     * 
     * @return number The length.
     */
    public function getLength()
    {
        return $this->_length;
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
        return parent::__toString() . "\nLength:{$this->_length}";
    }

    // @codeCoverageIgnoreEnd
}
