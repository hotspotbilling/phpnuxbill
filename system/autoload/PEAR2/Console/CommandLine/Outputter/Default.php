<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR2\Console\CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category  Console
 * @package   PEAR2\Console\CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007-2009 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @version   0.2.3
 * @link      http://pear2.php.net/PEAR2_Console_CommandLine
 * @since     File available since release 0.1.0
 *
 * @filesource
 */

namespace PEAR2\Console\CommandLine;

/**
 * PEAR2\Console\CommandLine default Outputter.
 *
 * @category  Console
 * @package   PEAR2\Console\CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007-2009 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @link      http://pear2.php.net/PEAR2_Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Outputter_Default implements Outputter
{
    // stdout() {{{

    /**
     * Writes the message $msg to STDOUT.
     *
     * @param string $msg The message to output
     *
     * @return void
     */
    public function stdout($msg)
    {
        if (defined('STDOUT')) {
            fwrite(STDOUT, $msg);
        } else {
            echo $msg;
        }
    }

    // }}}
    // stderr() {{{

    /**
     * Writes the message $msg to STDERR.
     *
     * @param string $msg The message to output
     *
     * @return void
     */
    public function stderr($msg)
    {
        if (defined('STDERR')) {
            fwrite(STDERR, $msg);
        } else {
            echo $msg;
        }
    }

    // }}}
}
