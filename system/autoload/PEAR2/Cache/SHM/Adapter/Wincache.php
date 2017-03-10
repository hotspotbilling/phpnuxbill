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
 * {@link Wincache::getIterator()} returns this object.
 */
use ArrayObject;

/**
 * Shared memory adapter for the WinCache extension.
 * 
 * @category Caching
 * @package  PEAR2_Cache_SHM
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Cache_SHM
 */
class Wincache extends SHM
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
     * @var array Array of lock names obtained during the current request.
     */
    protected static $locksBackup = array();
    
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
        $this->persistentId
            = static::encodeLockName(__CLASS__ . ' ' . $persistentId) . ' ';
        if (isset(static::$requestInstances[$this->persistentId])) {
            static::$requestInstances[$this->persistentId]++;
        } else {
            static::$requestInstances[$this->persistentId] = 1;
            static::$locksBackup[$this->persistentId] = array();
        }
    }
    
    /**
     * Encodes a lock name
     * 
     * Encodes a lock name, so that it can be properly obtained. The scheme used
     * is a subset of URL encoding, with only the "%" and "\" characters being
     * escaped. The encoding itself is necessary, since lock names can't contain
     * the "\" character.
     * 
     * @param string $name The lock name to encode.
     * 
     * @return string The encoded name.
     * @link http://msdn.microsoft.com/en-us/library/ms682411(VS.85).aspx
     */
    protected static function encodeLockName($name)
    {
        return str_replace(array('%', '\\'), array('%25', '%5C'), $name);
    }
    
    /**
     * Checks if the adapter meets its requirements.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public static function isMeetingRequirements()
    {
        return extension_loaded('wincache')
            && version_compare(phpversion('wincache'), '1.1.0', '>=')
            && ini_get('wincache.ucenabled')
            && ('cli' !== PHP_SAPI || ini_get('wincache.enablecli'));
    }
    
    /**
     * Releases any locks obtained by this instance as soon as there are no more
     * references to the object's persistent ID.
     */
    public function __destruct()
    {
        if (0 === --static::$requestInstances[$this->persistentId]) {
            foreach (static::$locksBackup[$this->persistentId] as $key) {
                wincache_unlock(
                    $this->persistentId . static::encodeLockName($key)
                );
            }
        }
    }

    
    /**
     * Obtains a named lock.
     * 
     * @param string $key     Name of the key to obtain. Note that $key may
     *     repeat for each distinct $persistentId.
     * @param double $timeout Ignored with WinCache. Script will always block if
     *     the lock can't be immediatly obtained.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function lock($key, $timeout = null)
    {
        $result = wincache_lock(
            $this->persistentId . static::encodeLockName($key)
        );
        if ($result) {
            static::$locksBackup[$this->persistentId] = $key;
        }
        return $result;
    }
    
    /**
     * Releases a named lock.
     * 
     * @param string $key Name of the key to release. Note that $key may
     *     repeat for each distinct $persistentId.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function unlock($key)
    {
        $result = wincache_unlock(
            $this->persistentId . static::encodeLockName($key)
        );
        if ($result) {
            unset(static::$locksBackup[$this->persistentId][array_search(
                $key,
                static::$locksBackup[$this->persistentId],
                true
            )]);
        }
        return $result;
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
        return wincache_ucache_exists($this->persistentId . $key);
    }
    
    /**
     * Adds a value to the shared memory storage.
     * 
     * Sets a value to the storage if it doesn't exist, or fails if it does.
     * 
     * @param string $key   Name of key to associate the value with.
     * @param mixed  $value Value for the specified key.
     * @param int    $ttl   Seconds to store the value. If set to 0 indicates no
     *     time limit.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function add($key, $value, $ttl = 0)
    {
        return wincache_ucache_add($this->persistentId . $key, $value, $ttl);
    }
    
    /**
     * Sets a value in the shared memory storage.
     * 
     * Adds a value to the storage if it doesn't exist, overwrites it otherwise.
     * 
     * @param string $key   Name of key to associate the value with.
     * @param mixed  $value Value for the specified key.
     * @param int    $ttl   Seconds to store the value. If set to 0 indicates no
     *     time limit.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function set($key, $value, $ttl = 0)
    {
        return wincache_ucache_set($this->persistentId . $key, $value, $ttl);
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
        $value = wincache_ucache_get($this->persistentId . $key, $success);
        if (!$success) {
            throw new SHM\InvalidArgumentException(
                'Unable to fetch key. No such key, or key has expired.',
                300
            );
        }
        return $value;
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
        return wincache_ucache_delete($this->persistentId . $key);
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
        $newValue = wincache_ucache_inc(
            $this->persistentId . $key,
            (int) $step,
            $success
        );
        if (!$success) {
            throw new SHM\InvalidArgumentException(
                'Unable to increase the value. Are you sure the value is int?',
                301
            );
        }
        return $newValue;
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
        $newValue = wincache_ucache_dec(
            $this->persistentId . $key,
            (int) $step,
            $success
        );
        if (!$success) {
            throw new SHM\InvalidArgumentException(
                'Unable to decrease the value. Are you sure the value is int?',
                302
            );
        }
        return $newValue;
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
        return wincache_ucache_cas($this->persistentId . $key, $old, $new);
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
        $info = wincache_ucache_info();
        foreach ($info['ucache_entries'] as $entry) {
            if (!$entry['is_session']
                && 0 === strpos($entry['key_name'], $this->persistentId)
            ) {
                wincache_ucache_delete($entry['key_name']);
            }
        }
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
        $info = wincache_ucache_info();
        $result = array();
        foreach ($info['ucache_entries'] as $entry) {
            if (!$entry['is_session']
                && 0 === strpos($entry['key_name'], $this->persistentId)
            ) {
                $localKey = strstr($entry['key_name'], $this->persistentId);
                if (null === $filter || preg_match($filter, $localKey)) {
                    if ($keysOnly) {
                        $result[] = $localKey;
                    } else {
                        $result[$localKey] = apc_fetch($localKey);
                    }
                }
            }
        }
        return new ArrayObject($result);
    }
}
