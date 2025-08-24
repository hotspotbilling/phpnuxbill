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
 * Base of this class.
 */
use RuntimeException;

/**
 * Refered to in the constructor.
 */
use Exception as E;

/**
 * Exception thrown by higher level classes (Util, etc.) when the router
 * returns an error.
 *
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class RouterErrorException extends RuntimeException implements Exception
{
    const CODE_ITEM_ERROR           = 0x100000;
    const CODE_SCRIPT_ERROR         = 0x200000;
    const CODE_READ_ERROR           = 0x010000;
    const CODE_WRITE_ERROR          = 0x020000;
    const CODE_EXEC_ERROR           = 0x040000;

    const CODE_CACHE_ERROR          = 0x100001;
    const CODE_GET_ERROR            = 0x110001;
    const CODE_GETALL_ERROR         = 0x110002;
    const CODE_ADD_ERROR            = 0x120001;
    const CODE_SET_ERROR            = 0x120002;
    const CODE_REMOVE_ERROR         = 0x120004;
    const CODE_ENABLE_ERROR         = 0x120012;
    const CODE_DISABLE_ERROR        = 0x120022;
    const CODE_COMMENT_ERROR        = 0x120042;
    const CODE_UNSET_ERROR          = 0x120082;
    const CODE_MOVE_ERROR           = 0x120107;
    const CODE_SCRIPT_ADD_ERROR     = 0x220001;
    const CODE_SCRIPT_REMOVE_ERROR  = 0x220004;
    const CODE_SCRIPT_RUN_ERROR     = 0x240001;
    const CODE_SCRIPT_FILE_ERROR    = 0x240003;

    /**
     * The complete response returned by the router.
     *
     * NULL when the router was not contacted at all.
     *
     * @var ResponseCollection|null
     */
    private $_responses = null;

    /**
     * Creates a new RouterErrorException.
     *
     * @param string                  $message   The Exception message to throw.
     * @param int                     $code      The Exception code.
     * @param E|null                  $previous  The previous exception used for
     *     the exception chaining.
     * @param ResponseCollection|null $responses The complete set responses
     *     returned by the router.
     */
    public function __construct(
        $message,
        $code = 0,
        E $previous = null,
        ResponseCollection $responses = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->_responses = $responses;
    }

    /**
     * Gets the complete set responses returned by the router.
     *
     * @return ResponseCollection|null The complete set responses
     *     returned by the router.
     */
    public function getResponses()
    {
        return $this->_responses;
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
        $result = parent::__toString();
        if ($this->_responses instanceof ResponseCollection) {
            $result .= "\nResponse collection:\n" .
                print_r($this->_responses->toArray(), true);
        }
        return $result;
    }

    // @codeCoverageIgnoreEnd
}
