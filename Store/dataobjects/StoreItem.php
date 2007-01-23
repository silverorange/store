<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreQuantityDiscountWrapper.php';
require_once 'Store/dataobjects/StoreItemRegionBindingWrapper.php';
require_once 'Store/dataobjects/StoreItemGroup.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * An item for an e-commerce web application
 *
 * Items are the lowest level in the product structure. Each product can have
 * several items. For example, you could have a tee-shirt product and several
 * items under the product describing different sizes or colours.
 *
 * Sites are expected to subclass this class to add site-speific properties
 * such as weight, units, status and whether or not to show the price range.
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
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItemWrapper
 */
abstract class StoreItem extends StoreDataObject
{
	// {{{ constants
	/**
	 * Shown on site and available for order
	 *
	 * No special note is displayed.
	 */
	//abstract const STATUS_AVAILABLE;

	/**
	 * Shown on the site but unavailable for ordering
	 *
	 * Items are displayed with a note indicating the item is not in stock.
	 */
	//abstract const STATUS_OUT_OF_STOCK;

	// }}}
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Merchant's stocking keeping unit (SKU) identifier
	 *
	 * @var string
	 */
	public $sku;

	/**
	 * User visible description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * If this item is enabled for the limiting region
	 *
	 * This field is joined from the ItemRegionBinding table; it is not a
	 * regular field. Only read from the enabled property.
	 *
	 * @var boolean
	 *
	 * @todo I assume, like price below make this protected with either an
	 *       accessor method or a magic get implementation or a fake autoloader
	 *       method.
	 */
	public $enabled;

	/**
	 * The status of an item - backordered, etc
	 *
	 * @var integer
	 */
	public $status;

	/**
	 * Unit cost of this item
	 *
	 * This field is joined from the ItemRegionBinding table; it is not a
	 * regular field. Only read from the price property.
	 *
	 * @var float
	 *
	 * @todo Make this protected with either an accessor method or a magic get
	 *       implementation or a fake autoloader method.
	 */
	public $price;

	// }}}
	// {{{ protected properties

	/**
	 * @var StoreRegion
	 */
	protected $region;

	/**
	 * @var boolean
	 */
	protected $limit_by_region = true;

	// }}}
	// {{{ public function setRegion()

	/**
	 * Sets the region to use when loading region-specific fields for this item
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to not load this item if it is
	 *                           not available in the given region.
	 */
	public function setRegion(StoreRegion $region, $limiting = true)
	{
		$this->region = $region;
		$this->limit_by_region = $limiting;
	}

	// }}}
	// {{{ public function isAvailableInRegion()

	/**
	 * Whether or not this item is available for purchase in the given region
	 *
	 * The item needs to have an id before this method will work.
	 *
	 * @param StoreRegion $region the region to check the availability of this
	 *                             item for.
	 *
	 * @return boolean true if this item is available for purchase in the
	 *                  given region and false if it is not.
	 *
	 * @throws StoreException if this item has no id defined.
	 */
	public function isAvailableInRegion(StoreRegion $region)
	{
		if ($this->id === null)
			throw new StoreException('Item must have an id set before region '.
				'availability can be determined.');

		// if this item has an is_available value set for the given region use
		// it instead of performing another query
		if ($this->hasInternalValue('region') &&
			$this->getInternalValue('region') == $region->id &&
			$this->hasInternalValue('is_available') &&
			$this->getInternalValue('is_available') !== null) {

			$available = $this->getInternalValue('is_available');
		} else {
			$sql = 'select count(item) from AvailableItemView
					where AvailableItemView.item = %s
					and AvailableItemView.region = %s';

			$sql = sprintf($sql,
				$this->db->quote($this->id, 'integer'),
				$this->db->quote($region->id, 'integer'));

			$available = (SwatDB::queryOne($this->db, $sql) > 0);
		}

		return $available;
	}

	// }}}
	// {{{ abstract public function hasAvailableStatus()

	/**
	 * Gets whether or not this item has a status which makes this item
	 * available for purchase
	 *
	 * @return boolean true if this item has a status making it available for
	 *                  purchase and false if this item has a status making it
	 *                  unavailable for purchase.
	 */
	abstract public function hasAvailableStatus();

	// }}}
	// {{{ abstract public function getDescription()

