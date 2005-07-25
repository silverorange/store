<?php

/**
 * Creating StoreItem objects is necessary when the items are on the
 * current page and must be displayed. Some StoreItems are saved in the session
 * because they are in the customer's cart.
 *
 * Pages of StoreItem objects are loaded using a MDB2 wrapper class called
 * StoreItemWrapper.
 *
 * This class contains mostly data.
 */
class StoreItem {

	/**
	 * The id in the database
	 */
	public var $id;
	public var $title;
	public var $description;
	public var $show;
	public var $unit;
	public var $tag;
	public var $status
	public var $price;
	public var $weight
	public var $hidePriceRange;
}

class StoreItemView {

	private var $item;

	public function __construct($item);

	public function display();

	public function getItem();
	public function setItem($item);
}

?>
