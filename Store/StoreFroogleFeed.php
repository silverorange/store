<?php

require_once 'AtomFeed/AtomFeed.php';
require_once 'Store/StoreFroogleFeedEntry.php';

/**
 * A class for constructing Froogle Atom feeds
 *
 * @package   AtomFeed
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFroogleFeed extends AtomFeed
{
	// {{{ public function __construct()

	/**
	 * Creates a new Atom feed 
	 */
	public function __construct()
	{
		$this->addNameSpace('g', 'http://base.google.com/ns/1.0');
	}

	// }}}
	// {{{ public static function getTextNode()

	/**
	 * Get text node
	 */
	public static function getTextNode($document, $name, $value, $name_space = null)
	{
		// value must be text-only
		$value = strip_tags($value);

		return parent::getTextNode($document, $name, $value, $name_space);
	}

	// }}}
	// {{{ public static function getDateNode()

	/**
	 * Get date node
	 */
	public static function getDateNode($document, $name, $date, $name_space = null)
	{
		if ($name == 'expiration_date') {
			if ($name_space !== null)
				$name = $name_space.':'.$name;

			if ($date === null || !$date instanceof Date)
				throw new PEAR_Exception(sprintf('%s is not a Date', $name));

			return self::getTextNode($document, 
				$name,
				$date->format('%Y-%m-%d'));
		} else {
			return parent::getDateNode($document, $name, $date, $name_space);
		}

	}

	// }}}
}

?>
