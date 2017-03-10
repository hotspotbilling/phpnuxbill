<?php

/**
 * Flags class for PEAR2_Console_Color
 * Mappping the names of Font Style to your values.
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
 * This class has the possibles flags to a color setting.
 * 
 * @category Console
 * @package  PEAR2_Console_Color
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Console_Color
 */
abstract class Flags
{
    /**
     * Used at {@link \PEAR2\Console\Color::setFlags()} to specify that no
     * flags should be applied.
     */
    const NONE    = 0;

    /**
     * Used at {@link \PEAR2\Console\Color::setFlags()} as part of a bitmask.
     * If specified, resets all color and style information before applying
     * everything else.
     */
    const RESET   = 1;

    /**
     * Used at {@link \PEAR2\Console\Color::setFlags()} as part of a bitmask.
     * If specified, inverses the font and background colors, before letting
     * the remaining settings further modify things.
     * If specified together with {@link self::RESET}, takes effect AFTER the
     * reset.
     */
    const INVERSE = 2;

    /**
     * @var int[] Array with the flag as a key, and the corresponding code as a
     *     value.
     */
    protected static $flagCodes = array(
        self::RESET   => 0,
        self::INVERSE => 7
    );

    /**
     * Gets the codes for a flag set.
     * 
     * @param int $flags The flags to get the codes for.
     * 
     * @return int[] The codes for the flags specified, in ascending order,
     *     based on the flag constants' values.
     */
    final public static function getCodes($flags)
    {
        if (self::NONE === $flags) {
            return array();
        }

        $result = array();
        $flagsClass = new ReflectionClass(get_called_class());
        $validFlags = array_values(
            array_unique($flagsClass->getConstants(), SORT_NUMERIC)
        );
        foreach ($validFlags as $flag) {
            if ($flags & $flag) {
                $result[] = static::$flagCodes[$flag];
            }
        }
        return $result;
    }
}
