<?php

/**
 * Styles class for PEAR2_Console_Color.
 * 
 * PHP version 5.3
 *
 * @category Console
 * @package  PEAR2_Console_Color
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version  1.0.0
 * @link     http://pear2.php.net/PEAR2_Console_Color
 */
namespace PEAR2\Console\Color;

use ReflectionClass;

/**
 * This class has the possibles values to a Font Style.
 * 
 * @category Console
 * @package  PEAR2_Console_Color
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Console_Color
 */
abstract class Styles
{
    /**
     * Used in {@link \PEAR2\Console\Color::setStyles()} to match all styles.
     */
    const ALL       = null;

    /**
     * Used in {@link \PEAR2\Console\Color::setStyles()} as part of a bitmask.
     * If specified, matches the bold style.
     * When this style is enabled, the font is bolder.
     * With ANSICON, the font color becomes more intense (but not bolder).
     */
    const BOLD      = 1;

    /**
     * Used in {@link \PEAR2\Console\Color::setStyles()} as part of a bitmask.
     * If specified, matches the underline style.
     * When this style is enabled, the font is underlined.
     * With ANSICON, the background color becomes more intense
     * (and the font is not underlined), same as {@link self::BLINK}.
     */
    const UNDERLINE = 2;

    /**
     * Used in {@link \PEAR2\Console\Color::setStyles()} as part of a bitmask.
     * If specified, matches the blink style.
     * When this style is enabled, the font color switches between its regular
     * color and the background color at regular (implementation defined)
     * intervals, creating the illusion of a blinking text.
     * With ANSICON, the background color becomes more intense
     * (and the font is not blinking), same as with {@link self::UNDERLINE}.
     */
    const BLINK     = 4;

    /**
     * Used in {@link \PEAR2\Console\Color::setStyles()} as part of a bitmask.
     * If specified, matches the concealed style.
     * When this style is enabled, the font color becomes the background color,
     * rendering the text invisible. This style is particularly useful for
     * implementations where simply setting the same color and background color
     * would not necesarily provide a fully invisibile text (e.g. ANSICON).
     */
    const CONCEALED = 8;

    /**
     * @var (int[])[] An array describing the codes for the styles.
     *     Each array key is the style's constant, and each value is an array
     *     where the first member is the disable code, and the second is the
     *     enable code.
     */
    protected static $styleCodes = array(
        self::BOLD      => array(22, 1),
        self::UNDERLINE => array(24, 4),
        self::BLINK     => array(25, 5),
        self::CONCEALED => array(28, 8)
    );

    /**
     * Get style constants.
     * 
     * @param int|null $styles Bitmask of styles to match.
     *     You can also use {@link self::ALL} (only) to get all styles.
     * 
     * @return int[] Matching style constants.
     */
    final public static function match($styles)
    {
        $flagsClass = new ReflectionClass(get_called_class());
        $validStyles = array_values(
            array_unique($flagsClass->getConstants(), SORT_NUMERIC)
        );
        unset($validStyles[array_search(self::ALL, $validStyles, true)]);

        if (self::ALL === $styles) {
            return $validStyles;
        }
        $styles = (int)$styles;

        $result = array();
        foreach ($validStyles as $flag) {
            if ($styles & $flag) {
                $result[] = $flag;
            }
        }
        return $result;
    }

    /**
     * Gets the code for a style.
     * 
     * @param int  $style The style to get the code for.
     * @param bool $state The state to get code for.
     *     TRUE for the enabled state codes,
     *     FALSE for the disabled state codes.
     * 
     * @return int The code for the flag specified.
     */
    final public static function getCode($style, $state)
    {
        return static::$styleCodes[$style][(int)(bool)$state];
    }
}
