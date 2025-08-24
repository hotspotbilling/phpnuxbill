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
 * Values at {@link Script::escapeValue()} can be casted from this type.
 */
use DateTime;

/**
 * Values at {@link Script::escapeValue()} can be casted from this type.
 */
use DateInterval;

/**
 * Used at {@link Script::escapeValue()} to get the proper time.
 */
use DateTimeZone;

/**
 * Used to reliably write to streams at {@link Script::prepare()}.
 */
use PEAR2\Net\Transmitter\Stream;

/**
 * Used to catch DateTime and DateInterval exceptions at
 * {@link Script::parseValue()}.
 */
use Exception as E;

/**
 * Scripting class.
 *
 * Provides functionality related to parsing and composing RouterOS scripts and
 * values.
 *
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Script
{
    /**
     * Parses a value from a RouterOS scripting context.
     *
     * Turns a value from RouterOS into an equivalent PHP value, based on
     * determining the type in the same way RouterOS would determine it for a
     * literal.
     *
     * This method is intended to be the very opposite of
     * {@link static::escapeValue()}. That is, results from that method, if
     * given to this method, should produce equivalent results.
     *
     * @param string            $value    The value to be parsed.
     *     Must be a literal of a value,
     *     e.g. what {@link static::escapeValue()} will give you.
     * @param DateTimeZone|null $timezone The timezone which any resulting
     *     DateTime object (either the main value, or values within an array)
     *     will use. Defaults to UTC.
     *
     * @return mixed Depending on RouterOS type detected:
     *     - "nil" (the string "[]") or "nothing" (empty string) - NULL.
     *     - "num" - int or double for large values.
     *     - "bool" - a boolean.
     *     - "array" - an array, with the keys and values processed recursively.
     *     - "time" - a {@link DateInterval} object.
     *     - "date" (pseudo type; string in the form "M/j/Y") - a DateTime
     *         object with the specified date, at midnight.
     *     - "datetime" (pseudo type; string in the form "M/j/Y H:i:s") - a
     *         DateTime object with the specified date and time.
     *     - "str" (a quoted string) - a string, with the contents escaped.
     *     - Unrecognized type - casted to a string, unmodified.
     */
    public static function parseValue($value, DateTimeZone $timezone = null)
    {
        $value = static::parseValueToSimple($value);
        if (!is_string($value)) {
            return $value;
        }

        try {
            return static::parseValueToArray($value, $timezone);
        } catch (ParserException $e) {
            try {
                return static::parseValueToDateInterval($value);
            } catch (ParserException $e) {
                try {
                    return static::parseValueToDateTime($value, $timezone);
                } catch (ParserException $e) {
                    return static::parseValueToString($value);
                }
            }
        }
    }

    /**
     * Parses a RouterOS value into a PHP string.
     *
     * @param string $value The value to be parsed.
     *     Must be a literal of a value,
     *     e.g. what {@link static::escapeValue()} will give you.
     *
     * @return string If a quoted string is provided, it would be parsed.
     *     Otherwise, the value is casted to a string, and returned unmodified.
     */
    public static function parseValueToString($value)
    {
        $value = (string)$value;
        if ('"' === $value[0] && '"' === $value[strlen($value) - 1]) {
            return str_replace(
                array('\"', '\\\\', "\\\n", "\\\r\n", "\\\r"),
                array('"', '\\'),
                substr($value, 1, -1)
            );
        }
        return $value;
    }

    /**
     * Parses a RouterOS value into a PHP simple type.
     *
     * Parses a RouterOS value into a PHP simple type. "Simple" types being
     * scalar types, plus NULL.
     *
     * @param string $value The value to be parsed. Must be a literal of a
     *     value, e.g. what {@link static::escapeValue()} will give you.
     *
     * @return string|bool|int|double|null Depending on RouterOS type detected:
     *     - "nil" (the string "[]") or "nothing" (empty string) - NULL.
     *     - "num" - int or double for large values.
     *     - "bool" - a boolean.
     *     - Unrecognized type - casted to a string, unmodified.
     */
    public static function parseValueToSimple($value)
    {
        $value = (string)$value;

        if (in_array($value, array('', '[]'), true)) {
            return null;
        } elseif (in_array($value, array('true', 'false', 'yes', 'no'), true)) {
            return $value === 'true' || $value === 'yes';
        } elseif ($value === (string)($num = (int)$value)
            || $value === (string)($num = (double)$value)
        ) {
            return $num;
        }
        return $value;
    }

    /**
     * Parses a RouterOS value into a PHP DateTime object
     *
     * Parses a RouterOS value into a PHP DateTime object.
     *
     * @param string            $value    The value to be parsed.
     *     Must be a literal of a value,
     *     e.g. what {@link static::escapeValue()} will give you.
     * @param DateTimeZone|null $timezone The timezone which the resulting
     *     DateTime object will use. Defaults to UTC.
     *
     * @return DateTime Depending on RouterOS type detected:
     *     - "date" (pseudo type; string in the form "M/j/Y") - a DateTime
     *         object with the specified date, at midnight UTC time (regardless
     *         of timezone provided).
     *     - "datetime" (pseudo type; string in the form "M/j/Y H:i:s") - a
     *         DateTime object with the specified date and time,
     *         with the specified timezone.
     *
     * @throws ParserException When the value is not of a recognized type.
     */
    public static function parseValueToDateTime(
        $value,
        DateTimeZone $timezone = null
    ) {
        $previous = null;
        $value = (string)$value;
        if ('' !== $value && preg_match(
            '#^
                (?<mon>jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)
                /
                (?<day>\d\d?)
                /
                (?<year>\d{4})
                (?:
                    \s+(?<time>\d{2}\:\d{2}:\d{2})
                )?
            $#uix',
            $value,
            $date
        )) {
            if (!isset($date['time'])) {
                $date['time'] = '00:00:00';
                $timezone = new DateTimeZone('UTC');
            } elseif (null === $timezone) {
                $timezone = new DateTimeZone('UTC');
            }
            try {
                return new DateTime(
                    $date['year'] .
                    '-' . ucfirst($date['mon']) .
                    "-{$date['day']} {$date['time']}",
                    $timezone
                );
            } catch (E $e) {
                $previous = $e;
            }
        }
        throw new ParserException(
            'The supplied value can not be converted to a DateTime',
            ParserException::CODE_DATETIME,
            $previous
        );
    }

    /**
     * Parses a RouterOS value into a PHP DateInterval.
     *
     * Parses a RouterOS value into a PHP DateInterval.
     *
     * @param string $value The value to be parsed. Must be a literal of a
     *     value, e.g. what {@link static::escapeValue()} will give you.
     *
     * @return DateInterval The value as a DateInterval object.
     *
     * @throws ParserException When the value is not of a recognized type.
     */
    public static function parseValueToDateInterval($value)
    {
        $value = (string)$value;
        if ('' !== $value && preg_match(
            '/^
                (?:(\d+)w)?
                (?:(\d+)d)?
                (?:(\d+)(?:\:|h))?
                (?|
                    (\d+)\:
                    (\d*(?:\.\d{1,9})?)
                |
                    (?:(\d+)m)?
                    (?:(\d+|\d*\.\d{1,9})s)?
                    (?:((?5))ms)?
                    (?:((?5))us)?
                    (?:((?5))ns)?
                )
            $/x',
            $value,
            $time
        )) {
            $days = isset($time[2]) ? (int)$time[2] : 0;
            if (isset($time[1])) {
                $days += 7 * (int)$time[1];
            }
            if (empty($time[3])) {
                $time[3] = 0;
            }
            if (empty($time[4])) {
                $time[4] = 0;
            }
            if (empty($time[5])) {
                $time[5] = 0;
            }

            $subsecondTime = 0.0;
            //@codeCoverageIgnoreStart
            // No PHP version currently supports sub-second DateIntervals,
            // meaning this section is untestable, since no version constraints
            // can be specified for test inputs.
            // All inputs currently use integer seconds only, making this
            // section unreachable during tests.
            // Nevertheless, this section exists right now, in order to provide
            // such support as soon as PHP has it.
            if (!empty($time[6])) {
                $subsecondTime += ((double)$time[6]) / 1000;
            }
            if (!empty($time[7])) {
                $subsecondTime += ((double)$time[7]) / 1000000;
            }
            if (!empty($time[8])) {
                $subsecondTime += ((double)$time[8]) / 1000000000;
            }
            //@codeCoverageIgnoreEnd

            $secondsSpec = $time[5] + $subsecondTime;
            try {
                return new DateInterval(
                    "P{$days}DT{$time[3]}H{$time[4]}M{$secondsSpec}S"
                );
                //@codeCoverageIgnoreStart
                // See previous ignored section's note.
                //
                // This section is added for backwards compatibility with current
                // PHP versions, when in the future sub-second support is added.
                // In that event, the test inputs for older versions will be
                // expected to get a rounded up result of the sub-second data.
            } catch (E $e) {
                $secondsSpec = (int)round($secondsSpec);
                return new DateInterval(
                    "P{$days}DT{$time[3]}H{$time[4]}M{$secondsSpec}S"
                );
            }
            //@codeCoverageIgnoreEnd
        }
        throw new ParserException(
            'The supplied value can not be converted to DateInterval',
            ParserException::CODE_DATEINTERVAL
        );
    }

    /**
     * Parses a RouterOS value into a PHP array.
     *
     * Parses a RouterOS value into a PHP array.
     *
     * @param string            $value    The value to be parsed.
     *     Must be a literal of a value,
     *     e.g. what {@link static::escapeValue()} will give you.
     * @param DateTimeZone|null $timezone The timezone which any resulting
     *     DateTime object within the array will use. Defaults to UTC.
     *
     * @return array An array, with the keys and values processed recursively,
     *         the keys with {@link static::parseValueToSimple()},
     *         and the values with {@link static::parseValue()}.
     *
     * @throws ParserException When the value is not of a recognized type.
     */
    public static function parseValueToArray(
        $value,
        DateTimeZone $timezone = null
    ) {
        $value = (string)$value;
        if ('{' === $value[0] && '}' === $value[strlen($value) - 1]) {
            $value = substr($value, 1, -1);
            if ('' === $value) {
                return array();
            }
            $parsedValue = preg_split(
                '/
                    (\"(?:\\\\\\\\|\\\\"|[^"])*\")
                    |
                    (\{[^{}]*(?2)?\})
                    |
                    ([^;=]+)
                /sx',
                $value,
                null,
                PREG_SPLIT_DELIM_CAPTURE
            );
            $result = array();
            $newVal = null;
            $newKey = null;
            for ($i = 0, $l = count($parsedValue); $i < $l; ++$i) {
                switch ($parsedValue[$i]) {
                case '':
                    break;
                case ';':
                    if (null === $newKey) {
                        $result[] = $newVal;
                    } else {
                        $result[$newKey] = $newVal;
                    }
                    $newKey = $newVal = null;
                    break;
                case '=':
                    $newKey = static::parseValueToSimple($parsedValue[$i - 1]);
                    $newVal = static::parseValue($parsedValue[++$i], $timezone);
                    break;
                default:
                    $newVal = static::parseValue($parsedValue[$i], $timezone);
                }
            }
            if (null === $newKey) {
                $result[] = $newVal;
            } else {
                $result[$newKey] = $newVal;
            }
            return $result;
        }
        throw new ParserException(
            'The supplied value can not be converted to an array',
            ParserException::CODE_ARRAY
        );
    }

    /**
     * Prepares a script.
     *
     * Prepares a script for eventual execution by prepending parameters as
     * variables to it.
     *
     * This is particularly useful when you're creating scripts that you don't
     * want to execute right now (as with {@link Util::exec()}, but instead
     * you want to store it for later execution, perhaps by supplying it to
     * "/system scheduler".
     *
     * @param string|resource         $source The source of the script,
     *     as a string or stream. If a stream is provided, reading starts from
     *     the current position to the end of the stream, and the pointer stays
     *     at the end after reading is done.
     * @param array<string|int,mixed> $params An array of parameters to make
     *     available in the script as local variables.
     *     Variable names are array keys, and variable values are array values.
     *     Array values are automatically processed with
     *     {@link static::escapeValue()}. Streams are also supported, and are
     *     processed in chunks, each with
     *     {@link static::escapeString()} with all bytes being escaped.
     *     Processing starts from the current position to the end of the stream,
     *     and the stream's pointer is left untouched after the reading is done.
     *     Variables with a value of type "nothing" can be declared with a
     *     numeric array key and the variable name as the array value
     *     (that is casted to a string).
     *
     * @return resource A new PHP temporary stream with the script as contents,
     *     with the pointer back at the start.
     *
     * @see static::append()
     */
    public static function prepare(
        $source,
        array $params = array()
    ) {
        $resultStream = fopen('php://temp', 'r+b');
        static::append($resultStream, $source, $params);
        rewind($resultStream);
        return $resultStream;
    }

    /**
     * Appends a script.
     *
     * Appends a script to an existing stream.
     *
     * @param resource                $stream An existing stream to write the
     *     resulting script to.
     * @param string|resource         $source The source of the script,
     *     as a string or stream. If a stream is provided, reading starts from
     *     the current position to the end of the stream, and the pointer stays
     *     at the end after reading is done.
     * @param array<string|int,mixed> $params An array of parameters to make
     *     available in the script as local variables.
     *     Variable names are array keys, and variable values are array values.
     *     Array values are automatically processed with
     *     {@link static::escapeValue()}. Streams are also supported, and are
     *     processed in chunks, each with
     *     {@link static::escapeString()} with all bytes being escaped.
     *     Processing starts from the current position to the end of the stream,
     *     and the stream's pointer is left untouched after the reading is done.
     *     Variables with a value of type "nothing" can be declared with a
     *     numeric array key and the variable name as the array value
     *     (that is casted to a string).
     *
     * @return int The number of bytes written to $stream is returned,
     *     and the pointer remains where it was after the write
     *     (i.e. it is not seeked back, even if seeking is supported).
     */
    public static function append(
        $stream,
        $source,
        array $params = array()
    ) {
        $writer = new Stream($stream, false);
        $bytes = 0;

        foreach ($params as $pname => $pvalue) {
            if (is_int($pname)) {
                $pvalue = static::escapeString((string)$pvalue);
                $bytes += $writer->send(":local \"{$pvalue}\";\n");
                continue;
            }
            $pname = static::escapeString($pname);
            $bytes += $writer->send(":local \"{$pname}\" ");
            if (Stream::isStream($pvalue)) {
                $reader = new Stream($pvalue, false);
                $chunkSize = $reader->getChunk(Stream::DIRECTION_RECEIVE);
                $bytes += $writer->send('"');
                while ($reader->isAvailable() && $reader->isDataAwaiting()) {
                    $bytes += $writer->send(
                        static::escapeString(fread($pvalue, $chunkSize), true)
                    );
                }
                $bytes += $writer->send("\";\n");
            } else {
                $bytes += $writer->send(static::escapeValue($pvalue) . ";\n");
            }
        }

        $bytes += $writer->send($source);
        return $bytes;
    }

    /**
     * Escapes a value for a RouterOS scripting context.
     *
     * Turns any native PHP value into an equivalent whole value that can be
     * inserted as part of a RouterOS script.
     *
     * DateInterval objects will be casted to RouterOS' "time" type.
     *
     * DateTime objects will be casted to a string following the "M/d/Y H:i:s"
     * format. If the time is exactly midnight (including microseconds), and
     * the timezone is UTC, the string will include only the "M/d/Y" date.
     *
     * Unrecognized types (i.e. resources and other objects) are casted to
     * strings, and those strings are then escaped.
     *
     * @param mixed $value The value to be escaped.
     *
     * @return string A string representation that can be directly inserted in a
     *     script as a whole value.
     */
    public static function escapeValue($value)
    {
        switch(gettype($value)) {
        case 'NULL':
            $value = '[]';
            break;
        case 'integer':
            $value = (string)$value;
            break;
        case 'boolean':
            $value = $value ? 'true' : 'false';
            break;
        case 'array':
            if (0 === count($value)) {
                $value = '({})';
                break;
            }
            $result = '';
            foreach ($value as $key => $val) {
                $result .= ';';
                if (!is_int($key)) {
                    $result .= static::escapeValue($key) . '=';
                }
                $result .= static::escapeValue($val);
            }
            $value = '{' . substr($result, 1) . '}';
            break;
        case 'object':
            if ($value instanceof DateTime) {
                $usec = $value->format('u');
                $usec = '000000' === $usec ? '' : '.' . $usec;
                $value = '00:00:00.000000 UTC' === $value->format('H:i:s.u e')
                    ? $value->format('M/d/Y')
                    : $value->format('M/d/Y H:i:s') . $usec;
            }
            if ($value instanceof DateInterval) {
                if (false === $value->days || $value->days < 0) {
                    $value = $value->format('%r%dd%H:%I:%S');
                } else {
                    $value = $value->format('%r%ad%H:%I:%S');
                }
                break;
            }
            //break; intentionally omitted
        default:
            $value = '"' . static::escapeString((string)$value) . '"';
            break;
        }
        return $value;
    }

    /**
     * Escapes a string for a RouterOS scripting context.
     *
     * Escapes a string for a RouterOS scripting context. The value can then be
     * surrounded with quotes at a RouterOS script (or concatenated onto a
     * larger string first), and you can be sure there won't be any code
     * injections coming from it.
     *
     * By default, for the sake of brevity of the output, ASCII alphanumeric
     * characters and underscores are left untouched. And for the sake of
     * character conversion, bytes above 0x7F are also left untouched.
     *
     * @param string $value Value to be escaped.
     * @param bool   $full  Whether to escape all bytes in the string, including
     *     ASCII alphanumeric characters, underscores and bytes above 0x7F.
     *
     * @return string The escaped value.
     *
     * @internal Why leave ONLY those ASCII characters and not also others?
     *     Because those can't in any way be mistaken for language constructs,
     *     unlike many other "safe inside strings, but not outside" ASCII
     *     characters, like ",", ".", "+", "-", "~", etc.
     */
    public static function escapeString($value, $full = false)
    {
        if ($full) {
            return self::_escapeCharacters(array($value));
        }
        return preg_replace_callback(
            '/[^\\_A-Za-z0-9\\x80-\\xFF]+/S',
            array(__CLASS__, '_escapeCharacters'),
            $value
        );
    }

    /**
     * Escapes a character for a RouterOS scripting context.
     *
     * Escapes a character for a RouterOS scripting context.
     * Intended to only be called by {@link self::escapeString()} for the
     * matching strings.
     *
     * @param array $chars The matches array, expected to contain exactly one
     *     member, in which is the whole string to be escaped.
     *
     * @return string The escaped characters.
     */
    private static function _escapeCharacters(array $chars)
    {
        $result = '';
        for ($i = 0, $l = strlen($chars[0]); $i < $l; ++$i) {
            $result .= '\\' . str_pad(
                strtoupper(dechex(ord($chars[0][$i]))),
                2,
                '0',
                STR_PAD_LEFT
            );
        }
        return $result;
    }
}
