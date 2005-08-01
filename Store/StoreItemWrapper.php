<?php

/**
 * A utility class that loads StoreItem objects from the database
 *
 * If there are many StoreItem objects that must be loaded for a page request,
 * this class should be used to load the objects.
 *
 * The typical usage of this object is:
 *
 *  $item_wrapper = new StoreItemWrapper($rs);
 *  while ($item = $item_wrapper->getNextItem()) {
 *      // do something with each item object here ...
 *  }
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemWrapper extends DBWrapper
{
	/**
	 * An array of StoreItem objects this wrapper returns
	 *
	 * @var array
	 */
	private $items;

	/**
	 * An internal array pointer pointing to the current array element
	 *
	 * @var integer
	 */
	private $current_item = 0;

	/**
	 * Creates a new wrapper object
	 *
	 * The constructor takes the result set of a MDB2 query and translates
	 * it into an array of objects.
	 *
	 * @param resource $rs the record set of the MDB2 query to get the items.
	 *
	 * @access public
	 */
	public function __construct($rs)
	{
	}

	/**
	 * Gets the next item from the items array
	 *
	 * @return the next item in the array, or null if there are no more items.
	 */
	public function getNextItem()
	{
	}
	
	/**
	 * Returns whether or not this wrapper contains any items
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
	}

	/**
	 * Gets the number of items this wrapper contains
	 *
	 * @return integer the number of items this wrapper contains.
	 */
	public function getItemCount()
	{
	}
}

?>
