<?php

/**
 * Wrapper for shared memory and locking functionality across different extensions.

 * 
 * Allows you to share data across requests as long as the PHP process is running. One of APC or WinCache is required to accomplish this, with other extensions being potentially pluggable as adapters.
 * 
 * PHP version 5
 * 
 * @category  Caching
 * @package   PEAR2_Cache_SHM
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   0.2.0
 * @link      http://pear2.php.net/PEAR2_Cache_SHM
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Cache\SHM;

/**
 * Exception thrown when there's something wrong with an argument.
 * 
 * @category Caching
 * @package  PEAR2_Cache_SHM
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Cache_SHM
 */
class InvalidArgumentException extends \InvalidArgumentException
    implements Exception
{
}
