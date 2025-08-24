<?php

/**
 * Main class for Console_Color
 *
 * PHP version 5.3
 *
 * @category Console
 * @package  Console_Color
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @author   Ivo Nascimento <ivo@o8o.com.br>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version  1.0.0
 * @link     http://pear.php.net/package/Console_Color
 */
namespace PEAR2\Console;

use PEAR2\Console\Color\Backgrounds;
use PEAR2\Console\Color\Flags;
use PEAR2\Console\Color\Fonts;
use PEAR2\Console\Color\Styles;
use PEAR2\Console\Color\UnexpectedValueException;
use ReflectionClass;

/**
 * Main class for Console_Color.
 *
 * @category Console
 * @package  Console_Color
 * @author   Ivo Nascimento <ivo@o8o.com.br>
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Console_Color
 */
class Color
{
    /**
     * @var array List of valid font colors.
     *     Filled by {@link fillValidators()}.
     */
    protected static $validFonts = array();

    /**
     * @var array List of valid background colors.
     *     Filled by {@link fillValidators()}.
     */
    protected static $validBackgorunds = array();

    /**
     * @var string Name of a class that is used to resolve flags to codes.
     */
    protected static $flagsResolver = '';

    /**
     * @var string Name of a class that is used to resolve styles to codes.
     */
    protected static $stylesResolver = '';

    /**
     * @var int Flags to set.
     */
    protected $flags = 0;

    /**
     * @var int|null The code for the currently specified font color.
     */
    protected $font = null;

    /**
     * @var int|null The code for the currently specified background color.
     */
    protected $backgorund = null;

    /**
     * @var bool[] Array with the status of each style.
     */
    protected $styles = array();

    /**
     * @var string|null The string to write to console to get the specified
     *     styling. NULL when the string needs to be regenerated.
     */
    protected $sequence = null;

    /**
     * Fills the list of valid fonts and backgrounds.
     * 
     * Classes extending this one that wish to add additional valid colors,
     * flags or styles should call this method in their own constructor BEFORE
     * calling the parent constructor.
     * 
     * @param string $fonts       Name of class, the constants of which are
     *     valid font colors.
     * @param string $backgrounds Name of class, the constants of which are
     *     valid background colors.
     * @param string $flags       Name of class that resolves flags to codes.
     *     Must inheirt from {@link Flags}. Constants of this
     *     class are considered the valid flags, and the coresponding codes must
     *     be overriden at the static $flagCodes property.
     * @param string $styles      Name of class that resolves styles to codes.
     *     Must inherit from {@link Styles}. Constants of this class are
     *     considered the valid styles, and the corresponding off/on codes must
     *     be overriden at the static $styleCodes property.
     * 
     * @return void
     */
    protected static function fillVlidators(
        $fonts,
        $backgrounds,
        $flags,
        $styles
    ) {
        if (empty(static::$validFonts)) {
            $fonts = new ReflectionClass($fonts);
            static::$validFonts = array_values(
                array_unique($fonts->getConstants(), SORT_REGULAR)
            );
        }

        if (empty(static::$validBackgorunds)) {
            $bgs = new ReflectionClass($backgrounds);
            static::$validBackgorunds = array_values(
                array_unique($bgs->getConstants(), SORT_REGULAR)
            );
        }

        if ('' === static::$flagsResolver) {
            $base = __CLASS__ . '\Flags';
            if ($base === $flags || is_subclass_of($flags, $base)) {
                static::$flagsResolver = $flags;
            }
        }

        if ('' === static::$stylesResolver) {
            $base = __CLASS__ . '\Styles';
            if ($base === $styles || is_subclass_of($styles, $base)) {
                static::$stylesResolver = $styles;
            }
        }
    }

    /**
     * Creates a new color.
     * 
     * Note that leaving all arguments with their default values (and not
     * applying styles) would result in a sequence that resets all settings to
     * the console's defaults.
     * 
     * @param int|null $font       Initial font color.
     * @param int|null $background Initial backgorund color.
     * @param int      $flags      Initial flags.
     * 
     * @see setFlags()
     * @see setStyles()
     * @see __toString()
     */
    public function __construct(
        $font = Fonts::KEEP,
        $background = Backgrounds::KEEP,
        $flags = Flags::NONE
    ) {
        static::fillVlidators(
            __CLASS__ . '\Fonts',
            __CLASS__ . '\Backgrounds',
            __CLASS__ . '\Flags',
            __CLASS__ . '\Styles'
        );
        $this->setFont($font);
        $this->setBackground($background);
        $this->setFlags($flags);
    }

