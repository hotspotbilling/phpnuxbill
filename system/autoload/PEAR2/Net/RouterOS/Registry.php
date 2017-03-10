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
 * Uses shared memory to keep responses in.
 */
use PEAR2\Cache\SHM;

/**
 * A RouterOS registry.
 * 
 * Provides functionality for managing the request/response flow. Particularly
 * useful in persistent connections.
 * 
 * Note that this class is not meant to be called directly.
 * 
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Registry
{
    /**
     * @var SHM The storage. 
     */
    protected $shm;
    
    /**
     * @var int ID of request. Populated at first instance in request.
     */
    protected static $requestId = -1;
    
    /**
     * @var int ID to be given to next instance, after incrementing it.
     */
    protected static $instanceIdSeed = -1;
    
    /**
     * @var int ID of instance within the request.
     */
    protected $instanceId;
    
    /**
     * Creates a registry.
     * 
     * @param string $uri An URI to bind the registry to.
     */
    public function __construct($uri)
    {
        $this->shm = SHM::factory(__CLASS__ . ' ' . $uri);
        if (-1 === self::$requestId) {
            self::$requestId = $this->shm->add('requestId', 0)
                ? 0 : $this->shm->inc('requestId');
        }
        $this->instanceId = ++self::$instanceIdSeed;
        $this->shm->add('responseBuffer_' . $this->getOwnershipTag(), array());
    }
    
    /**
     * Parses a tag.
     * 
     * Parses a tag to reveal the ownership part of it, and the original tag.
     * 
     * @param string $tag The tag (as received) to parse.
     * 
     * @return array An array with the first member being the ownership tag, and
     *     the second one being the original tag. 
     */
    public static function parseTag($tag)
    {
        if (null === $tag) {
            return array(null, null);
        }
        $result = explode('__', $tag, 2);
        $result[0] .= '__';
        if ('' === $result[1]) {
            $result[1] = null;
        }
        return $result;
    }
    
    /**
     * Checks if this instance is the tagless mode owner.
     * 
     * @return bool TRUE if this instance is the tagless mode owner, FALSE
     *     otherwise.
     */
    public function isTaglessModeOwner()
    {
        $this->shm->lock('taglessModeOwner');
        $result = $this->shm->exists('taglessModeOwner')
            && $this->getOwnershipTag() === $this->shm->get('taglessModeOwner');
        $this->shm->unlock('taglessModeOwner');
        return $result;
    }
    
    /**
     * Sets the "tagless mode" setting.
     * 
     * While in tagless mode, this instance will claim owhership of any
     * responses without a tag. While not in this mode, any requests without a
     * tag will be given to all instances.
     * 
     * Regardless of mode, if the type of the response is
     * {@link Response::TYPE_FATAL}, it will be given to all instances.
     * 
     * @param bool $taglessMode TRUE to claim tagless ownership, FALSE to
     *     release such ownership, if taken.
     * 
     * @return bool TRUE on success, FALSE on failure. 
     */
    public function setTaglessMode($taglessMode)
    {
        return $taglessMode
            ?   ($this->shm->lock('taglessMode')
                && $this->shm->lock('taglessModeOwner')
                && $this->shm->add('taglessModeOwner', $this->getOwnershipTag())
                && $this->shm->unlock('taglessModeOwner'))
            :   ($this->isTaglessModeOwner()
                && $this->shm->lock('taglessModeOwner')
                && $this->shm->delete('taglessModeOwner')
                && $this->shm->unlock('taglessModeOwner')
                && $this->shm->unlock('taglessMode'));
    }
    
    /**
     * Get the ownership tag for this instance.
     * 
     * @return string The ownership tag for this registry instance. 
     */
    public function getOwnershipTag()
    {
        return self::$requestId . '_' . $this->instanceId . '__';
    }
    
    /**
     * Add a response to the registry.
     * 
     * @param Response $response     The response to add. The caller of this
     *     function is responsible for ensuring that the ownership tag and the
     *     original tag are separated, so that only the original one remains in
     *     the response.
     * @param string   $ownershipTag The ownership tag that the response had.
     * 
     * @return bool TRUE if the request was added to its buffer, FALSE if
     *     this instance owns the response, and therefore doesn't need to add
     *     the response to its buffer.
     */
    public function add(Response $response, $ownershipTag)
    {
        if ($this->getOwnershipTag() === $ownershipTag
            || ($this->isTaglessModeOwner()
            && $response->getType() !== Response::TYPE_FATAL)
        ) {
            return false;
        }
        
        if (null === $ownershipTag) {
            $this->shm->lock('taglessModeOwner');
            if ($this->shm->exists('taglessModeOwner')
                && $response->getType() !== Response::TYPE_FATAL
            ) {
                $ownershipTag = $this->shm->get('taglessModeOwner');
                $this->shm->unlock('taglessModeOwner');
            } else {
                $this->shm->unlock('taglessModeOwner');
                foreach ($this->shm->getIterator(
                    '/^(responseBuffer\_)/',
                    true
                ) as $targetBufferName) {
                    $this->_add($response, $targetBufferName);
                }
                return true;
            }
        }
        
        $this->_add($response, 'responseBuffer_' . $ownershipTag);
        return true;
    }
    
    /**
     * Adds a response to a buffer.
     * 
     * @param Response $response         The response to add.
     * @param string   $targetBufferName The name of the buffer to add the
     *     response to.
     * 
     * @return void
     */
    private function _add(Response $response, $targetBufferName)
    {
        if ($this->shm->lock($targetBufferName)) {
            $targetBuffer = $this->shm->get($targetBufferName);
            $targetBuffer[] = $response;
            $this->shm->set($targetBufferName, $targetBuffer);
            $this->shm->unlock($targetBufferName);
        }
    }
    
    /**
     * Gets the next response from this instance's buffer.
     * 
     * @return Response|null The next response, or NULL if there isn't one. 
     */
    public function getNextResponse()
    {
        $response = null;
        $targetBufferName = 'responseBuffer_' . $this->getOwnershipTag();
        if ($this->shm->exists($targetBufferName)
            && $this->shm->lock($targetBufferName)
        ) {
            $targetBuffer = $this->shm->get($targetBufferName);
            if (!empty($targetBuffer)) {
                $response = array_shift($targetBuffer);
                $this->shm->set($targetBufferName, $targetBuffer);
            }
            $this->shm->unlock($targetBufferName);
        }
        return $response;
    }
    
    /**
     * Closes the registry.
     * 
     * Closes the registry, meaning that all buffers are cleared.
     * 
     * @return void 
     */
    public function close()
    {
        self::$requestId = -1;
        self::$instanceIdSeed = -1;
        $this->shm->clear();
    }
    
    /**
     * Removes a buffer.
     * 
     * @param string $targetBufferName The buffer to remove.
     * 
     * @return void
     */
    private function _close($targetBufferName)
    {
        if ($this->shm->lock($targetBufferName)) {
            $this->shm->delete($targetBufferName);
            $this->shm->unlock($targetBufferName);
        }
    }
    
    /**
     * Removes this instance's buffer. 
     */
    public function __destruct()
    {
        $this->_close('responseBuffer_' . $this->getOwnershipTag());
    }
}
