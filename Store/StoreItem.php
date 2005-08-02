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
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The visible title of this item
	 *
	 * @var string
	 */
	public $title;

	/**
	 * A short description of this item
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Whether or not this item is to be shown on the site
	 *
	 * @var boolean
	 */
	public $show;

	/**
	 * The unit of measurement of this item
	 *
	 * @var ???
	 */
	public $unit;

	/**
	 * @var ???
	 */
	public $tag;

	/**
	 * @var ???
	 */
	public $status

	/**
	 * The unit cost of this item
	 *
	 * @var double
	 */
	public $price;

	/**
	 * The unit weight of this item (in kg)
	 *
	 * @var double
	 */
	public $weight

	/**
	 * Whether or not to show the price range of this item on the site
	 *
	 * @var boolean
	 */
	public $show_price_range;

	/**
	 * An array of property names of this object that are not database fields
	 *
	 * @var array
	 */
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
		$fields = array_diff(array_keys($this->getProperties()),
			$this->db_field_blacklist);

		$row = SwatDB::queryRow($this->app->db, 'items',
			$fields, 'item_id', $id);

		$this->initFromRow($row);
		$this->generatePropertyHashes();
	}

	/**
	 * Saves this item object to the database
	 *
	 * Only modified properties are updated and if this item does not have
	 * an id set or the id is 0 then it is inserted instead of updated.
	 *
	 * @return boolean true on successfully saving and false on failure
	 *                  to save.
	 */
	public function saveToDB()
	{
	}
}

?>
