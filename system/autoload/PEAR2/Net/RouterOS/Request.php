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
 * Refers to transmitter direction constants.
 */
use PEAR2\Net\Transmitter as T;

/**
 * Represents a RouterOS request.
 *
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Request extends Message
{

    /**
     * The command to be executed.
     *
     * @var string
     */
    private $_command;

    /**
     * A query for the command.
     *
     * @var Query
     */
    private $_query;

    /**
     * Creates a request to send to RouterOS.
     *
     * @param string      $command The command to send.
     *     Can also contain arguments expressed in a shell-like syntax.
     * @param Query|null  $query   A query to associate with the request.
     * @param string|null $tag     The tag for the request.
     *
     * @see setCommand()
     * @see setArgument()
     * @see setTag()
     * @see setQuery()
     */
    public function __construct($command, Query $query = null, $tag = null)
    {
        if (false !== strpos($command, '=')
            && false !== ($spaceBeforeEquals = strrpos(
                strstr($command, '=', true),
                ' '
            ))
        ) {
            $this->parseArgumentString(substr($command, $spaceBeforeEquals));
            $command = rtrim(substr($command, 0, $spaceBeforeEquals));
        }
        $this->setCommand($command);
        $this->setQuery($query);
        $this->setTag($tag);
    }

    /**
     * A shorthand gateway.
     *
     * This is a magic PHP method that allows you to call the object as a
     * function. Depending on the argument given, one of the other functions in
     * the class is invoked and its returned value is returned by this function.
     *
     * @param Query|Communicator|string|null $arg A {@link Query} to associate
     *     the request with, a {@link Communicator} to send the request over,
     *     an argument to get the value of, or NULL to get the tag. If a
     *     second argument is provided, this becomes the name of the argument to
     *     set the value of, and the second argument is the value to set.
     *
     * @return string|resource|int|$this Whatever the long form
     *     function returns.
     */
    public function __invoke($arg = null)
    {
        if (func_num_args() > 1) {
            return $this->setArgument(func_get_arg(0), func_get_arg(1));
        }
        if ($arg instanceof Query) {
            return $this->setQuery($arg);
        }
        if ($arg instanceof Communicator) {
            return $this->send($arg);
        }
        return parent::__invoke($arg);
    }

    /**
     * Sets the command to send to RouterOS.
     *
     * Sets the command to send to RouterOS. The command can use the API or CLI
     * syntax of RouterOS, but either way, it must be absolute (begin  with a
     * "/") and without arguments.
     *
     * @param string $command The command to send.
     *
     * @return $this The request object.
     *
     * @see getCommand()
     * @see setArgument()
     */
    public function setCommand($command)
    {
        $command = (string) $command;
        if (strpos($command, '/') !== 0) {
            throw new InvalidArgumentException(
                'Commands must be absolute.',
                InvalidArgumentException::CODE_ABSOLUTE_REQUIRED
            );
        }
        if (substr_count($command, '/') === 1) {
            //Command line syntax convertion
            $cmdParts = preg_split('#[\s/]+#sm', $command);
            $cmdRes = array($cmdParts[0]);
            for ($i = 1, $n = count($cmdParts); $i < $n; $i++) {
                if ('..' === $cmdParts[$i]) {
                    $delIndex = count($cmdRes) - 1;
                    if ($delIndex < 1) {
                        throw new InvalidArgumentException(
                            'Unable to resolve command',
                            InvalidArgumentException::CODE_CMD_UNRESOLVABLE
                        );
                    }
                    unset($cmdRes[$delIndex]);
                    $cmdRes = array_values($cmdRes);
                } else {
                    $cmdRes[] = $cmdParts[$i];
                }
            }
            $command = implode('/', $cmdRes);
        }
        if (!preg_match('#^/\S+$#sm', $command)) {
            throw new InvalidArgumentException(
                'Invalid command supplied.',
                InvalidArgumentException::CODE_CMD_INVALID
            );
        }
        $this->_command = $command;
        return $this;
    }

    /**
     * Gets the command that will be send to RouterOS.
     *
     * Gets the command that will be send to RouterOS in its API syntax.
     *
     * @return string The command to send.
     *
     * @see setCommand()
     */
    public function getCommand()
    {
        return $this->_command;
    }

    /**
     * Sets the query to send with the command.
     *
     * @param Query|null $query The query to be set.
     *     Setting NULL will remove the  currently associated query.
     *
     * @return $this The request object.
     *
     * @see getQuery()
     */
    public function setQuery(Query $query = null)
    {
        $this->_query = $query;
        return $this;
    }

    /**
     * Gets the currently associated query
     *
     * @return Query|null The currently associated query.
     *
     * @see setQuery()
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Sets the tag to associate the request with.
     *
     * Sets the tag to associate the request with. Setting NULL erases the
     * currently set tag.
     *
     * @param string|null $tag The tag to set.
     *
     * @return $this The request object.
     *
     * @see getTag()
     */
    public function setTag($tag)
    {
        return parent::setTag($tag);
    }

    /**
     * Sets an argument for the request.
     *
     * @param string               $name  Name of the argument.
     * @param string|resource|null $value Value of the argument as a string or
     *     seekable stream.
     *     Setting the value to NULL removes an argument of this name.
     *     If a seekable stream is provided, it is sent from its current
     *     position to its end, and the pointer is seeked back to its current
     *     position after sending.
     *     Non seekable streams, as well as all other types, are casted to a
     *     string.
     *
     * @return $this The request object.
     *
     * @see getArgument()
     */
    public function setArgument($name, $value = '')
    {
        return parent::setAttribute($name, $value);
    }

    /**
     * Gets the value of an argument.
     *
     * @param string $name The name of the argument.
     *
     * @return string|resource|null The value of the specified argument.
     *     Returns NULL if such an argument is not set.
     *
     * @see setAttribute()
     */
    public function getArgument($name)
    {
        return parent::getAttribute($name);
    }

    /**
     * Removes all arguments from the request.
     *
     * @return $this The request object.
     */
    public function removeAllArguments()
    {
        return parent::removeAllAttributes();
    }

    /**
     * Sends a request over a communicator.
     *
     * @param Communicator  $com The communicator to send the request over.
     * @param Registry|null $reg An optional registry to sync the request with.
     *
     * @return int The number of bytes sent.
     *
     * @see Client::sendSync()
     * @see Client::sendAsync()
     */
    public function send(Communicator $com, Registry $reg = null)
    {
        if (null !== $reg
            && (null != $this->getTag() || !$reg->isTaglessModeOwner())
        ) {
            $originalTag = $this->getTag();
            $this->setTag($reg->getOwnershipTag() . $originalTag);
            $bytes = $this->send($com);
            $this->setTag($originalTag);
            return $bytes;
        }
        if ($com->getTransmitter()->isPersistent()) {
            $old = $com->getTransmitter()->lock(T\Stream::DIRECTION_SEND);
            $bytes = $this->_send($com);
            $com->getTransmitter()->lock($old, true);
            return $bytes;
        }
        return $this->_send($com);
    }

    /**
     * Sends a request over a communicator.
     *
     * The only difference with the non private equivalent is that this one does
     * not do locking.
     *
     * @param Communicator $com The communicator to send the request over.
     *
     * @return int The number of bytes sent.
     *
     * @see Client::sendSync()
     * @see Client::sendAsync()
     */
    private function _send(Communicator $com)
    {
        if (!$com->getTransmitter()->isAcceptingData()) {
            throw new SocketException(
                'Transmitter is invalid. Sending aborted.',
                SocketException::CODE_REQUEST_SEND_FAIL
            );
        }
        $bytes = 0;
        $bytes += $com->sendWord($this->getCommand());
        if (null !== ($tag = $this->getTag())) {
            $bytes += $com->sendWord('.tag=' . $tag);
        }
        foreach ($this->attributes as $name => $value) {
            $prefix = '=' . $name . '=';
            if (is_string($value)) {
                $bytes += $com->sendWord($prefix . $value);
            } else {
                $bytes += $com->sendWordFromStream($prefix, $value);
            }
        }
        $query = $this->getQuery();
        if ($query instanceof Query) {
            $bytes += $query->send($com);
        }
        $bytes += $com->sendWord('');
        return $bytes;
    }

    /**
     * Verifies the request.
     *
     * Verifies the request against a communicator, i.e. whether the request
     * could successfully be sent (assuming the connection is still opened).
     *
     * @param Communicator $com The Communicator to check against.
     *
     * @return $this The request object itself.
     *
     * @throws LengthException If the resulting length of an API word is not
     *     supported.
     */
    public function verify(Communicator $com)
    {
        $com::verifyLengthSupport(strlen($this->getCommand()));
        $com::verifyLengthSupport(strlen('.tag=' . (string)$this->getTag()));
        foreach ($this->attributes as $name => $value) {
            if (is_string($value)) {
                $com::verifyLengthSupport(strlen('=' . $name . '=' . $value));
            } else {
                $com::verifyLengthSupport(
                    strlen('=' . $name . '=') +
                    $com::seekableStreamLength($value)
                );
            }
        }
        $query = $this->getQuery();
        if ($query instanceof Query) {
            $query->verify($com);
        }
        return $this;
    }

    /**
     * Parses the arguments of a command.
     *
     * @param string $string The argument string to parse.
     *
     * @return void
     */
    protected function parseArgumentString($string)
    {
        /*
         * Grammar:
         *
         * <arguments> := (<<\s+>>, <argument>)*,
         * <argument> := <name>, <value>?
         * <name> := <<[^\=\s]+>>
         * <value> := "=", (<quoted string> | <unquoted string>)
         * <quotedString> := <<">>, <<([^"]|\\"|\\\\)*>>, <<">>
         * <unquotedString> := <<\S+>>
         */

        $token = '';
        $name = null;
        while ($string = substr($string, strlen($token))) {
            if (null === $name) {
                if (preg_match('/^\s+([^\s=]+)/sS', $string, $matches)) {
                    $token = $matches[0];
                    $name = $matches[1];
                } else {
                    throw new InvalidArgumentException(
                        "Parsing of argument name failed near '{$string}'",
                        InvalidArgumentException::CODE_NAME_UNPARSABLE
                    );
                }
            } elseif (preg_match('/^\s/s', $string, $matches)) {
                //Empty argument
                $token = '';
                $this->setArgument($name);
                $name = null;
            } elseif (preg_match(
                '/^="(([^\\\"]|\\\"|\\\\)*)"/sS',
                $string,
                $matches
            )) {
                $token = $matches[0];
                $this->setArgument(
                    $name,
                    str_replace(
                        array('\\"', '\\\\'),
                        array('"', '\\'),
                        $matches[1]
                    )
                );
                $name = null;
            } elseif (preg_match('/^=(\S+)/sS', $string, $matches)) {
                $token = $matches[0];
                $this->setArgument($name, $matches[1]);
                $name = null;
            } else {
                throw new InvalidArgumentException(
                    "Parsing of argument value failed near '{$string}'",
                    InvalidArgumentException::CODE_VALUE_UNPARSABLE
                );
            }
        }

        if (null !== $name && ('' !== ($name = trim($name)))) {
            $this->setArgument($name, '');
        }

    }
}
