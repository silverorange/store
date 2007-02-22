<?php

require_once 'Store/StoreCategoryPathEntry.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
class StoreCategoryPath implements Iterator, Countable
{
	// {{{ private properties

	/**
	 * An array of the objects created by this wrapper
	 *
	 * @var array
	 */
	private $path_entries = array();

	/**
	 * The current index of the iterator interface
	 *
	 * @var integer
	 */
	private $current_index = 0;
	
	// }}}
	// {{{ public function __construct

	/**
	 * Creates a new path object
	 *
	 * @param integer category id.
	 */
	public function __construct($app, $category_id = null)
	{
		if ($category_id === null)
			return;

		$class_map = StoreClassMap::instance();
		$entry_class = $class_map->resolveClass('StoreCategoryPathEntry');

		foreach ($this->queryPath($app, $category_id) as $row)
			$this->path_entries[] = new $entry_class($row);

		$this->path_entries = array_reverse($this->path_entries);
	}

	// }}}
	// {{{ public function current()

	/**
	 * Returns the current element
	 *
	 * @return mixed the current element.
	 */
	public function current()
	{
		return $this->path_entries[$this->current_index];
	}

	// }}}
	// {{{ public function key()

	/**
	 * Returns the key of the current element
	 *
	 * @return integer the key of the current element
	 */
	public function key()
	{
		return $this->current_index;
	}

	// }}}
	// {{{ public function next()

	/**
	 * Moves forward to the next element
	 */
	public function next()
	{
		$this->current_index++;
	}

	// }}}
	// {{{ public function rewind()

	/**
	 * Rewinds this iterator to the first element
	 */
	public function rewind()
	{
		$this->current_index = 0;
	}

	// }}}
	// {{{ public function valid()

	/**
	 * Checks is there is a current element after calls to rewind() and next()
	 *
	 * @return boolean true if there is a current element and false if there
	 *                  is not.
	 */
	public function valid()
	{
		return isset($this->path_entries[$this->current_index]);
	}

	// }}}
	// {{{ public function get()

	/**
	 * Retrieves the an object
	 *
	 * @return mixed the object or null if it does not exist
	 */
	public function get($key = 0)
	{
		if (isset($this->path_entries[$key]))
			return $this->path_entries[$key];

		return null;
	}

	// }}}
	// {{{ public function getFirst()

	/**
	 * Retrieves the first object
	 *
	 * @return mixed the first object or null if it does not exist
	 */
	public function getFirst()
	{
		if (isset($this->path_entries[0]))
			return $this->path_entries[0];

		return null;
	}

	// }}}
	// {{{ public function getLast()

	/**
	 * Retrieves the last object
	 *
	 * @return mixed the last object or null if no objects exist
	 */
	public function getLast()
	{
		if (count($this) > 0)
			return $this->path_entries[count($this) - 1];

		return null;
	}

	// }}}
	// {{{ public function count()

	/**
	 * Gets the number of objects
	 *
	 * Satisfies the countable interface.
	 *
	 * @return integer the number of objects in this recordset.
	 */
	public function count()
	{
		return count($this->path_entries);
	}

	// }}}
	// {{{ protected function queryPath()

	protected function queryPath($app, $category_id)
	{
		$sql = sprintf('select * from getCategoryPathInfo(%s)',
			$app->db->quote($category_id, 'integer'));

		return SwatDB::query($app->db, $sql);
	}

	// }}}
}

?>
