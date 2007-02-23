<?php

/**
 * A single entry in a {@link StorePath}
 *
 * After a path entry is created, its properties are readable but not writeable.
 * Path entries have the following readable properties:
 * - id
 * - parent
 * - shortname
 * - title
 *
 * @package   Store
 * @copyright 2004-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePath
 */
class StorePathEntry
{
	// {{{ protected properties

	/**
	 * The database id of this entry
	 *
	 * @var integer
	 */
	protected $id;

	/**
	 * The database id of the parent of this entry or null if this entry
	 * does not have a parent
	 *
	 * @var integer
	 */
	protected $parent;

	/**
	 * The shortname of this entry
	 *
	 * @var string
	 */
	protected $shortname;

	/**
	 * The title of this entry
	 *
	 * @var string
	 */
	protected $title;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new path entry
	 *
	 * @param integer $id the database id of this entry.
	 * @param integer $parent the database id of the parent of this entry or
	 *                         null if this entry does not have a parent.
	 * @param string $shortname the shortname of this entry.
	 * @param string $title the title of this entry.
	 */
	public function __construct($id, $parent, $shortname, $title)
	{
		$this->id = $id;
		$this->parent = $parent;
		$this->shortname = $shortname;
		$this->title = $title;
	}

	// }}}
	// {{{ private function __get()

	/**
	 * Magic get for allowing reading but not writing of path entry properties
	 *
	 * @param string $name the name of the property to get.
	 *
	 * @return mixed the value of the property if the property is readable.
	 */
	private function __get($name)
	{
		static $readable_properties = array(
			'id',
			'parent',
			'shortname',
			'title',
		);

		if (in_array($name, $readable_properties))
			return $this->$name;
	}

	// }}}
}

?>
