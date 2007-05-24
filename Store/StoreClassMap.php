<?php

require_once 'Swat/SwatObject.php';

/**
 * This is a deprecated equivalent of {@link SwatDBClassMap}.
 *
 * Maps Store package class names to site-specific overridden class-names
 *
 * @package    Store
 * @copyright  2006 silverorange
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @deprecated This class was incorportated into Swat. Use SwatDBClassMap
 * 	       instead. 
 * @see        SwatDBClassMap
 */
class StoreClassMap extends SwatObject
{
	// {{{ private properties

	/**
	 * Singleton instance
	 *
	 * @var StoreClassMap
	 */
	private static $instance = null;

	/**
	 * An associative array of class-mappings
	 *
	 * The array is of the form 'StoreClass' => 'SiteSpecificOverrideClass'.
	 *
	 * @var array
	 */
	private $map = array();

	/**
	 * The path to search for site-specific class files
	 *
	 * @var string
	 */
	private $path = null;

	// }}}
	// {{{ public static function instance()

	/**
	 * Gets the singleton instance of the class-mapping object
	 *
	 * @return StoreClassMap the singleton instance of the class-
	 *                                  mapping object.
	 */
	public static function instance()
	{
		if (self::$instance === null)
			self::$instance = new self();

		return self::$instance;
	}

	// }}}
	// {{{ public function addMapping()

	/**
	 * Adds a class-mapping to the class-mapping object
	 *
	 * @param string $store_class_name the name of the Store package 
	 *                                  class to override.
	 * @param string $class_nam the name of the site-specific class.
	 */
	public function addMapping($store_class_name, $class_name)
	{
		$this->map[$store_class_name] = $class_name;
	}

	// }}}
	// {{{ public function resolveClass()

	/**
	 * Gets the appropriate class name for a given Store package class name
	 *
	 * @param string $name the name of the Store package class to get the
	 *                      mapped name of.
	 *
	 * @return string the appropriate class name for site-specific code. If
	 *                 the site-specific code has overridden a Store package
	 *                 class, the site-specific overridden value is
	 *                 returned. Otherwise, the Store package default class
	 *                 name is returned.
	 */
	public function resolveClass($name)
	{
		$class_name = $name;

		if (array_key_exists($name, $this->map)) {
			$class_name = $this->map[$name];

			if (!class_exists($class_name) && $this->path !== null) {
				$class_file = sprintf('%s/%s.php', $this->path, $class_name);
				require_once $class_file;
			}
		}

		return $class_name;
	}

	// }}}
	// {{{ public function setPath()

	/**
	 * Sets the path to search for site-specific class files
	 *
	 * @param string $path the path to search for site-specific class files.
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Creates a Store class-mapping object
	 *
	 * The constructor is private as this class uses the singleton pattern.
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
