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

use InvalidArgumentException as I;

/**
 * Exception thrown when there's something wrong with message arguments.
 * 
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class InvalidArgumentException extends I implements Exception
{
    const CODE_SEEKABLE_REQUIRED = 1100;
    const CODE_NAME_INVALID = 20100;
    const CODE_ABSOLUTE_REQUIRED = 40200;
    const CODE_CMD_UNRESOLVABLE = 40201;
    const CODE_CMD_INVALID = 40202;
    const CODE_NAME_UNPARSABLE = 41000;
    const CODE_VALUE_UNPARSABLE = 41001;
}
