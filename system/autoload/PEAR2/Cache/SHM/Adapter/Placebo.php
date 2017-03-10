<?php

/**
 * ~~summary~~
 * 
 * ~~description~~
 * 
 * PHP version 5
 * 
 * @category  Caching
 * @package   PEAR2_Cache_SHM
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   0.1.3
 * @link      http://pear2.php.net/PEAR2_Cache_SHM
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Cache\SHM\Adapter;

/**
 * Throws exceptions from this namespace, and extends from this class.
 */
use PEAR2\Cache\SHM;

/**
 * {@link Placebo::getIterator()} returns this object.
 */
use ArrayObject;

/**
 * This adapter is not truly persistent. It is intended to emulate persistency
 * in non persistent environments, so that upper level applications can use a
 * single code path for persistent and non persistent code.
 * 
 * @category Caching
 * @package  PEAR2_Cache_SHM
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Cache_SHM
 */
class Placebo extends SHM
{
    /**
     * @var string ID of the current storage. 
     */
    protected $persistentId;
    
    /**
     * List of persistent IDs.
     * 
     * A list of persistent IDs within the current request (as keys) with an int
     * (as a value) specifying the number of instances in the current request.
     * Used as an attempt to ensure implicit lock releases on destruction.
     * @var array 
     */
    protected static $requestInstances = array();
    
    /**
     * @var array Array of lock names (as values) for each persistent ID (as
     *     key) obtained during the current request.
     */
    protected static $locksBackup = array();
    
    /**
     * The data storage.
     * 
     * Each persistent ID is a key, and the value is an array.
     * Each such array has data keys as its keys, and an array as a value.
     * Each such array has as its elements the value, the timeout and the time
     * the data was set.
     * @var array 
     */
    protected static $data = array();
    
    /**
     * Creates a new shared memory storage.
     * 
     * Estabilishes a separate persistent storage.
     * 
     * @param string $persistentId The ID for the storage. The storage will be
     *     reused if it exists, or created if it doesn't exist. Data and locks
     *     are namespaced by this ID.
     */
    public function __construct($persistentId)
    {
        if (isset(static::$requestInstances[$persistentId])) {
            ++static::$requestInstances[$persistentId];
        } else {
            static::$requestInstances[$persistentId] = 1;
            static::$locksBackup[$persistentId] = array();
            static::$data[$persistentId] = array();
        }
        $this->persistentId = $persistentId;
    }
    
    /**
     * Releases any unreleased locks. 
     */
    public function __destruct()
    {
        if (0 === --static::$requestInstances[$this->persistentId]) {
            static::$locksBackup[$this->persistentId] = array();
        }
    }
    
    /**
     * Checks if the adapter meets its requirements.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public static function isMeetingRequirements()
    {
        return 'cli' === PHP_SAPI;
    }
    
    /**
     * Pretends to obtain a lock.
     * 
     * @param string $key     Ignored.
     * @param double $timeout Ignored.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function lock($key, $timeout = null)
    {
        $key = (string) $key;
        if (in_array($key, static::$locksBackup[$this->persistentId], true)) {
            return false;
        }
        static::$locksBackup[$this->persistentId][] = $key;
        return true;
    }
    
    /**
     * Pretends to release a lock.
     * 
     * @param string $key Ignored
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function unlock($key)
    {
        $key = (string) $key;
        if (!in_array($key, static::$locksBackup[$this->persistentId], true)) {
            return false;
        }
        unset(static::$locksBackup[$this->persistentId][array_search(
            $key,
            static::$locksBackup[$this->persistentId],
            true
        )]);
        return true;
    }
    
    /**
     * Checks if a specified key is in the storage.
     * 
     * @param string $key Name of key to check.
     * 
     * @return bool TRUE if the key is in the storage, FALSE otherwise. 
     */
    public function exists($key)
    {
        return array_key_exists($key, static::$data[$this->persistentId]);
    }
    
