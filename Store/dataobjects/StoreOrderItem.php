<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreRegion.php';
require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/dataobjects/StoreCartEntry.php';

/**
 * An item in an order
 *
 * A single order contains multiple order items. An order item contains all
 * price, product, quantity and discount information from when the order was
 * placed. An order item is a combination of important fields from an item,
 * a cart entry and a product.
 *
 * You can automatically create StoreOrderItem objects from StoreCartEntry
 * objects using the {@link StoreCartEntry::createOrderItem()} method.
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCartEntry::createOrderItem()
 */
class StoreOrderItem extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Merchant's stocking keeping unit (SKU)
	 *
	 * @var string
	 */
	public $sku;

	/**
	 * Sku Alias
	 *
	 * @var string
	 */
	public $alias_sku;

	/**
	 * Quantity
	 *
	 * @var integer
	 */
	public $quantity;

	/**
	 * Price
	 *
	 * @var float
	 */
	public $price;

	/**
	 * Whether or not this item has a custom-overide price
	 *
	 * @var boolean
	 */
	public $custom_price;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Extension
	 *
	 * @var float
	 */
	public $extension;

	/**
	 * Item identifier
	 *
	 * @var integer
	 */
	public $item;

	/**
	 * Product identifier
	 *
	 * @var integer
	 */
	public $product;

	/**
	 * Product title
	 *
	 * @var string
	 */
	public $product_title;

	/**
	 * Title of item group if this item belonged to an item group
	 *
	 * @var string
	 */
	public $item_group_title;

	/**
	 * Catalog id
	 *
	 * @var integer
	 */
	public $catalog;

	/**
	 * Where this order item was created.
	 *
	 * Uses StoreCartEntry::SOURCE_* constants
	 *
	 * @var integer
	 * @see StoreCartEntry
	 */
	public $source;

	/**
	 * Category related to  the source of this order item.
	 *
	 * @var integer
	 * @see StoreCartEntry
	 */
	public $source_category;

	/**
	 * Whether or not this item was ordered through the quick-order tool
	 *
	 * @var boolean
	 */
	public $quick_order;

	/**
	 * Sale discount identifier
	 *
	 * @var integer
	 */
	public $sale_discount;

	/**
	 * Discount off normal price
	 *
	 * @float
	 */
	public $discount;

	/**
	 * Discount extension
	 *
	 * @float
	 */
	public $discount_extension;

	// }}}
	// {{{ protected properties

	/**
	 * Cart entry id this order item was created from.
	 *
	 * @var integer
	 */
	protected $cart_entry_id = null;

	/**
	 * Cache of region-available StoreItem for this order item
	 *
	 * Array keys are region ids. Array values are {@link StoreItem} items
	 * or null if no items are available.
	 *
	 * @var array
	 * @see StoreOrderItem::getAvailableItem()
	 */
	protected $available_items_cache = array();

	/**
	 * Cache of StoreItem for this order item
	 *
	 * @var StoreItem
	 */
	protected $item_cache = false;

	// }}}
	// {{{ public function getDescription()

	/**
	 * Gets the description for this order item
	 *
	 * @return string the description for this order item.
	 */
	public function getDescription()
	{
		return $this->description;
	}

	// }}}
	// {{{ public function setCartEntryId()

	public function setCartEntryId($id)
	{
		$this->cart_entry_id = $id;
	}

	// }}}
	// {{{ public function getCartEntryId()

	public function getCartEntryId()
	{
		return $this->cart_entry_id;
	}

	// }}}
	// {{{ public function getAvailableItemId()

	/**
	 * Gets the id of the item belonging to this order item if the item is
	 * still available on the site
	 *
	 * @param StoreRegion $region the region to get the item in.
	 *
	 * @return integer the id of the item belonging to this order item or null
	 *                  if no such item exists.
	 */
	public function getAvailableItemId(StoreRegion $region)
	{
		$sql = 'select Item.id from Item
			inner join AvailableItemView
				on AvailableItemView.item = Item.id
				and AvailableItemView.region = %s
			where Item.id = %s';

		$sql = sprintf($sql,
			$this->db->quote($region->id, 'integer'),
			$this->db->quote($this->item, 'integer'));

		$id = SwatDB::queryOne($this->db, $sql);

		if ($id === null) {
			$sql = 'select Item.id from Item
				inner join AvailableItemView
					on AvailableItemView.item = Item.id
					and AvailableItemView.region = %s
				where Item.sku = %s';

			$sql = sprintf($sql,
				$this->db->quote($region->id, 'integer'),
				$this->db->quote($this->sku, 'text'));

			$id = SwatDB::queryOne($this->db, $sql);
		}

		return $id;
	}

	// }}}
	// {{{ public function getAvailableItem()

	/**
	 * Gets StoreItem belonging to this order item if the item is
	 * still available on the site
	 *
	 * @param StoreRegion $region the region to get the item in.
	 *
	 * @return StoreItem The currently available item related to this order
	 *                   item.
	 */
	public function getAvailableItem(StoreRegion $region)
	{
		if (isset($this->available_items_cache[$region->id])) {
			$item = $this->available_items_cache[$region->id];
		} else {
			$item = null;

			$wrapper = SwatDBClassMap::get('StoreItemWrapper');

			$sql = sprintf(
				'select Item.* from Item
				inner join AvailableItemView
					on AvailableItemView.item = Item.id
					and AvailableItemView.region = %s
				where Item.id = %s',
				$this->db->quote($region->id, 'integer'),
				$this->db->quote($this->item, 'integer'));

			$item = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

			// if lookup by id failed, try lookup by sku
			if (!$item instanceof StoreItem && $this->sku != '') {
				$sql = sprintf(
					'select Item.* from Item
					inner join AvailableItemView
						on AvailableItemView.item = Item.id
						and AvailableItemView.region = %s
					where Item.sku = %s',
					$this->db->quote($region->id, 'integer'),
					$this->db->quote($this->sku, 'text'));

				$item = SwatDB::query($this->db, $sql, $wrapper)->getFirst();
			}

			$this->setAvailableItemCache($region, $item);
		}

		return $item;
	}

	// }}}
	// {{{ public function setAvailableItemCache()

	public function setAvailableItemCache(StoreRegion $region,
		StoreItem $item = null)
	{
		$this->available_items_cache[$region->id] = $item;
	}

	// }}}
	// {{{ public function getItem()

	/**
	 * Gets the StoreItem belonging to this order item
	 *
	 * The item is retrieved using the loose binding field OrderItem.item. If
	 * that fails, the loose binding OrderItem.sku is attempted.
	 *
	 * @return StoreItem the StoreItem belonging to this order item, or null
	 *                   if the item no longer exists.
	 */
	public function getItem()
	{
		if ($this->item_cache !== false) {
			$item = $this->item_cache;
		} else {
			
			$item = null;

			$wrapper = SwatDBClassMap::get('StoreItemWrapper');

			$sql = sprintf(
				'select * from Item where id = %s',
				$this->db->quote($this->item, 'integer'));

			$item = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

			// if lookup by id failed, try lookup by sku
			if (!($item instanceof StoreItem)) {
				$sql = sprintf(
					'select * from Item where sku = %s',
					$this->db->quote($this->sku, 'text'));

				$item = SwatDB::query($this->db, $sql, $wrapper)->getFirst();
			}

			$this->setItemCache($item);
		}

		return $item;
	}

	// }}}
	// {{{ public function setItemCache()

	public function setItemCache(StoreItem $item = null)
	{
		$this->item_cache = $item;
	}

	// }}}
	// {{{ public function getSourceCategoryTitle()

	public function getSourceCategoryTitle()
	{
		$title = null;

		if ($this->source_category !== null) {
			$this->checkDB();

			$sql = sprintf('select title from Category where id = %s',
				$this->source_category);

			$title = SwatDB::queryOne($this->db, $sql);
		}

		return $title;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('ordernum',
			SwatDBClassMap::get('StoreOrder'));

		$this->registerDeprecatedProperty('quick_order');

		$this->table = 'OrderItem';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		$properties = parent::getSerializablePrivateProperties();
		$properties[] = 'cart_entry_id';
		return $properties;
	}

	// }}}
}

?>
