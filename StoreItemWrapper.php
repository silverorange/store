<?php

class StoreItemWrapper extends DBWrapper {
	/**
	 * An array of StoreItems this wrapper returns
	 *
	 * @var array
	 * @access private
	 */
	private var $items;

	/**
	 * An internal array pointer pointing to the current array element
	 *
	 * @var int
	 */
	private var $current_item = 0;
	
	/**
	 * Creates a new wrapper object
	 *
	 * The constructor here takes the result set of a MDB2 query and translates
	 * it into an array of objects.
	 *
	 * @param $rs resource the result set of the MDB2 query to get the items.
	 *
	 * @access public
	 */
	public function __construct($rs);

	/**
	 * Gets the next item from the items array
	 *
	 * @return the next item in the array, or null if there are no more items.
	 */
	public function getNextItem();
	
	public function isEmpty();

	public function getItemCount();

}

?>
