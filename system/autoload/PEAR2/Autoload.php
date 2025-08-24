<?php

/**
 * Standard Autoloader for PEAR2
 *
 * PEAR2_Autoload is the standard method of class loading for development and
 * low-volume web sites using PEAR2 packages.
 *
 * PHP version 5
 *
 * @category PEAR2
 * @package  PEAR2_Autoload
 * @author   Gregory Beaver <cellog@php.net>
 * @author   Brett Bieber <saltybeagle@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  0.3.0
 * @link     http://pear2.php.net/PEAR2_Autoload
 */
namespace PEAR2;

if (!class_exists('\PEAR2\Autoload', false)) {
    /**
     * Standard Autoloader for PEAR2
     *
     * PEAR2_Autoload is the standard method of class loading for development
     * and low-volume web sites using PEAR2 packages.
     *
     * PHP version 5
     *
     * @category PEAR2
     * @package  PEAR2_Autoload
     * @author   Gregory Beaver <cellog@php.net>
     * @author   Brett Bieber <saltybeagle@php.net>
     * @license  http://www.opensource.org/licenses/bsd-license.php BSD
     * New BSDLicense
     * @link     http://pear2.php.net/PEAR2_Autoload
     */
    class Autoload
    {
        /**
         * Used at {@link initialize()} to specify that the load function, path
         * and map should be appended to the respective lists.
         */
        const APPEND = 0;

        /**
         * Used at {@link initialize()} to specify that the load function should
         * be prepended on the autoload stack, instead of being appended.
         */
        const PREPEND_LOAD = 1;

        /**
         * Used at {@link initialize()} to specify that the path should be
         * prepended on the list of paths, instead of being appended.
         */
        const PREPEND_PATH = 2;

        /**
         * Used at {@link initialize()} to specify that the map should be
         * prepended on the list of maps, instead of being appended.
         */
        const PREPEND_MAP = 4;

        /**
         * Used at {@link initialize()} to specify that the load function, path
         * and map should be prepended on their respective lists, instead of
         * being appended.
         */
        const PREPEND = 7;

        /**
         * Whether the autoload class has been spl_autoload_register-ed
         *
         * @var bool
         */
        protected static $registered = false;

        /**
         * Array of PEAR2 autoload paths registered
         *
         * @var array
         */
        protected static $paths = array();

        /**
         * Array of classname-to-file mapping
         *
         * @var array
         */
        protected static $map = array();

        /**
         * Array of class maps loaded
         *
         * @var array
         */
        protected static $maps = array();

        /**
         * Last classmap specified
         *
         * @var array
         */
        protected static $mapfile = null;

        /**
         * Array of classes loaded automatically not in the map
         *
         * @var array
         */
        protected static $unmapped = array();

        /**
         * Array of functions to be checked in exception traces.
         *
         * @var array
         */
        protected static $checkFunctions = array(
            'class_exists', 'interface_exists'
        );

        /**
         * Initialize the PEAR2 autoloader
         *
         * @param string $path    Directory path(s) to register.
         * @param string $mapfile Path to a mapping file to register.
         * @param int    $flags   A bitmaks with options for the autoloader.
         * See the PREPEND(_*) constants for details.
         *
         * @return void
         */
        public static function initialize(
            $path,
            $mapfile = null,
            $flags = self::APPEND
        ) {
            self::register(0 !== $flags & self::PREPEND_LOAD);
            self::addPath($path, 0 !== ($flags & self::PREPEND_PATH));
            self::addMap($mapfile, 0 !== ($flags & self::PREPEND_MAP));
        }

        /**
         * Register the PEAR2 autoload class with spl_autoload_register
         *
         * @param bool $prepend Whether to prepend the load function to the
         * autoload stack instead of appending it.
         *
         * @return void
         */
        protected static function register($prepend = false)
        {
            if (!self::$registered) {
                // set up __autoload
                $autoload = spl_autoload_functions();
                spl_autoload_register('PEAR2\Autoload::load', true, $prepend);
                if (function_exists('__autoload') && ($autoload === false)) {
                    // __autoload() was being used, but now would be ignored,
                    // add it to the autoload stack
                    spl_autoload_register('__autoload');
                }
                if (function_exists('trait_exists')) {
                    self::$checkFunctions[] = 'trait_exists';
                }
                self::$registered = true;
            }
        }

        /**
         * Add a path
         *
         * @param string $paths   The folder(s) to add to the set of paths.
         * @param bool   $prepend Whether to prepend the path to the list of
         * paths instead of appending it.
         *
         * @return void
         */
        protected static function addPath($paths, $prepend = false)
        {
            foreach (explode(PATH_SEPARATOR, $paths) as $path) {
                if (!in_array($path, self::$paths)) {
                    if ($prepend) {
                        self::$paths = array_merge(array($path), self::$paths);
                    } else {
                        self::$paths[] = $path;
                    }
                }
            }
        }

        /**
         * Add a classname-to-file map
         *
         * @param string $mapfile The filename of the classmap.
         * @param bool   $prepend Whether to prepend the map to the list of maps
         * instead of appending it.
         *
         * @return void
         */
        protected static function addMap($mapfile, $prepend = false)
        {
            if (!in_array($mapfile, self::$maps)) {
                // keep track of specific map file loaded in this
                // instance so we can update it if necessary
                self::$mapfile = $mapfile;

                if (is_file($mapfile)) {
                    $map = include $mapfile;
                    if (is_array($map)) {
                        // mapfile contains a valid map, so we'll keep it
                        if ($prepend) {
                            self::$maps = array_merge(
                                array($mapfile),
                                self::$maps
                            );
                            self::$map = array_merge($map, self::$map);
                        } else {
                            self::$maps[] = $mapfile;
                            self::$map = array_merge(self::$map, $map);
                        }
                    }
                }

            }
        }

        /**
         * Check if the class is already defined in a classmap
         *
         * @param string $class The class to look for
         *
         * @return bool
         */
        protected static function isMapped($class)
        {
            if (isset(self::$map[$class])) {
                return true;
            }
            if (isset(self::$mapfile) && ! isset(self::$map[$class])) {
                self::$unmapped[] = $class;
                return false;
            }
            return false;
        }

        /**
         * Load a PEAR2 class
         *
         * @param string $class The class to load
         *
         * @return bool
         */
        public static function load($class)
        {
            // need to check if there's a current map file specified ALSO.
            // this could be the first time writing it.
            $mapped = self::isMapped($class);
            if ($mapped && is_file(self::$map[$class])) {
                include self::$map[$class];
                if (!self::loadSuccessful($class)) {
                    // record this failure & keep going, we may still find it
                    self::$unmapped[] = $class;
                } else {
                    return true;
                }
            }

            $file = '';
            $className = $class;
            if (false !== $lastNsPos = strrpos($class, '\\')) {
                $namespace = substr($class, 0, $lastNsPos);
                $className = substr($class, $lastNsPos + 1);
                $file = str_replace(
                    '\\',
                    DIRECTORY_SEPARATOR,
                    $namespace
                ) . DIRECTORY_SEPARATOR;
            }
            $file .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            foreach (self::$paths as $path) {
                if (is_file($path . DIRECTORY_SEPARATOR . $file)) {
                    include $path . DIRECTORY_SEPARATOR . $file;
                    if (!self::loadSuccessful($class)) {
                        if (count(spl_autoload_functions()) > 1) {
                            return false;
                        }
                        throw new \Exception(
                            'Class ' . $class . ' was not present in ' .
                            $path . DIRECTORY_SEPARATOR . $file .
                            '") [PEAR2_Autoload-@PACKAGE_VERSION@]'
                        );
                    }

                    if (in_array($class, self::$unmapped)) {
                        self::updateMap(
                            $class,
                            $path . DIRECTORY_SEPARATOR . $file
                        );
                    }
                    return true;
                }
            }
            if (count(spl_autoload_functions()) > 1) {
                return false;
            }
            $e = new \Exception(
                'Class ' . $class . ' could not be loaded from ' .
                $file . ', file does not exist (registered paths="' .
                implode(PATH_SEPARATOR, self::$paths) .
                '") [PEAR2_Autoload-@PACKAGE_VERSION@]'
            );
            $trace = $e->getTrace();
            if (isset($trace[2]) && isset($trace[2]['function'])
                && in_array($trace[2]['function'], self::$checkFunctions)
            ) {
                return false;
            }
            if (isset($trace[1]) && isset($trace[1]['function'])
                && in_array($trace[1]['function'], self::$checkFunctions)
            ) {
                return false;
            }
            throw $e;
        }

        /**
         * Check if the requested class was loaded from the specified path
         *
         * @param string $class The name of the class to check.
         *
         * @return bool
         */
        protected static function loadSuccessful($class)
        {
            return class_exists($class, false)
                || interface_exists($class, false)
                || (in_array('trait_exists', self::$checkFunctions, true)
                && trait_exists($class, false));
        }

        /**
         * If possible, update the classmap file with newly-discovered
         * mapping.
         *
         * @param string $class  Class name discovered
         * @param string $origin File where class was found
         *
         * @return void
         */
        protected static function updateMap($class, $origin)
        {
            if (is_writable(self::$mapfile)
                || is_writable(dirname(self::$mapfile))
            ) {
                self::$map[$class] = $origin;
                file_put_contents(
                    self::$mapfile,
                    '<'."?php\n"
                    . "// PEAR2\Autoload auto-generated classmap\n"
                    . "return " . var_export(self::$map, true) . ';',
                    LOCK_EX
                );
            }
        }

        /**
         * Return the array of paths PEAR2 autoload has registered
         *
         * @return array
         */
        public static function getPaths()
        {
            return self::$paths;
        }
    }
}
Autoload::initialize(dirname(__DIR__));