    /**
     * Gets the font color.
     * 
     * @return int|null $color The font color.
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Sets the font color.
     * 
     * @param int|null $color The font color.
     * 
     * @return $this
     */
    public function setFont($color)
    {
        if (!in_array($color, static::$validFonts, true)) {
            throw new UnexpectedValueException(
                'Invalid font supplied.',
                UnexpectedValueException::CODE_FONT
            );
        }
        $this->font = $color;

        $this->sequence = null;
        return $this;
    }

    /**
     * Gets the background color.
     * 
     * @return int|null $color The background color.
     */
    public function getBackground()
    {
        return $this->backgorund;
    }

    /**
     * Sets the background color.
     * 
     * @param int|null $color The background color.
     * 
     * @return $this
     */
    public function setBackground($color)
    {
        if (!in_array($color, static::$validBackgorunds, true)) {
            throw new UnexpectedValueException(
                'Invalid background supplied.',
                UnexpectedValueException::CODE_BACKGROUND
            );
        }
        $this->backgorund = $color;

        $this->sequence = null;
        return $this;
    }

    /**
     * Gets the flags.
     * 
     * @return int The currently set flags.
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Sets the flags.
     * 
     * Sets the flags to apply in the sequence. Note that flags are applied
     * before all other settings, in ascending order of the constant values.
     * 
     * @param int $flags The new flags to set. Unknown flags will be ignored
     *     when forming the sequence, but will be visible with
     *     {@link getFlags()} non the less.
     * 
     * @return $this
     */
    public function setFlags($flags)
    {
        $this->flags = (int)$flags;

        $this->sequence = null;
        return $this;
    }

    /**
     * Gets styles.
     * 
     * @param int|null $style A single style to get the status of,
     *     or {@link Styles::ALL} to get all styles in an array.
     * 
     * @return bool|null|bool[] A single style status, or
     *     an array of status if $style is {@link Styles::ALL}.
     */
    public function getStyles($style = Styles::ALL)
    {
        if (Styles::ALL === $style) {
            return $this->styles;
        }
        return isset($this->styles[$style]) ? $this->styles[$style] : null;
    }

    /**
     * Sets styles.
     * 
     * Sets styles matched to a specified state.
     * 
     * @param int|null  $styles Bitmask of styles to set. You can also use the
     *     constant {@link Styles::ALL} (only) to set all known styles.
     *     Unknown styles will be ignored.
     * @param bool|null $state  The state to set the matched styles in.
     *     TRUE to enable them,
     *     FLASE to disable them,
     *     NULL to remove the setting for them (in effect using whatever the
     *     console had before the sequence was applied).
     * 
     * @return $this
     */
    public function setStyles($styles, $state)
    {
        $matchingStyles = call_user_func(
            array(static::$stylesResolver, 'match'),
            $styles
        );
        if (null === $state) {
            foreach ($matchingStyles as $style) {
                unset($this->styles[$style]);
            }
        } else {
            $state = (bool)$state;
            foreach ($matchingStyles as $style) {
                $this->styles[$style] = $state;
            }
            ksort($this->styles);
        }

        $this->sequence = null;
        return $this;
    }

    /**
     * Get the console escaping sequence.
     * 
     * This is a magic PHP method that will be called when you use the object in
     * a string context or otherwise explicitly cast it to a string.
     * 
     * It generates the escape sequence and returns it.
     * For the sake of performance, the escape sequence is cached, and is only
     * regenerated when a setter has been previously called.
     * 
     * @return string The string to write to console to get the specified
     *     styling.
     */
    public function __toString()
    {
        if (null === $this->sequence) {
            $seq = "\033[";

            $flags = implode(
                ';',
                call_user_func(
                    array(static::$flagsResolver, 'getCodes'),
                    $this->flags
                )
            );
            if ('' !== $flags) {
                $seq .= $flags . ';';
            }

            if (Fonts::KEEP !== $this->font) {
                $seq .= "{$this->font};";
            }
            if (Backgrounds::KEEP !== $this->backgorund) {
                $seq .= "{$this->backgorund};";
            }

            foreach ($this->styles as $style => $state) {
                $seq .= call_user_func(
                    array(static::$stylesResolver, 'getCode'),
                    $style,
                    $state
                ) . ';';
            }

            $this->sequence = rtrim($seq, ';') . 'm';
        }

        return $this->sequence;
    }
}