    /**
     * Adds a value to the shared memory storage.
     * 
     * Adds a value to the storage if it doesn't exist, or fails if it does.
     * 
     * @param string $key   Name of key to associate the value with.
     * @param mixed  $value Value for the specified key.
     * @param int    $ttl   Because "true" adapters purge the cache at the next
     *     request, this setting is ignored.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function add($key, $value, $ttl = 0)
    {
        if ($this->exists($key)) {
            return false;
        }
        return $this->set($key, $value, $ttl);
    }
    
    /**
     * Sets a value in the shared memory storage.
     * 
     * Adds a value to the storage if it doesn't exist, overwrites it otherwise.
     * 
     * @param string $key   Name of key to associate the value with.
     * @param mixed  $value Value for the specified key.
     * @param int    $ttl   Because "true" adapters purge the cache at the next
     *     request, this setting is ignored.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function set($key, $value, $ttl = 0)
    {
        static::$data[$this->persistentId][$key] = $value;
        return true;
    }
    
    /**
     * Gets a value from the shared memory storage.
     * 
     * Gets the current value, or throws an exception if it's not stored.
     * 
     * @param string $key Name of key to get the value of.
     * 
     * @return mixed The current value of the specified key.
     */
    public function get($key)
    {
        if ($this->exists($key)) {
            return static::$data[$this->persistentId][$key];
        }
        throw new SHM\InvalidArgumentException(
            'Unable to fetch key. No such key.',
            200
        );
    }
    
    /**
     * Deletes a value from the shared memory storage.
     * 
     * @param string $key Name of key to delete.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function delete($key)
    {
        if ($this->exists($key)) {
            unset(static::$data[$this->persistentId][$key]);
            return true;
        }
        return false;
    }
    
    /**
     * Increases a value from the shared memory storage.
     * 
     * Increases a value from the shared memory storage. Unlike a plain
     * set($key, get($key)+$step) combination, this function also implicitly
     * performs locking.
     * 
     * @param string $key  Name of key to increase.
     * @param int    $step Value to increase the key by.
     * 
     * @return int The new value.
     */
    public function inc($key, $step = 1)
    {
        if (!$this->exists($key) || !is_int($value = $this->get($key))
            || !$this->set($key, $value + (int) $step)
        ) {
            throw new SHM\InvalidArgumentException(
                'Unable to increase the value. Are you sure the value is int?',
                201
            );
        }
        return $this->get($key);
    }
    
    /**
     * Decreases a value from the shared memory storage.
     * 
     * Decreases a value from the shared memory storage. Unlike a plain
     * set($key, get($key)-$step) combination, this function also implicitly
     * performs locking.
     * 
     * @param string $key  Name of key to decrease.
     * @param int    $step Value to decrease the key by.
     * 
     * @return int The new value.
     */
    public function dec($key, $step = 1)
    {
        if (!$this->exists($key) || !is_int($value = $this->get($key))
            || !$this->set($key, $value - (int) $step)
        ) {
            throw new SHM\InvalidArgumentException(
                'Unable to increase the value. Are you sure the value is int?',
                202
            );
        }
        return $this->get($key);
    }

    /**
     * Sets a new value if a key has a certain value.
     * 
     * Sets a new value if a key has a certain value. This function only works
     * when $old and $new are longs.
     * 
     * @param string $key Key of the value to compare and set.
     * @param int    $old The value to compare the key against.
     * @param int    $new The value to set the key to.
     * 
     * @return bool TRUE on success, FALSE on failure. 
     */
    public function cas($key, $old, $new)
    {
        return $this->exists($key) && ($this->get($key) === $old)
            && is_int($new) && $this->set($key, $new);
    }
    
    /**
     * Clears the persistent storage.
     * 
     * Clears the persistent storage, i.e. removes all keys. Locks are left
     * intact.
     * 
     * @return void
     */
    public function clear()
    {
        static::$data[$this->persistentId] = array();
    }
    
    /**
     * Retrieve an external iterator
     * 
     * Returns an external iterator.
     * 
     * @param string|null $filter   A PCRE regular expression.
     *     Only matching keys will be iterated over.
     *     Setting this to NULL matches all keys of this instance.
     * @param bool        $keysOnly Whether to return only the keys,
     *     or return both the keys and values.
     * 
     * @return ArrayObject An array with all matching keys as array keys,
     *     and values as array values. If $keysOnly is TRUE, the array keys are
     *     numeric, and the array values are key names.
     */
    public function getIterator($filter = null, $keysOnly = false)
    {
        if (null === $filter) {
            return new ArrayObject(
                $keysOnly
                ? array_keys(static::$data[$this->persistentId])
                : static::$data[$this->persistentId]
            );
        }
        
        $result = array();
        foreach (static::$data[$this->persistentId] as $key => $value) {
            if (preg_match($filter, $key)) {
                $result[$key] = $value;
            }
        }
        return new ArrayObject($keysOnly ? array_keys($result) : $result);
    }
}
