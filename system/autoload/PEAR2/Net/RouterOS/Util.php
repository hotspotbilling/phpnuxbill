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
 * Returned from {@link Util::getCurrentTime()}.
 */
use DateTime;

/**
 * Used at {@link Util::getCurrentTime()} to get the proper time.
 */
use DateTimeZone;

/**
 * Implemented by this class.
 */
use Countable;

/**
 * Used to detect streams in various methods of this class.
 */
use PEAR2\Net\Transmitter\Stream;

/**
 * Used to catch a DateTime exception at {@link Util::getCurrentTime()}.
 */
use Exception as E;

/**
 * Utility class.
 *
 * Abstracts away frequently used functionality (particularly CRUD operations)
 * in convenient to use methods by wrapping around a connection.
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
     * The connection to wrap around.
     *
     * @var Client
     */
    protected $client;

    /**
     * The current menu.
     *
     * Note that the root menu (only) uses an empty string.
     * This is done to enable commands executed at it without special casing it
     * at all commands.
     * Instead, only {@link static::setMenu()} is special cased.
     *
     * @var string
     */
    protected $menu = '';

    /**
     * An array with the numbers of items in the current menu.
     *
     * Numbers as keys, and the corresponding IDs as values.
     * NULL when the cache needs regenerating.
     *
     * @var array<int,string>|null
     */
    protected $idCache = null;

    /**
     * Creates a new Util instance.
     *
     * Wraps around a connection to provide convenience methods.
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
     * @return string The absolute path to current menu, using API syntax.
     */
    public function getMenu()
    {
        return '' === $this->menu ? '/' : $this->menu;
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
            if ('/' === $newMenu) {
                $this->menu = '';
            } elseif ('/' === $newMenu[0]) {
                $this->menu = $menuRequest->setCommand($newMenu)->getCommand();
            } else {
                $newMenu = (string)substr(
                    $menuRequest->setCommand(
                        '/' .
                        str_replace('/', ' ', (string)substr($this->menu, 1)) .
                        ' ' .
                        str_replace('/', ' ', $newMenu)
                        . ' ?'
                    )->getCommand(),
                    1,
                    -2/*strlen('/?')*/
                );
                if ('' !== $newMenu) {
                    $this->menu = '/' . $newMenu;
                } else {
                    $this->menu = '';
                }
            }
        }
        $this->clearIdCache();
        return $this;
    }

    /**
     * Creates a Request object.
     *
     * Creates a {@link Request} object, with a command that's at the
     * current menu. The request can then be sent using {@link Client}.
     *
     * @param string      $command The command of the request, not including
     *     the menu. The request will have that command at the current menu.
     * @param array       $args    Arguments of the request.
     *     Each array key is the name of the argument, and each array value is
     *     the value of the argument to be passed.
     *     Arguments without a value (i.e. empty arguments) can also be
     *     specified using a numeric key, and the name of the argument as the
     *     array value.
     * @param Query|null  $query   The {@link Query} of the request.
     * @param string|null $tag     The tag of the request.
     *
     * @return Request The {@link Request} object.
     *
     * @throws NotSupportedException On an attempt to call a command in a
     *     different menu using API syntax.
     * @throws InvalidArgumentException On an attempt to call a command in a
     *     different menu using CLI syntax.
     */
    public function newRequest(
        $command,
        array $args = array(),
        Query $query = null,
        $tag = null
    ) {
        if (false !== strpos($command, '/')) {
            throw new NotSupportedException(
                'Command tried to go to a different menu',
                NotSupportedException::CODE_MENU_MISMATCH,
                null,
                $command
            );
        }
        $request = new Request('/menu', $query, $tag);
        $request->setCommand("{$this->menu}/{$command}");
        foreach ($args as $name => $value) {
            if (is_int($name)) {
                $request->setArgument($value);
            } else {
                $request->setArgument($name, $value);
            }
        }
        return $request;
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
     *     Note that the script's (generated) name is always added as the
     *     variable "_", which will be inadvertently lost if you overwrite it
     *     from here.
     * @param string|null             $policy Allows you to specify a policy the
     *     script must follow. Has the same format as in terminal.
     *     If left NULL, the script has no restrictions beyond those imposed by
     *     the username.
     * @param string|null             $name   The script is executed after being
     *     saved in "/system script" and is removed after execution.
     *     If this argument is left NULL, a random string,
     *     prefixed with the computer's name, is generated and used
     *     as the script's name.
     *     To eliminate any possibility of name clashes,
     *     you can specify your own name instead.
     *
     * @return ResponseCollection The responses of all requests involved, i.e.
     *     the add, the run and the remove.
     *
     * @throws RouterErrorException When there is an error in any step of the
     *     way. The reponses include all successful commands prior to the error
     *     as well. If the error occurs during the run, there will also be a
     *     remove attempt, and the results will include its results as well.
     */
    public function exec(
        $source,
        array $params = array(),
        $policy = null,
        $name = null
    ) {
        if (null === $name) {
            $name = uniqid(gethostname(), true);
        }

        $request = new Request('/system/script/add');
        $request->setArgument('name', $name);
        $request->setArgument('policy', $policy);

        $params += array('_' => $name);

        $finalSource = fopen('php://temp', 'r+b');
        fwrite(
            $finalSource,
            '/' . str_replace('/', ' ', substr($this->menu, 1)). "\n"
        );
        Script::append($finalSource, $source, $params);
        fwrite($finalSource, "\n");
        rewind($finalSource);

        $request->setArgument('source', $finalSource);
        $addResult = $this->client->sendSync($request);

        if (count($addResult->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when trying to add script',
                RouterErrorException::CODE_SCRIPT_ADD_ERROR,
                null,
                $addResult
            );
        }

        $request = new Request('/system/script/run');
        $request->setArgument('number', $name);
        $runResult = $this->client->sendSync($request);
        $request = new Request('/system/script/remove');
        $request->setArgument('numbers', $name);
        $removeResult = $this->client->sendSync($request);

        $results = new ResponseCollection(
            array_merge(
                $addResult->toArray(),
                $runResult->toArray(),
                $removeResult->toArray()
            )
        );

        if (count($runResult->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when running script',
                RouterErrorException::CODE_SCRIPT_RUN_ERROR,
                null,
                $results
            );
        }
        if (count($removeResult->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when removing script',
                RouterErrorException::CODE_SCRIPT_REMOVE_ERROR,
                null,
                $results
            );
        }

        return $results;
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
     * Gets the current time on the router.
     *
     * Gets the current time on the router, regardless of the current menu.
     *
     * If the timezone is one known to both RouterOS and PHP, it will be used
     * as the timezone identifier. Otherwise (e.g. "manual"), the current GMT
     * offset will be used as a timezone, without any DST awareness.
     *
     * @return DateTime The current time of the router, as a DateTime object.
     */
    public function getCurrentTime()
    {
        $clock = $this->client->sendSync(
            new Request(
                '/system/clock/print
                .proplist=date,time,time-zone-name,gmt-offset'
            )
        )->current();
        $clockParts = array();
        foreach (array(
            'date',
            'time',
            'time-zone-name',
            'gmt-offset'
        ) as $clockPart) {
            $clockParts[$clockPart] = $clock->getProperty($clockPart);
            if (Stream::isStream($clockParts[$clockPart])) {
                $clockParts[$clockPart] = stream_get_contents(
                    $clockParts[$clockPart]
                );
            }
        }
        $datetime = ucfirst(strtolower($clockParts['date'])) . ' ' .
            $clockParts['time'];
        try {
            $result = DateTime::createFromFormat(
                'M/j/Y H:i:s',
                $datetime,
                new DateTimeZone($clockParts['time-zone-name'])
            );
        } catch (E $e) {
            $result = DateTime::createFromFormat(
                'M/j/Y H:i:s P',
                $datetime . ' ' . $clockParts['gmt-offset'],
                new DateTimeZone('UTC')
            );
        }
        return $result;
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
                $ret = $this->client->sendSync(
                    new Request($this->menu . '/find')
                )->getProperty('ret');
                if (null === $ret) {
                    $this->idCache = array();
                    return '';
                } elseif (!is_string($ret)) {
                    $ret = stream_get_contents($ret);
                }

                $idCache = str_replace(
                    ';',
                    ',',
                    strtolower($ret)
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
                )->getAllOfType(Response::TYPE_DATA) as $response) {
                    $newId = $response->getProperty('.id');
                    $idList .= strtolower(
                        is_string($newId)
                        ? $newId
                        : stream_get_contents($newId) . ','
                    );
                }
            } elseif (is_callable($criteria)) {
                $idCache = array();
                foreach ($this->client->sendSync(
                    new Request($this->menu . '/print')
                )->getAllOfType(Response::TYPE_DATA) as $response) {
                    $newId = $response->getProperty('.id');
                    $newId = strtolower(
                        is_string($newId)
                        ? $newId
                        : stream_get_contents($newId)
                    );
                    if ($criteria($response)) {
                        $idList .= $newId . ',';
                    }
                    $idCache[] = $newId;
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
     * @param int|string|null|Query $number    A number identifying the target
     *     item. Can also be an ID or (in some menus) name. For menus where
     *     there are no items (e.g. "/system identity"), you can specify NULL.
     *     You can also specify a {@link Query}, in which case the first match
     *     will be considered the target item.
     * @param string|resource|null  $valueName The name of the value to get.
     *     If omitted, or set to NULL, gets all properties of the target item.
     *
     * @return string|resource|null|array The value of the specified
     *     property as a string or as new PHP temp stream if the underlying
     *     {@link Client::isStreamingResponses()} is set to TRUE.
     *     If the property is not set, NULL will be returned.
     *     If $valueName is NULL, returns all properties as an array, where
     *     the result is parsed with {@link Script::parseValueToArray()}.
     *
     * @throws RouterErrorException When the router returns an error response
     *     (e.g. no such item, invalid property, etc.).
     */
    public function get($number, $valueName = null)
    {
        if ($number instanceof Query) {
            $number = explode(',', $this->find($number));
            $number = $number[0];
        } elseif (is_int($number) || ((string)$number === (string)(int)$number)) {
            $this->find();
            if (isset($this->idCache[(int)$number])) {
                $number = $this->idCache[(int)$number];
            } else {
                throw new RouterErrorException(
                    'Unable to resolve number from ID cache (no such item maybe)',
                    RouterErrorException::CODE_CACHE_ERROR
                );
            }
        }

        $request = new Request($this->menu . '/get');
        $request->setArgument('number', $number);
        $request->setArgument('value-name', $valueName);
        $responses = $this->client->sendSync($request);
        if (Response::TYPE_ERROR === $responses->getType()) {
            throw new RouterErrorException(
                'Error getting property',
                RouterErrorException::CODE_GET_ERROR,
                null,
                $responses
            );
        }

        $result = $responses->getProperty('ret');
        if (Stream::isStream($result)) {
            $result = stream_get_contents($result);
        }
        if (null === $valueName) {
            // @codeCoverageIgnoreStart
            //Some earlier RouterOS versions use "," instead of ";" as separator
            //Newer versions can't possibly enter this condition
            if (false === strpos($result, ';')
                && preg_match('/^([^=,]+\=[^=,]*)(?:\,(?1))+$/', $result)
            ) {
                $result = str_replace(',', ';', $result);
            }
            // @codeCoverageIgnoreEnd
            return Script::parseValueToArray('{' . $result . '}');
        }
        return $result;
    }

    /**
     * Enables all items at the current menu matching certain criteria.
     *
     * Zero or more arguments can be specified, each being a criteria.
     * If zero arguments are specified, enables all items.
     * See {@link static::find()} for a description of what criteria are
     * accepted.
     *
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
     */
    public function enable()
    {
        $responses = $this->doBulk('enable', func_get_args());
        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when enabling items',
                RouterErrorException::CODE_ENABLE_ERROR,
                null,
                $responses
            );
        }
        return $responses;
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
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
     */
    public function disable()
    {
        $responses = $this->doBulk('disable', func_get_args());
        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when disabling items',
                RouterErrorException::CODE_DISABLE_ERROR,
                null,
                $responses
            );
        }
        return $responses;
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
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
     */
    public function remove()
    {
        $responses = $this->doBulk('remove', func_get_args());
        $this->clearIdCache();
        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when removing items',
                RouterErrorException::CODE_REMOVE_ERROR,
                null,
                $responses
            );
        }
        return $responses;
    }

    /**
     * Comments items.
     *
     * Sets new comments on all items at the current menu
     * which match certain criteria, using the "comment" command.
     *
     * Note that not all menus have a "comment" command. Most notably, those are
     * menus without items in them (e.g. "/system identity"), and menus with
     * fixed items (e.g. "/ip service").
     *
     * @param mixed           $numbers Targeted items. Can be any criteria
     *     accepted by {@link static::find()}.
     * @param string|resource $comment The new comment to set on the item as a
     *     string or a seekable stream.
     *     If a seekable stream is provided, it is sent from its current
     *     position to its end, and the pointer is seeked back to its current
     *     position after sending.
     *     Non seekable streams, as well as all other types, are casted to a
     *     string.
     *
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
     */
    public function comment($numbers, $comment)
    {
        $commentRequest = new Request($this->menu . '/comment');
        $commentRequest->setArgument('comment', $comment);
        $commentRequest->setArgument('numbers', $this->find($numbers));
        $responses = $this->client->sendSync($commentRequest);
        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when commenting items',
                RouterErrorException::CODE_COMMENT_ERROR,
                null,
                $responses
            );
        }
        return $responses;
    }

    /**
     * Sets new values.
     *
     * Sets new values on certain properties on all items at the current menu
     * which match certain criteria.
     *
     * @param mixed                                           $numbers   Items
     *     to be modified.
     *     Can be any criteria accepted by {@link static::find()} or NULL
     *     in case the menu is one without items (e.g. "/system identity").
     * @param array<string,string|resource>|array<int,string> $newValues An
     *     array with the names of each property to set as an array key, and the
     *     new value as an array value.
     *     Flags (properties with a value "true" that is interpreted as
     *     equivalent of "yes" from CLI) can also be specified with a numeric
     *     index as the array key, and the name of the flag as the array value.
     *
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
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
        $responses = $this->client->sendSync($setRequest);
        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when setting items',
                RouterErrorException::CODE_SET_ERROR,
                null,
                $responses
            );
        }
        return $responses;
    }

    /**
     * Alias of {@link static::set()}
     *
     * @param mixed                $numbers   Items to be modified.
     *     Can be any criteria accepted by {@link static::find()} or NULL
     *     in case the menu is one without items (e.g. "/system identity").
     * @param string               $valueName Name of property to be modified.
     * @param string|resource|null $newValue  The new value to set.
     *     If set to NULL, the property is unset.
     *
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
     */
    public function edit($numbers, $valueName, $newValue)
    {
        return null === $newValue
            ? $this->unsetValue($numbers, $valueName)
            : $this->set($numbers, array($valueName => $newValue));
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
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
     */
    public function unsetValue($numbers, $valueName)
    {
        $unsetRequest = new Request($this->menu . '/unset');
        $responses = $this->client->sendSync(
            $unsetRequest->setArgument('numbers', $this->find($numbers))
                ->setArgument('value-name', $valueName)
        );
        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when unsetting value of items',
                RouterErrorException::CODE_UNSET_ERROR,
                null,
                $responses
            );
        }
        return $responses;
    }

    /**
     * Adds a new item at the current menu.
     *
     * @param array<string,string|resource>|array<int,string> $values Accepts
     *     one or more items to add to the current menu.
     *     The data about each item is specified as an array with the names of
     *     each property as an array key, and the value as an array value.
     *     Flags (properties with a value "true" that is interpreted as
     *     equivalent of "yes" from CLI) can also be specified with a numeric
     *     index as the array key, and the name of the flag as the array value.
     * @param array<string,string|resource>|array<int,string> $...    Additional
     *     items.
     *
     * @return string A comma separated list of the new items' IDs.
     *
     * @throws RouterErrorException When one or more items were not succesfully
     *     added. Note that the response collection will include all replies of
     *     all add commands, including the successful ones, in order.
     */
    public function add(array $values)
    {
        $addRequest = new Request($this->menu . '/add');
        $hasErrors = false;
        $results = array();
        foreach (func_get_args() as $values) {
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
            $result = $this->client->sendSync($addRequest);
            if (count($result->getAllOfType(Response::TYPE_ERROR)) > 0) {
                $hasErrors = true;
            }
            $results = array_merge($results, $result->toArray());
            $addRequest->removeAllArguments();
        }

        $this->clearIdCache();
        if ($hasErrors) {
            throw new RouterErrorException(
                'Router returned error when adding items',
                RouterErrorException::CODE_ADD_ERROR,
                null,
                new ResponseCollection($results)
            );
        }
        $results = new ResponseCollection($results);
        $idList = '';
        foreach ($results->getAllOfType(Response::TYPE_FINAL) as $final) {
            $idList .= ',' . strtolower($final->getProperty('ret'));
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
     * @param mixed $destination Item before which the targeted items will be
     *     moved to. Can be any criteria accepted by {@link static::find()}.
     *     If multiple items match the criteria, the targeted items will move
     *     above the first match.
     *     If NULL is given (or this argument is omitted), the targeted items
     *     will be moved to the bottom of the menu.
     *
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect the output. Current RouterOS versions don't return
     *     anything useful, but if future ones do, you can read it right away.
     *
     * @throws RouterErrorException When the router returns one or more errors.
     */
    public function move($numbers, $destination = null)
    {
        $moveRequest = new Request($this->menu . '/move');
        $moveRequest->setArgument('numbers', $this->find($numbers));
        if (null !== $destination) {
            $destination = $this->find($destination);
            if (false !== strpos($destination, ',')) {
                $destination = strstr($destination, ',', true);
            }
            $moveRequest->setArgument('destination', $destination);
        }
        $this->clearIdCache();
        $responses = $this->client->sendSync($moveRequest);
        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when moving items',
                RouterErrorException::CODE_MOVE_ERROR,
                null,
                $responses
            );
        }
        return $responses;
    }

    /**
     * Counts items at the current menu.
     *
     * Counts items at the current menu. This executes a dedicated command
     * ("print" with a "count-only" argument) on RouterOS, which is why only
     * queries are allowed as a criteria, in contrast with
     * {@link static::find()}, where numbers and callbacks are allowed also.
     *
     * @param Query|null           $query A query to filter items by.
     *     Without it, all items are included in the count.
     * @param string|resource|null $from  A comma separated list of item IDs.
     *     Any items in the set that still exist at the time of couting
     *     are included in the final tally. Note that the $query filters this
     *     set further (i.e. the item must be in the list AND match the $query).
     *     Leaving the value to NULL means all matching items at the current
     *     menu are included in the count.
     *
     * @return int The number of items, or -1 on failure (e.g. if the
     *     current menu does not have a "print" command or items to be counted).
     */
    public function count(Query $query = null, $from = null)
    {
        $countRequest = new Request(
            $this->menu . '/print count-only=""',
            $query
        );
        $countRequest->setArgument('from', $from);
        $result = $this->client->sendSync($countRequest)->end()
            ->getProperty('ret');

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
     * @param array<string,string|resource>|array<int,string> $args  Additional
     *     arguments to pass to the request.
     *     Each array key is the name of the argument, and each array value is
     *     the value of the argument to be passed.
     *     Arguments without a value (i.e. empty arguments) can also be
     *     specified using a numeric key, and the name of the argument as the
     *     array value.
     *     The "follow" and "follow-only" arguments are prohibited,
     *     as they would cause a synchronous request to run forever, without
     *     allowing the results to be observed.
     *     If you need to use those arguments, use {@link static::newRequest()},
     *     and pass the resulting {@link Request} to {@link Client::sendAsync()}.
     *     The "count-only" argument is also prohibited, as results from it
     *     would not be consumable. Use {@link static::count()} for that.
     * @param Query|null                                      $query A query to
     *     filter items by.
     *     NULL to get all items.
     *
     * @return ResponseCollection A response collection with all
     *     {@link Response::TYPE_DATA} responses. The collection will be empty
     *     when there are no matching items.
     *
     * @throws NotSupportedException If $args contains prohibited arguments
     *     ("follow", "follow-only" or "count-only").
     *
     * @throws RouterErrorException When there's an error upon attempting to
     *     call the "print" command on the specified menu (e.g. if there's no
     *     "print" command at the menu to begin with).
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

        foreach (array('follow', 'follow-only', 'count-only') as $arg) {
            if ($printRequest->getArgument($arg) !== null) {
                throw new NotSupportedException(
                    "The argument '{$arg}' was specified, but is prohibited",
                    NotSupportedException::CODE_ARG_PROHIBITED,
                    null,
                    $arg
                );
            }
        }
        $responses = $this->client->sendSync($printRequest);

        if (count($responses->getAllOfType(Response::TYPE_ERROR)) > 0) {
            throw new RouterErrorException(
                'Error when reading items',
                RouterErrorException::CODE_GETALL_ERROR,
                null,
                $responses
            );
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
     *     position to its end, and the pointer is seeked back to its current
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
        }
    }

    /**
     * Gets the contents of a specified file.
     *
     * @param string      $filename      The name of the file to get
     *     the contents of.
     * @param string|null $tmpScriptName In order to get the file's contents, a
     *     script is created at "/system script", the source of which is then
     *     overwritten with the file's contents, then retrieved from there,
     *     after which the script is removed.
     *     If this argument is left NULL, a random string,
     *     prefixed with the computer's name, is generated and used
     *     as the script's name.
     *     To eliminate any possibility of name clashes,
     *     you can specify your own name instead.
     *
     * @return string|resource The contents of the file as a string or as
     *     new PHP temp stream if the underlying
     *     {@link Client::isStreamingResponses()} is set to TRUE.
     *
     * @throws RouterErrorException When there's an error with the temporary
     *     script used to get the file, or if the file doesn't exist.
     */
    public function fileGetContents($filename, $tmpScriptName = null)
    {
        try {
            $responses = $this->exec(
                ':error ("&" . [/file get $filename contents]);',
                array('filename' => $filename),
                null,
                $tmpScriptName
            );
            throw new RouterErrorException(
                'Unable to read file through script (no error returned)',
                RouterErrorException::CODE_SCRIPT_FILE_ERROR,
                null,
                $responses
            );
        } catch (RouterErrorException $e) {
            if ($e->getCode() !== RouterErrorException::CODE_SCRIPT_RUN_ERROR) {
                throw $e;
            }
            $message = $e->getResponses()->getAllOfType(Response::TYPE_ERROR)
                ->getProperty('message');
            if (Stream::isStream($message)) {
                $successToken = fread($message, 1/*strlen('&')*/);
                if ('&' === $successToken) {
                    $messageCopy = fopen('php://temp', 'r+b');
                    stream_copy_to_stream($message, $messageCopy);
                    rewind($messageCopy);
                    fclose($message);
                    return $messageCopy;
                }
                rewind($message);
            } elseif (strpos($message, '&') === 0) {
                return substr($message, 1/*strlen('&')*/);
            }
            throw $e;
        }
    }

    /**
     * Performs an action on a bulk of items at the current menu.
     *
     * @param string $command What command to perform.
     * @param array  $args    Zero or more arguments can be specified,
     *     each being a criteria.
     *     If zero arguments are specified, matches all items.
     *     See {@link static::find()} for a description of what criteria are
     *     accepted.
     *
     * @return ResponseCollection Returns the response collection, allowing you
     *     to inspect errors, if any.
     */
    protected function doBulk($command, array $args = array())
    {
        $bulkRequest = new Request("{$this->menu}/{$command}");
        $bulkRequest->setArgument(
            'numbers',
            call_user_func_array(array($this, 'find'), $args)
        );
        return $this->client->sendSync($bulkRequest);
    }
}
