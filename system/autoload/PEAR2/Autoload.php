<?php
namespace PEAR2;
if (!class_exists('\PEAR2\Autoload', false)) {
    class Autoload
    {
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
         * Initialize the PEAR2 autoloader
         * 
         * @param string $path Directory path to register
         * 
         * @return void
         */
        static function initialize($path, $mapfile = null)
        {
            self::register();
            self::addPath($path);
            self::addMap($mapfile);
        }

        /**
         * Register the PEAR2 autoload class with spl_autoload_register
         * 
         * @return void
         */
        protected static function register()
        {
            if (!self::$registered) {
                // set up __autoload
                $autoload = spl_autoload_functions();
                spl_autoload_register('PEAR2\Autoload::load');
                if (function_exists('__autoload') && ($autoload === false)) {
                    // __autoload() was being used, but now would be ignored, add
                    // it to the autoload stack
                    spl_autoload_register('__autoload');
                }
            }
            self::$registered = true;
        }

        /**
         * Add a path
         * 
         * @param string $path The directory to add to the set of PEAR2 paths
         * 
         * @return void
         */
        protected static function addPath($path)
        {
            if (!in_array($path, self::$paths)) {
                self::$paths[] = $path;
            }
        }

        /**
         * Add a classname-to-file map
         *
         * @param string $mapfile The filename of the classmap
         *
         * @return void
         */
        protected static function addMap($mapfile)
        {
            if (! in_array($mapfile, self::$maps)) {
                
                // keep track of specific map file loaded in this
                // instance so we can update it if necessary                
                self::$mapfile = $mapfile;
                
                if (file_exists($mapfile)) {
                    $map = include $mapfile;
                    if (is_array($map)) {
                        // mapfile contains a valid map, so we'll keep it
                        self::$maps[] = $mapfile;                        
                        self::$map = array_merge(self::$map, $map);
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
        static function load($class)
        {
            // need to check if there's a current map file specified ALSO.
            // this could be the first time writing it.
            $mapped = self::isMapped($class);
            if ($mapped) {
                require self::$map[$class];
                if (!self::loadSuccessful($class)) {
                    // record this failure & keep going, we may still find it
                    self::$unmapped[] = $class;
                } else {
                    return true;
                }
            }

            $file = str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $class) . '.php';
            foreach (self::$paths as $path) {
                if (file_exists($path . DIRECTORY_SEPARATOR . $file)) {
                    require $path . DIRECTORY_SEPARATOR . $file;
                    if (!self::loadSuccessful($class)) {
                        throw new \Exception('Class ' . $class . ' was not present in ' .
                            $path . DIRECTORY_SEPARATOR . $file .
                            '") [PEAR2_Autoload-0.2.4]');
                    }
                    
                    if (in_array($class, self::$unmapped)) {
                        self::updateMap($class, $path . DIRECTORY_SEPARATOR . $file);
                    }
                    return true;
                }
            }
            
            $e = new \Exception('Class ' . $class . ' could not be loaded from ' .
                $file . ', file does not exist (registered paths="' .
                implode(PATH_SEPARATOR, self::$paths) .
                '") [PEAR2_Autoload-0.2.4]');
            $trace = $e->getTrace();
            if (isset($trace[2]) && isset($trace[2]['function']) &&
                  in_array($trace[2]['function'], array('class_exists', 'interface_exists'))) {
                return false;
            }
            if (isset($trace[1]) && isset($trace[1]['function']) &&
                  in_array($trace[1]['function'], array('class_exists', 'interface_exists'))) {
                return false;
            }
            throw $e;
        }

        /**
         * Check if the requested class was loaded from the specified path
         * 
         * @return bool
         */
        protected static function loadSuccessful($class)
        {
            if (!class_exists($class, false) && !interface_exists($class, false)) {
                return false;
            }
            return true;
        }
        
        /**
         * If possible, update the classmap file with newly-discovered 
         * mapping.
         * 
         * @param string $class Class name discovered
         * 
         * @param string $origin File where class was found
         * 
         */
        protected static function updateMap($class, $origin)
        {
            if (is_writable(self::$mapfile) || is_writable(dirname(self::$mapfile))) {
                self::$map[$class] = $origin;
                file_put_contents(self::$mapfile, 
                    '<'."?php\n"
                    . "// PEAR2\Autoload auto-generated classmap\n"
                    . "return " . var_export(self::$map, true) . ';',
                    LOCK_EX
                );
            }
        }
        
        /**
         * return the array of paths PEAR2 autoload has registered
         * 
         * @return array
         */
        static function getPaths()
        {
            return self::$paths;
        }
    }
}
Autoload::initialize(dirname(__DIR__));