	/**
	 * Gets a description for this item
	 *
	 * @param boolean $include_item_group an optional paramater that specifies
	 *                                     whether or not to include item-group
	 *                                     information in the description. By
	 *                                     default, item-group information is
	 *                                     included.
	 *
	 * @return string a description for this item.
	 *
	 * @see StoreItem::getDetailedDescription()
	 */
	abstract public function getDescription($include_item_group = true);

	// }}}
	// {{{ abstract public function getDetailedDescription()

	/**
	 * Gets a detailed description for this item
	 *
	 * @return string a detailed description for this item.
	 *
	 * @see StoreItem::getDescription()
	 */
	abstract public function getDetailedDescription();

	// }}}
	// {{{ public static function validateSku()

	/**
	 * Validates a new item SKU in the given catalogue
	 *
	 * SKU's must be unique across all catalogues with the exception that they
	 * can exist multiple times amongst catalogues that are clones of each
	 * other and multiple times within a single product.
	 *
	 * @param string $sku the new SKU.
	 * @param integer $catalog_id the database identifier of the catalogue to
	 *                             validate the new SKU in.
	 * @param integer $product the product the SKU belongs to.
	 * @param array $valid_skus an optional array of SKUs to ignore when
	 *                           validating.
	 * @return true if the new SKU is valid in the given catalogue and product
	 *          and false
	 *          if it is not.
	 */
	public static function validateSku($db, $sku, $catalog_id, $product_id,
		$valid_skus = array())
	{
		$sql = 'select count(ItemView.id) from itemView 
			inner join Product on ItemView.product = Product.id 
			inner join Catalog on Product.catalog = Catalog.id 
			where Catalog.id not in 
				(select clone from CatalogCloneView where catalog = %s) 
				and Product.id != %s 
			and ItemView.sku = %s';

		$sql = sprintf($sql,
			$db->quote($catalog_id, 'integer'),
			$db->quote($product_id, 'integer'),
			$db->quote($sku, 'text'));
		
		if (count($valid_skus) > 0) {
			$sql.= sprintf(' and ItemView.sku not in (%s)',
				$db->implodeArray($valid_skus, 'text'));
		}

		return (SwatDB::queryOne($db, $sql) == 0);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('is_available');
		$this->registerInternalProperty('region',
			$this->class_map->resolveClass('StoreRegion'));

		$this->registerInternalProperty('product',
			$this->class_map->resolveClass('StoreProduct'));

		$this->registerInternalProperty('item_group',
			$this->class_map->resolveClass('StoreItemGroup'));

		$this->table = 'Item';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadInteral()

	/**
	 * If a limiting region is specified, load() will automatically load
	 * region specific fields for this item
	 *
	 * @param integer $id the id of the item to load into this object.
	 *
	 * @see StoreItem::setRegion()
	 */
	protected function loadInternal($id)
	{
		if ($this->region === null) {
			$row = parent::loadInternal($id);
		} else {
			$id_field = new SwatDBField($this->id_field, 'integer');
			$sql = 'select Item.*, ItemRegionBinding.price,
					ItemRegionBinding.region,
					ItemRegionBinding.enabled
				from Item
				%s ItemRegionBinding on item = Item.id
					and ItemRegionBinding.region = %s
				where Item.id = %s';

			$sql = sprintf($sql,
				$this->limit_by_region ? 'inner join' : 'left outer join',
				$this->db->quote($this->region->id, 'integer'),
				$this->db->quote($id, 'integer'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		return $row;
	}

	// }}}

	// loader methods
	// {{{ protected function loadQuantityDiscounts()

	protected function loadQuantityDiscounts()
	{
		if (!$this->hasInternalValue('region'))
			return null;

		$region = $this->getInternalValue('region');

		$sql = 'select QuantityDiscount.*, QuantityDiscountRegionBinding.price
			from QuantityDiscount 
			inner join QuantityDiscountRegionBinding on
			quantity_discount = QuantityDiscount.id ';

		if ($region !== null)
			$sql.= sprintf('and region = %s ',
				$this->db->quote($region, 'integer'));
                  
		$sql.= 'where QuantityDiscount.item = %s
			order by QuantityDiscount.quantity asc';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper =
			$this->class_map->resolveClass('StoreQuantityDiscountWrapper');

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function loadRegionBindings()

	protected function loadRegionBindings()
	{
		$sql = 'select * from ItemRegionBinding where item = %s';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper =
			$this->class_map->resolveClass('StoreItemRegionBindingWrapper');

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
}

?>
