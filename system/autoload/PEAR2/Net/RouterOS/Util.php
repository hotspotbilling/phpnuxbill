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

/**
 * Values at {@link Util::exec()} can be casted from this type.
 */
use DateTime;

/**
 * Values at {@link Util::exec()} can be casted from this type.
 */
use DateInterval;

/**
 * Implemented by this class.
 */
use Countable;

/**
 * Used to reliably write to streams at {@link static::prepareScript()}.
 */
use PEAR2\Net\Transmitter\Stream;

/**
 * Utility class.
 * 
 * Abstracts away frequently used functionality (particularly CRUD operations)
 * in convinient to use methods by wrapping around a connection.
 * 
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Util implements Countable
{
    /**
     * @var Client The connection to wrap around.
     */
    protected $client;

    /**
     * @var string The current menu.
     */
    protected $menu = '/';

    /**
     * @var array|null An array with the numbers of items in the current menu as
     *     keys, and the corresponding IDs as values. NULL when the cache needs
     *     regenerating.
     */
    protected $idCache = null;

    /**
     * Parses a value from a RouterOS scripting context.
     * 
     * Turns a value from RouterOS into an equivalent PHP value, based on
     * determining the type in the same way RouterOS would determine it for a
     * literal.
     * 
     * This method is intended to be the very opposite of
     * {@link static::escapeValue()}. hat is, results from that method, if
     * given to this method, should produce equivalent results.
     * 
     * @param string $value The value to be parsed. Must be a literal of a
     *     value, e.g. what {@link static::escapeValue()} will give you.
     * 
     * @return mixed Depending on RouterOS type detected:
     *     - "nil" or "nothing" - NULL.
     *     - "number" - int or double for large values.
     *     - "bool" - a boolean.
     *     - "time" - a {@link DateInterval} object.
     *     - "array" - an array, with the values processed recursively.
     *     - "str" - a string.
     *     - Unrecognized type - treated as an unquoted string.
     */
    public static function parseValue($value)
    {
        $value = (string)$value;
        
        if (in_array($value, array('', 'nil'), true)) {
            return null;
        } elseif (in_array($value, array('true', 'false', 'yes', 'no'), true)) {
            return $value === 'true' || $value === 'yes';
        } elseif ($value === (string)($num = (int)$value)
            || $value === (string)($num = (double)$value)
        ) {
            return $num;
        } elseif (preg_match(
            '/^
               (?:(\d+)w)?
               (?:(\d+)d)?
               (?:(\d\d)\:)?
               (\d\d)\:
               (\d\d(:\.\d{1,6})?)
            $/x',
            $value,
            $time
        )) {
            $days = isset($time[2]) ? (int)$time[2] : 0;
            if (isset($time[1])) {
                $days += 7 * (int)$time[1];
            }
            if ('' === $time[3]) {
                $time[3] = 0;
            }
            return new DateInterval(
                "P{$days}DT{$time[3]}H{$time[4]}M{$time[5]}S"
            );
        } elseif (('"' === $value[0]) && substr(strrev($value), 0, 1) === '"') {
            return str_replace(
                array('\"', '\\\\', "\\\n", "\\\r\n", "\\\r"),
                array('"', '\\'),
                substr($value, 1, -1)
            );
        } elseif ('{' === $value[0]) {
            $len = strlen($value);
            if ($value[$len - 1] === '}') {
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
                        $newKey = static::parseValue($parsedValue[$i - 1]);
                        $newVal = static::parseValue($parsedValue[++$i]);
                        break;
                    default:
                        $newVal = static::parseValue($parsedValue[$i]);
                    }
                }
                if (null === $newKey) {
                    $result[] = $newVal;
                } else {
                    $result[$newKey] = $newVal;
                }
                return $result;
            }
        }
        return $value;
    }

    /**
     * Prepares a script.
     * 
     * Prepares a script for eventual execution by prepending parameters as
     * variables to it.
     * 
     * This is particularly useful when you're creating scripts that you don't
     * want to execute right now (as with {@link static::exec()}, but instead
     * you want to store it for later execution, perhaps by supplying it to
     * "/system scheduler".
     * 
     * @param string|resource $source The source of the script, as a string
     *     or stream. If a stream is provided, reading starts from the current
     *     position to the end of the stream, and the pointer stays at the end
     *     after reading is done.
     * @param array           $params An array of parameters to make available
     *     in the script as local variables.
     *     Variable names are array keys, and variable values are array values.
     *     Array values are automatically processed with
     *     {@link static::escapeValue()}. Streams are also supported, and are
     *     processed in chunks, each with
     *     {@link static::escapeString()}. Processing starts from the current
     *     position to the end of the stream, and the stream's pointer stays at
     *     the end after reading is done.
     * 
     * @return resource A new PHP temporary stream with the script as contents,
     *     with the pointer back at the start.
     * @see static::appendScript()
     */
    public static function prepareScript(
        $source,
        array $params = array()
    ) {
        $resultStream = fopen('php://temp', 'r+b');
        self::appendScript($resultStream, $source, $params);
        rewind($resultStream);
        return $resultStream;
    }

    /**
     * Appends a script.
     * 
     * Appends a script to an existing stream.
     * 
     * @param resource        $stream An existing stream to write the resulting
     *     script to.
     * @param string|resource $source The source of the script, as a string
     *     or stream. If a stream is provided, reading starts from the current
     *     position to the end of the stream, and the pointer stays at the end
     *     after reading is done.
     * @param array           $params An array of parameters to make available
     *     in the script as local variables.
     *     Variable names are array keys, and variable values are array values.
     *     Array values are automatically processed with
     *     {@link static::escapeValue()}. Streams are also supported, and are
     *     processed in chunks, each with
     *     {@link static::escapeString()}. Processing starts from the current
     *     position to the end of the stream, and the stream's pointer stays at
     *     the end after reading is done.
     * 
     * @return int The number of bytes written to $stream is returned,
     *     and the pointer remains where it was after the write
     *     (i.e. it is not seeked back, even if seeking is supported).
     */
    public static function appendScript(
        $stream,
        $source,
        array $params = array()
    ) {
        $writer = new Stream($stream, false);
        $bytes = 0;

        foreach ($params as $pname => $pvalue) {
            $pname = static::escapeString($pname);
            $bytes += $writer->send(":local \"{$pname}\" ");
            if (Stream::isStream($pvalue)) {
                $reader = new Stream($pvalue, false);
                $chunkSize = $reader->getChunk(Stream::DIRECTION_RECEIVE);
                $bytes += $writer->send('"');
                while ($reader->isAvailable() && $reader->isDataAwaiting()) {
                    $bytes += $writer->send(
                        static::escapeString(fread($pvalue, $chunkSize))
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
     * DateTime and DateInterval objects will be casted to RouterOS' "time"
     * type. A DateTime object will be converted to a time relative to the UNIX
     * epoch time. Note that if a DateInterval does not have the "days" property
     * ("a" in formatting), then its months and years will be ignored, because
     * they can't be unambigiously converted to a "time" value.
     * 
     * Unrecognized types (i.e. resources and other objects) are casted to
     * strings.
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
            $value = '';
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
                if ('000000' === $usec) {
                    unset($usec);
                }
                $unixEpoch = new DateTime('@0');
                $value = $unixEpoch->diff($value);
            }
            if ($value instanceof DateInterval) {
                if (false === $value->days || $value->days < 0) {
                    $value = $value->format('%r%dd%H:%I:%S');
                } else {
                    $value = $value->format('%r%ad%H:%I:%S');
                }
                if (strpos('.', $value) === false && isset($usec)) {
                    $value .= '.' . $usec;
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
     * @param string $value Value to be escaped.
     * 
     * @return string The escaped value.
     */
    public static function escapeString($value)
    {
        return preg_replace_callback(
            '/[^\\_A-Za-z0-9]+/S',
            array(__CLASS__, '_escapeCharacters'),
            $value
        );
    }
    
    /**
     * Escapes a character for a RouterOS scripting context.
     * 
     * Escapes a character for a RouterOS scripting context. Intended to only be
     * called for non-alphanumeric characters.
     * 
     * @param string $chars The matches array, expected to contain exactly one
     *     member, in which is the whole string to be escaped.
     * 
     * @return string The escaped characters.
     */
    private static function _escapeCharacters($chars)
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

    /**
     * Creates a new Util instance.
     * 
     * Wraps around a connection to provide convinience methods.
     * 
     * @param Client $client The connection to wrap around.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Gets the current menu.
     * 
     * @return string The current menu.
     */
    public function getMenu()
    {
        return $this->menu;
    }
    
    /**
     * Sets the current menu.
     * 
     * Sets the current menu.
     * 
     * @param string $newMenu The menu to change to. Can be specified with API
     *     or CLI syntax and can be either absolute or relative. If relative,
     *     it's relative to the current menu, which by default is the root.
     * 
     * @return $this The object itself. If an empty string is given for
     *     a new menu, no change is performed,
     *     but the ID cache is cleared anyway.
     * 
     * @see static::clearIdCache()
     */
    public function setMenu($newMenu)
    {
        $newMenu = (string)$newMenu;
        if ('' !== $newMenu) {
            $menuRequest = new Request('/menu');
            if ('/' === $newMenu[0]) {
                $this->menu = $menuRequest->setCommand($newMenu)->getCommand();
            } else {
                $this->menu = $menuRequest->setCommand(
                    '/' . str_replace('/', ' ', substr($this->menu, 1)) . ' ' .
                    str_replace('/', ' ', $newMenu)
                )->getCommand();
            }
        }
        $this->clearIdCache();
        return $this;
    }

    /**
     * Executes a RouterOS script.
     * 
     * Executes a RouterOS script, written as a string or a stream.
     * Note that in cases of errors, the line numbers will be off, because the
     * script is executed at the current menu as context, with the specified
     * variables pre declared. This is achieved by prepending 1+count($params)
     * lines before your actual script.
     * 
     * @param string|resource $source The source of the script, as a string or
     *     stream. If a stream is provided, reading starts from the current
     *     position to the end of the stream, and the pointer stays at the end
     *     after reading is done.
     * @param array           $params An array of parameters to make available
     *     in the script as local variables.
     *     Variable names are array keys, and variable values are array values.
     *     Array values are automatically processed with
     *     {@link static::escapeValue()}. Streams are also supported, and are
     *     processed in chunks, each processed with
     *     {@link static::escapeString()}. Processing starts from the current
     *     position to the end of the stream, and the stream's pointer is left
     *     untouched after the reading is done.
     *     Note that the script's (generated) name is always added as the
     *     variable "_", which will be inadvertently lost if you overwrite it
     *     from here.
     * @param string          $policy Allows you to specify a policy the script
     *     must follow. Has the same format as in terminal.
     *     If left NULL, the script has no restrictions beyond those imposed by
     *     the username.
     * @param string          $name   The script is executed after being saved
     *     in "/system script" under a random name (prefixed with the computer's
     *     name), and is removed after execution. To eliminate any possibility
     *     of name clashes, you can specify your own name.
     * 
     * @return ResponseCollection Returns the response collection of the
     *     run, allowing you to inspect errors, if any.
     *     If the script was not added successfully before execution, the
     *     ResponseCollection from the add attempt is going to be returned.
     */
    public function exec(
        $source,
        array $params = array(),
        $policy = null,
        $name = null
    ) {
        return $this->_exec($source, $params, $policy, $name);
    }
    
    /**
     * Clears the ID cache.
     * 
     * Normally, the ID cache improves performance when targeting items by a
     * number. If you're using both Util's methods and other means (e.g.
     * {@link Client} or {@link Util::exec()}) to add/move/remove items, the
     * cache may end up being out of date. By calling this method right before
     * targeting an item with a number, you can ensure number accuracy.
     * 
     * Note that Util's {@link static::move()} and {@link static::remove()}
     * methods automatically clear the cache before returning, while
     * {@link static::add()} adds the new item's ID to the cache as the next
     * number. A change in the menu also clears the cache.
     * 
     * Note also that the cache is being rebuilt unconditionally every time you
     * use {@link static::find()} with a callback.
     * 
     * @return $this The Util object itself.
     */
    public function clearIdCache()
    {
        $this->idCache = null;
        return $this;
    }

    /**
     * Finds the IDs of items at the current menu.
     * 
     * Finds the IDs of items based on specified criteria, and returns them as
     * a comma separated string, ready for insertion at a "numbers" argument.
     * 
     * Accepts zero or more criteria as arguments. If zero arguments are
     * specified, returns all items' IDs. The value of each criteria can be a
     * number (just as in Winbox), a literal ID to be included, a {@link Query}
     * object, or a callback. If a callback is specified, it is called for each
     * item, with the item as an argument. If it returns a true value, the
     * item's ID is included in the result. Every other value is casted to a
     * string. A string is treated as a comma separated values of IDs, numbers
     * or callback names. Non-existent callback names are instead placed in the
     * result, which may be useful in menus that accept identifiers other than
     * IDs, but note that it can cause errors on other menus.
     * 
     * @return string A comma separated list of all items matching the
     *     specified criteria.
     */
    public function find()
    {
        if (func_num_args() === 0) {
            if (null === $this->idCache) {
                $idCache = str_replace(
                    ';',
                    ',',
                    $this->client->sendSync(
                        new Request($this->menu . '/find')
                    )->getProperty('ret')
                );
                $this->idCache = explode(',', $idCache);
                return $idCache;
            }
            return implode(',', $this->idCache);
        }
        $idList = '';
        foreach (func_get_args() as $criteria) {
            if ($criteria instanceof Query) {
                foreach ($this->client->sendSync(
                    new Request($this->menu . '/print .proplist=.id', $criteria)
                ) as $response) {
                    $idList .= $response->getProperty('.id') . ',';
                }
            } elseif (is_callable($criteria)) {
                $idCache = array();
                foreach ($this->client->sendSync(
                    new Request($this->menu . '/print')
                ) as $response) {
                    if ($criteria($response)) {
                        $idList .= $response->getProperty('.id') . ',';
                    }
                    $idCache[] = $response->getProperty('.id');
                }
                $this->idCache = $idCache;
            } else {
                $this->find();
                if (is_int($criteria)) {
                    if (isset($this->idCache[$criteria])) {
                        $idList = $this->idCache[$criteria] . ',';
                    }
                } else {
                    $criteria = (string)$criteria;
                    if ($criteria === (string)(int)$criteria) {
                        if (isset($this->idCache[(int)$criteria])) {
                            $idList .= $this->idCache[(int)$criteria] . ',';
                        }
                    } elseif (false === strpos($criteria, ',')) {
                        $idList .= $criteria . ',';
                    } else {
                        $criteriaArr = explode(',', $criteria);
                        for ($i = count($criteriaArr) - 1; $i >= 0; --$i) {
                            if ('' === $criteriaArr[$i]) {
                                unset($criteriaArr[$i]);
                            } elseif ('*' === $criteriaArr[$i][0]) {
                                $idList .= $criteriaArr[$i] . ',';
                                unset($criteriaArr[$i]);
                            }
                        }
                        if (!empty($criteriaArr)) {
                            $idList .= call_user_func_array(
                                array($this, 'find'),
                                $criteriaArr
                            ) . ',';
                        }
                    }
                }
            }
        }
        return rtrim($idList, ',');
    }

    /**
     * Gets a value of a specified item at the current menu.
     * 
     * @param int|string|null $number    A number identifying the item you're
     *     targeting. Can also be an ID or (in some menus) name. For menus where
     *     there are no items (e.g. "/system identity"), you can specify NULL.
     * @param string          $valueName The name of the value you want to get.
     * 
     * @return string|null|bool The value of the specified property. If the
     *     property is not set, NULL will be returned. FALSE on failure
     *     (e.g. no such item, invalid property, etc.).
     */
    public function get($number, $valueName)
    {
        if (is_int($number) || ((string)$number === (string)(int)$number)) {
            $this->find();
            if (isset($this->idCache[(int)$number])) {
                $number = $this->idCache[(int)$number];
            } else {
                return false;
            }
        }

        //For new RouterOS versions
        $request = new Request($this->menu . '/get');
        $request->setArgument('number', $number);
        $request->setArgument('value-name', $valueName);
        $responses = $this->client->sendSync($request);
        if (Response::TYPE_ERROR === $responses->getType()) {
            return false;
        }
        $result = $responses->getProperty('ret');
        if (null !== $result) {
            return $result;
        }

        // The "get" of old RouterOS versions returns an empty !done response.
        // New versions return such only when the property is not set.
        // This is a backup for old versions' sake.
        $query = null;
        if (null !== $number) {
            $number = (string)$number;
            $query = Query::where('.id', $number)->orWhere('name', $number);
        }
        $responses = $this->getAll(
            array('.proplist' => $valueName, 'detail'),
            $query
        );

        if (0 === count($responses)) {
            // @codeCoverageIgnoreStart
            // New versions of RouterOS can't possibly reach this section.
            return false;
            // @codeCoverageIgnoreEnd
        }
        return $responses->getProperty($valueName);
    }

    /**
     * Enables all items at the current menu matching certain criteria.
     * 
     * Zero or more arguments can be specified, each being a criteria.
     * If zero arguments are specified, enables all items.
     * See {@link static::find()} for a description of what criteria are
     * accepted.
     * 
     * @return ResponseCollection returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    public function enable()
    {
        return $this->doBulk('enable', func_get_args());
    }

    /**
     * Disables all items at the current menu matching certain criteria.
     * 
     * Zero or more arguments can be specified, each being a criteria.
     * If zero arguments are specified, disables all items.
     * See {@link static::find()} for a description of what criteria are
     * accepted.
     * 
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    public function disable()
    {
        return $this->doBulk('disable', func_get_args());
    }

    /**
     * Removes all items at the current menu matching certain criteria.
     * 
     * Zero or more arguments can be specified, each being a criteria.
     * If zero arguments are specified, removes all items.
     * See {@link static::find()} for a description of what criteria are
     * accepted.
     * 
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    public function remove()
    {
        $result = $this->doBulk('remove', func_get_args());
        $this->clearIdCache();
        return $result;
    }

    /**
     * Sets new values.
     * 
     * Sets new values on certain properties on all items at the current menu
     * which match certain criteria.
     * 
     * @param mixed $numbers   Targeted items. Can be any criteria accepted by
     *     {@link static::find()} or NULL in case the menu is one without items
     *     (e.g. "/system identity").
     * @param array $newValues An array with the names of each property to set
     *     as an array key, and the new value as an array value.
     *     Flags (properties with a value "true" that is interpreted as
     *     equivalent of "yes" from CLI) can also be specified with a numeric
     *     index as the array key, and the name of the flag as the array value.
     * 
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    public function set($numbers, array $newValues)
    {
        $setRequest = new Request($this->menu . '/set');
        foreach ($newValues as $name => $value) {
            if (is_int($name)) {
                $setRequest->setArgument($value, 'true');
            } else {
                $setRequest->setArgument($name, $value);
            }
        }
        if (null !== $numbers) {
            $setRequest->setArgument('numbers', $this->find($numbers));
        }
        return $this->client->sendSync($setRequest);
    }

    /**
     * Alias of {@link static::set()}
     * 
     * @param mixed $numbers   Targeted items. Can be any criteria accepted by
     *     {@link static::find()}.
     * @param array $newValues An array with the names of each changed property
     *     as an array key, and the new value as an array value.
     *     Flags (properties with a value "true" that is interpreted as
     *     equivalent of "yes" from CLI) can also be specified with a numeric
     *     index as the array key, and the name of the flag as the array value.
     * 
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    public function edit($numbers, array $newValues)
    {
        return $this->set($numbers, $newValues);
    }

    /**
     * Unsets a value of a specified item at the current menu.
     * 
     * Equivalent of scripting's "unset" command. The "Value" part in the method
     * name is added because "unset" is a language construct, and thus a
     * reserved word.
     * 
     * @param mixed  $numbers   Targeted items. Can be any criteria accepted
     *     by {@link static::find()}.
     * @param string $valueName The name of the value you want to unset.
     * 
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    public function unsetValue($numbers, $valueName)
    {
        $unsetRequest = new Request($this->menu . '/unset');
        return $this->client->sendSync(
            $unsetRequest->setArgument('numbers', $this->find($numbers))
                ->setArgument('value-name', $valueName)
        );
    }

    /**
     * Adds a new item at the current menu.
     * 
     * @param array $values Accepts one or more items to add to the
     *     current menu. The data about each item is specified as an array with
     *     the names of each property as an array key, and the value as an array
     *     value.
     *     Flags (properties with a value "true" that is interpreted as
     *     equivalent of "yes" from CLI) can also be specified with a numeric
     *     index as the array key, and the name of the flag as the array value.
     * @param array $...    Additional items.
     * 
     * @return string A comma separated list of the new items' IDs. If a
     *     particular item was not added, this will be indicated by an empty
     *     string in its spot on the list. e.g. "*1D,,*1E" means that
     *     you supplied three items to be added, of which the second one was
     *     not added for some reason.
     */
    public function add(array $values)
    {
        $addRequest = new Request($this->menu . '/add');
        $idList = '';
        foreach (func_get_args() as $values) {
            $idList .= ',';
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $name => $value) {
                if (is_int($name)) {
                    $addRequest->setArgument($value, 'true');
                } else {
                    $addRequest->setArgument($name, $value);
                }
            }
            $id = $this->client->sendSync($addRequest)->getProperty('ret');
            if (null !== $this->idCache) {
                $this->idCache[] = $id;
            }
            $idList .= $id;
            $addRequest->removeAllArguments();
        }
        return substr($idList, 1);
    }

    /**
     * Moves items at the current menu before a certain other item.
     * 
     * Moves items before a certain other item. Note that the "move"
     * command is not available on all menus. As a rule of thumb, if the order
     * of items in a menu is irrelevant to their interpretation, there won't
     * be a move command on that menu. If in doubt, check from a terminal.
     * 
     * @param mixed $numbers     Targeted items. Can be any criteria accepted
     *     by {@link static::find()}.
     * @param mixed $destination item before which the targeted items will be
     *     moved to. Can be any criteria accepted by {@link static::find()}.
     *     If multiple items match the criteria, the targeted items will move
     *     above the first match.
     * 
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    public function move($numbers, $destination)
    {
        $moveRequest = new Request($this->menu . '/move');
        $moveRequest->setArgument('numbers', $this->find($numbers));
        $destination = $this->find($destination);
        if (false !== strpos($destination, ',')) {
            $destination = strstr($destination, ',', true);
        }
        $moveRequest->setArgument('destination', $destination);
        $this->clearIdCache();
        return $this->client->sendSync($moveRequest);
    }

    /**
     * Counts items at the current menu.
     * 
     * Counts items at the current menu. This executes a dedicated command
     * ("print" with a "count-only" argument) on RouterOS, which is why only
     * queries are allowed as a criteria, in contrast with
     * {@link static::find()}, where numbers and callbacks are allowed also.
     * 
     * @param int   $mode  The counter mode.
     *     Currently ignored, but present for compatiblity with PHP 5.6+.
     * @param Query $query A query to filter items by. Without it, all items
     *     are included in the count.
     * 
     * @return int The number of items, or -1 on failure (e.g. if the
     *     current menu does not have a "print" command or items to be counted).
     */
    public function count($mode = COUNT_NORMAL, Query $query = null)
    {
        $result = $this->client->sendSync(
            new Request($this->menu . '/print count-only=""', $query)
        )->end()->getProperty('ret');

        if (null === $result) {
            return -1;
        }
        if (Stream::isStream($result)) {
            $result = stream_get_contents($result);
        }
        return (int)$result;
    }

    /**
     * Gets all items in the current menu.
     * 
     * Gets all items in the current menu, using a print request.
     * 
     * @param array<int|string,string> $args  Additional arguments to pass
     *     to the request.
     *     Each array key is the name of the argument, and each array value is
     *     the value of the argument to be passed.
     *     Arguments without a value (i.e. empty arguments) can also be
     *     specified using a numeric key, and the name of the argument as the
     *     array value.
     * @param Query|null               $query A query to filter items by.
     *     NULL to get all items.
     * 
     * @return ResponseCollection|false A response collection with all
     *     {@link Response::TYPE_DATA} responses. The collection will be empty
     *     when there are no matching items. FALSE on failure.
     */
    public function getAll(array $args = array(), Query $query = null)
    {
        $printRequest = new Request($this->menu . '/print', $query);
        foreach ($args as $name => $value) {
            if (is_int($name)) {
                $printRequest->setArgument($value);
            } else {
                $printRequest->setArgument($name, $value);
            }
        }
        $responses = $this->client->sendSync($printRequest);

        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            return false;
        }
        return $responses->getAllOfType(Response::TYPE_DATA);
    }

    /**
     * Puts a file on RouterOS's file system.
     * 
     * Puts a file on RouterOS's file system, regardless of the current menu.
     * Note that this is a **VERY VERY VERY** time consuming method - it takes a
     * minimum of a little over 4 seconds, most of which are in sleep. It waits
     * 2 seconds after a file is first created (required to actually start
     * writing to the file), and another 2 seconds after its contents is written
     * (performed in order to verify success afterwards).
     * Similarly for removal (when $data is NULL) - there are two seconds in
     * sleep, used to verify the file was really deleted.
     * 
     * If you want an efficient way of transferring files, use (T)FTP.
     * If you want an efficient way of removing files, use
     * {@link static::setMenu()} to move to the "/file" menu, and call
     * {@link static::remove()} without performing verification afterwards.
     * 
     * @param string               $filename  The filename to write data in.
     * @param string|resource|null $data      The data the file is going to have
     *     as a string or a seekable stream.
     *     Setting the value to NULL removes a file of this name.
     *     If a seekable stream is provided, it is sent from its current
     *     posistion to its end, and the pointer is seeked back to its current
     *     position after sending.
     *     Non seekable streams, as well as all other types, are casted to a
     *     string.
     * @param bool                 $overwrite Whether to overwrite the file if
     *     it exists.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function filePutContents($filename, $data, $overwrite = false)
    {
        $printRequest = new Request(
            '/file/print .proplist=""',
            Query::where('name', $filename)
        );
        $fileExists = count($this->client->sendSync($printRequest)) > 1;

        if (null === $data) {
            if (!$fileExists) {
                return false;
            }
            $removeRequest = new Request('/file/remove');
            $this->client->sendSync(
                $removeRequest->setArgument('numbers', $filename)
            );
            //Required for RouterOS to REALLY remove the file.
            sleep(2);
            return !(count($this->client->sendSync($printRequest)) > 1);
        }

        if (!$overwrite && $fileExists) {
            return false;
        }
        $result = $this->client->sendSync(
            $printRequest->setArgument('file', $filename)
        );
        if (count($result->getAllOfType(Response::TYPE_ERROR)) > 0) {
            return false;
        }
        //Required for RouterOS to write the initial file.
        sleep(2);
        $setRequest = new Request('/file/set contents=""');
        $setRequest->setArgument('numbers', $filename);
        $this->client->sendSync($setRequest);
        $this->client->sendSync($setRequest->setArgument('contents', $data));
        //Required for RouterOS to write the file's new contents.
        sleep(2);

        $fileSize = $this->client->sendSync(
            $printRequest->setArgument('file', null)
                ->setArgument('.proplist', 'size')
        )->getProperty('size');
        if (Stream::isStream($fileSize)) {
            $fileSize = stream_get_contents($fileSize);
        }
        if (Communicator::isSeekableStream($data)) {
            return Communicator::seekableStreamLength($data) == $fileSize;
        } else {
            return sprintf('%u', strlen((string)$data)) === $fileSize;
        };
    }

    /**
     * Gets the contents of a specified file.
     * 
     * @param string $filename      The name of the file to get the contents of.
     * @param string $tmpScriptName In order to get the file's contents, a
     *     script is created at "/system script" with a random name, the
     *     source of which is then overwriten with the file's contents, and
     *     finally retrieved. To eliminate any possibility of name clashes, you
     *     can specify your own name for the script.
     * 
     * @return string|resource|false The contents of the file as a string or as
     *     new PHP temp stream if the underliying
     *     {@link Client::isStreamingResponses()} is set to TRUE.
     *     FALSE is returned if there is no such file.
     */
    public function fileGetContents($filename, $tmpScriptName = null)
    {
        $checkRequest = new Request(
            '/file/print',
            Query::where('name', $filename)
        );
        if (1 === count($this->client->sendSync($checkRequest))) {
            return false;
        }
        $contents = $this->_exec(
            '/system script set $"_" source=[/file get $filename contents]',
            array('filename' => $filename),
            null,
            $tmpScriptName,
            true
        );
        return $contents;
    }

    /**
     * Performs an action on a bulk of items at the current menu.
     * 
     * @param string $what What action to perform.
     * @param array  $args Zero or more arguments can be specified, each being
     *     a criteria. If zero arguments are specified, removes all items.
     *     See {@link static::find()} for a description of what criteria are
     *     accepted.
     * 
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    protected function doBulk($what, array $args = array())
    {
        $bulkRequest = new Request($this->menu . '/' . $what);
        $bulkRequest->setArgument(
            'numbers',
            call_user_func_array(array($this, 'find'), $args)
        );
        return $this->client->sendSync($bulkRequest);
    }

    /**
     * Executes a RouterOS script.
     * 
     * Same as the public equivalent, with the addition of allowing you to get
     * the contents of the script post execution, instead of removing it.
     * 
     * @param string|resource $source The source of the script, as a string or
     *     stream. If a stream is provided, reading starts from the current
     *     position to the end of the stream, and the pointer stays at the end
     *     after reading is done.
     * @param array           $params An array of parameters to make available
     *     in the script as local variables.
     *     Variable names are array keys, and variable values are array values.
     *     Array values are automatically processed with
     *     {@link static::escapeValue()}. Streams are also supported, and are
     *     processed in chunks, each processed with
     *     {@link static::escapeString()}. Processing starts from the current
     *     position to the end of the stream, and the stream's pointer is left
     *     untouched after the reading is done.
     *     Note that the script's (generated) name is always added as the
     *     variable "_", which will be inadvertently lost if you overwrite it
     *     from here.
     * @param string          $policy Allows you to specify a policy the script
     *     must follow. Has the same format as in terminal.
     *     If left NULL, the script has no restrictions beyond those imposed by
     *     the username.
     * @param string          $name   The script is executed after being saved
     *     in "/system script" under a random name (prefixed with the computer's
     *     name), and is removed after execution. To eliminate any possibility
     *     of name clashes, you can specify your own name.
     * @param bool            $get    Whether to get the source of the script.
     * 
     * @return ResponseCollection|string Returns the response collection of the
     *     run, allowing you to inspect errors, if any.
     *     If the script was not added successfully before execution, the
     *     ResponseCollection from the add attempt is going to be returned.
     *     If $get is TRUE, returns the source of the script on success.
     */
    private function _exec(
        $source,
        array $params = array(),
        $policy = null,
        $name = null,
        $get = false
    ) {
        $request = new Request('/system/script/add');
        if (null === $name) {
            $name = uniqid(gethostname(), true);
        }
        $request->setArgument('name', $name);
        $request->setArgument('policy', $policy);

        $params += array('_' => $name);

        $finalSource = fopen('php://temp', 'r+b');
        fwrite(
            $finalSource,
            '/' . str_replace('/', ' ', substr($this->menu, 1)). "\n"
        );
        static::appendScript($finalSource, $source, $params);
        fwrite($finalSource, "\n");
        rewind($finalSource);

        $request->setArgument('source', $finalSource);
        $result = $this->client->sendSync($request);

        if (0 === count($result->getAllOfType(Response::TYPE_ERROR))) {
            $request = new Request('/system/script/run');
            $request->setArgument('number', $name);
            $result = $this->client->sendSync($request);

            if ($get) {
                $result = $this->client->sendSync(
                    new Request(
                        '/system/script/print .proplist="source"',
                        Query::where('name', $name)
                    )
                )->getProperty('source');
            }
            $request = new Request('/system/script/remove');
            $request->setArgument('numbers', $name);
            $this->client->sendSync($request);
        }

        return $result;
    }
}
