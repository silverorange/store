<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An item for an e-commerce web application
 *
 * Items are the lowest level in the product structure. Each product can have
 * several items. For example, you could have a tee-shirt product and several
 * items under the product describing different sizes or colours.
 *
 * <pre>
 * Category
 * |
 * -- Product
 *    |
 *    -- Item
 * </pre>
 *
 * Creating StoreItem objects is necessary when the items are on the current
 * page and must be displayed. Some StoreItem objects are stored in the
 * customer's session because they are in the customer's cart.
 *
 * If there are many StoreItem objects that must be loaded for a page request,
 * the MDB2 wrapper class called StoreItemWrapper should be used to load the
 * objects.
 *
 * This class contains mostly data.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItemWrapper
 */
class StoreItem extends SwatDBDataObject
{
	/**
	 * The id in the database
	 */
	public $id;
	public $title;
	public $description;
	public $show;
	public $unit;
	public $tag;
	public $status
	public $price;
	public $weight
	public $hide_price_range;

	private $db_field_blacklist = array('addresses');

	/**
	 * Loads an item from the database into this object
	 *
	 * @param integer $id the database id of the item to load.
	 *
	 * @return boolean true if the item was found in the database and false
	 *                  if the item was not found in the database.
	 */
	public function loadFromDB($id)
	{
		$fields = array_diff(array_keys($this->getProperties()), $this->db_field_blacklist);
		$row = SwatDB::queryRow($this->app->db, 'items', $fields, 'item_id', $id);
		$this->initFromRow($row);
		$this->generatePropertyHashes();
	}

	public function saveToDB()
	{
	}
}

class StoreItemView
{
	private $item;

	public function __construct($item);

	public function display();

	public function getItem();
	public function setItem($item);
}

?>
