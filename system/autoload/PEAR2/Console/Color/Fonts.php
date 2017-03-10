<?php

/**
 * Font class for PEAR2_Console_Color
 * 
 * PHP version 5.3
 *
 * @category  Console
 * @package   PEAR2_Console_Color
 * @author    Ivo Nascimento <ivo@o8o.com.br>
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   1.0.0
 * @link      http://pear2.php.net/PEAR2_Console_Color
 */
namespace PEAR2\Console\Color;

/**
 * This class has the possibles values to a Font Color.
 *
 * @category  Console
 * @package   PEAR2_Console_Color
 * @author    Ivo Nascimento <ivo@o8o.com.br>
 * @copyright 2011 Ivo Nascimento
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://pear2.php.net/PEAR2_Console_Color
 */
abstract class Fonts
{
    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to specify that
     * the font color already in effect should be kept.
     */
    const KEEP    = null;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to black/grey (implementation defined).
     */
    const BLACK   = 30;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to black/grey (implementation defined).
     */
    const GREY    = 30;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to maroon/red (implementation defined).
     */
    const MAROON  = 31;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to maroon/red (implementation defined).
     */
    const RED     = 31;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to green/lime (implementation defined).
     */
    const LIME    = 32;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to green/lime (implementation defined).
     */
    const GREEN   = 32;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to brown/yellow (implementation defined).
     */
    const BROWN   = 33;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to brown/yellow (implementation defined).
     */
    const YELLOW  = 33;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to navy/blue (implementation defined).
     */
    const NAVY    = 34;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to navy/blue (implementation defined).
     */
    const BLUE    = 34;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to purple/magenta (implementation defined).
     */
    const PURPLE  = 35;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to purple/magenta (implementation defined).
     */
    const MAGENTA = 35;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to teal/cyan (implementation defined).
     */
    const TEAL    = 36;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to teal/cyan (implementation defined).
     */
    const CYAN    = 36;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to silver/white (implementation defined).
     */
    const SILVER  = 37;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to silver/white (implementation defined).
     */
    const WHITE   = 37;

    /**
     * Used at {@link \PEAR2\Console\Color::setFont()} to set the
     * font color to whatever the default one is.
     */
    const RESET   = 39;
}
