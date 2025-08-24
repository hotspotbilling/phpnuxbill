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
 * Exception thrown when something goes wrong with the connection.
 *
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class SocketException extends RuntimeException implements Exception
{
    const CODE_SERVICE_INCOMPATIBLE = 10200;
    const CODE_CONNECTION_FAIL = 100;
    const CODE_QUERY_SEND_FAIL = 30600;
    const CODE_REQUEST_SEND_FAIL = 40900;
    const CODE_NO_DATA = 50000;
}
