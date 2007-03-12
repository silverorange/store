<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreQuantityDiscountWrapper.php';
require_once 'Store/dataobjects/StoreItemRegionBindingWrapper.php';
require_once 'Store/dataobjects/StoreItemGroup.php';
require_once 'Store/dataobjects/StoreRegion.php';
require_once 'Store/StoreItemStatus.php';
require_once 'Store/StoreItemStatusList.php';

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

	/**
	 * Cache of enabled state of this item indexed by region id
	 *
	 * This is an array of boolean values.
	 *
	 * @var array
	 */
	protected $is_enabled = array();

	/**
	 * Cache of unit cost of this item indexed by region id
	 *
	 * This is an array of floats.
	 *
	 * @var array
	 */
	protected $price = array();

	/**
	 * Cache of availability of this item indexed by region id
	 *
	 * This is an array of boolean values.
	 *
	 * @var array
	 */
	protected $is_available = array();

	/**
	 * The status of an item - backordered, etc
	 *
	 * @var StoreItemStatus
	 *
	 * @see StoreItem::getStatus()
	 * @see StoreItem::setStatus()
	 */
	protected $status;

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

		if ($this->hasSubDataObject('quantity_discounts'))
			foreach ($this->quantity_discounts as $quantity_discount)
				$quantity_discount->setRegion($region, $limiting);
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
		if ($this->region !== null && $this->region->id == $region->id &&
			isset($this->is_available[$region->id])) {
			$available = $this->is_available[$region->id];
		} else {
			$sql = 'select count(item) from AvailableItemView
					where AvailableItemView.item = %s
					and AvailableItemView.region = %s';

			$sql = sprintf($sql,
				$this->db->quote($this->id, 'integer'),
				$this->db->quote($region->id, 'integer'));

			$available = (SwatDB::queryOne($this->db, $sql) > 0);
			$this->is_available[$region->id] = $available;
		}

		return $available;
	}

	// }}}
	// {{{ public function isEnabled()

	/**
	 * Gets whether or not this item is enabled in a region
	 *
	 * @param StoreRegion $region optional. Region for which to get enabled
	 *                             status. If no region is specified, the
	 *                             region set using
	 *                             {@link StoreItem::setRegion()} is used.
	 *
	 * @return boolean true if this item is enabled in the given region and
	 *                  false if it is not.
	 */
	public function isEnabled(StoreRegion $region = null)
	{
		if ($region !== null && !($region instanceof StoreRegion))
			throw new StoreException(
				'$region must be an instance of StoreRegion.');

		// If region is not specified but is set through setRegion() use
		// that region instead.
		if ($region === null && $this->region !== null)
			$region = $this->region;

		// A region is required.
		if ($region === null)
			throw new StoreException(
				'$region must be specified unless setRegion() is called '.
				'beforehand.');

		$enabled = null;

		if ($this->region->id == $region->id &&
			isset($this->is_enabled[$region->id])) {
			$enabled = $this->is_enabled[$region->id];
		} else {
			// Price is not loaded, load from specified region through region
			// bindings.
			$region_bindings = $this->region_bindings;
			foreach ($region_bindings as $binding) {
				if ($binding->getInternalValue('region') == $region->id) {
					$enabled = $binding->enabled;
					$this->is_enabled[$region->id] = $enabled;
					break;
				}
			}
		}

		return $enabled;
	}

	// }}}
	// {{{ public function getPrice()

	/**
	 * Gets the price of this item in a region
	 *
	 * @param StoreRegion $region optional. Region for which to get price. If
	 *                             no region is specified, the region set using
	 *                             {@link StoreItem::setRegion()} is used.
	 *
	 * @return double the price of this item in the given region or null if
	 *                 this item has no price in the given region.
	 */
	public function getPrice($region = null)
	{
		if ($region !== null && !($region instanceof StoreRegion))
			throw new StoreException(
				'$region must be an instance of StoreRegion.');

		// If region is not specified but is set through setRegion() use
		// that region instead.
		if ($region === null && $this->region !== null)
			$region = $this->region;

		// A region is required.
		if ($region === null)
			throw new StoreException(
				'$region must be specified unless setRegion() is called '.
				'beforehand.');

		$price = null;

		if ($this->region->id == $region->id &&
			isset($this->price[$region->id])) {
			$price = $this->price[$region->id];
		} else {
			// Price is not loaded, load from specified region through region
			// bindings.
			$region_bindings = $this->region_bindings;
			foreach ($region_bindings as $binding) {
				if ($binding->getInternalValue('region') == $region->id) {
					$price = $binding->price;
					$this->price[$region->id] = $price;
					break;
				}
			}
		}

		return $price;
	}

	// }}}
	// {{{ public function hasAvailableStatus()

	/**
	 * Gets whether or not this item has a status which makes this item
	 * available for purchase
	 *
	 * @return boolean true if this item has a status making it available for
	 *                  purchase and false if this item has a status making it
	 *                  unavailable for purchase.
	 */
	public function hasAvailableStatus()
	{
		return
			($this->getStatus() === StoreItemStatusList::status('available'));
	}

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
	// {{{ public function getStatus()

	/** 
	 * Gets the status of this item
	 *
	 * @return StoreItemStatus the status of this item or null if this item's
	 *                          status is undefined.
	 */
	public function getStatus()
	{
		if ($this->status === null && $this->hasInternalValue('status')) {
			$list = StoreItemStatusList::statuses();
			$this->status = $list->getById($this->getInternalValue('status'));
		}

		return $this->status;
	}

	// }}}
	// {{{ public function setStatus()

	public function setStatus(StoreItemStatus $status)
	{
		$this->status = $status;
		$this->setInternalValue('status', $status->id);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('status');
		$this->registerInternalProperty('product',
			$this->class_map->resolveClass('StoreProduct'));

		$this->registerInternalProperty('item_group',
			$this->class_map->resolveClass('StoreItemGroup'));

		$this->table = 'Item';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function initFromRow()

	/**
	 * Initializes this item from a row object
	 *
	 * If the row object has a 'region_id' field and any of the fields
	 * 'price', 'enabled', and 'is_available' these values are cached for
	 * subsequent calls to the getPrice(), isEnabled() and
	 * isAvailableInRegion() methods.
	 */
	protected function initFromRow($row)
	{
		parent::initFromRow($row);

		if (is_object($row))
			$row = get_object_vars($row);

		if (isset($row['region_id'])) {
			if (isset($row['price']))
				$this->price[$row['region_id']] = $row['price'];

			if (isset($row['enabled']))
				$this->is_enabled[$row['region_id']] = $row['enabled'];

			if (isset($row['is_available']))
				$this->is_available[$row['region_id']] = $row['is_available'];
		}
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
		if ($this->region !== null)  {
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = 'select Item.*, ItemRegionBinding.price,
					ItemRegionBinding.enabled
				from Item
					inner join ItemRegionBinding on item = Item.id
					and ItemRegionBinding.region = %s
				where %s.%s = %s';

			$sql = sprintf($sql,
				$this->db->quote($this->region->id, 'integer'),
				$this->table,
				$id_field->name,
				$this->db->quote($id, 'integer'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		} else {
			$row = parent::loadInternal($id);
		}

		return $row;
	}

	// }}}

	// loader methods
	// {{{ protected function loadQuantityDiscounts()

	protected function loadQuantityDiscounts()
	{
		$quantity_discounts = null;
		$wrapper =
			$this->class_map->resolveClass('StoreQuantityDiscountWrapper');

		if ($this->region === null) {
			$sql = sprintf('select * from QuantityDiscount 
				where QuantityDiscount.item = %s
				order by QuantityDiscount.quantity asc',
				$this->db->quote($this->id, 'integer'));

			$quantity_discounts = SwatDB::query($this->db, $sql, $wrapper);
		} else {
			$sql = sprintf('select QuantityDiscount.*,
					QuantityDiscountRegionBinding.price,
					QuantityDiscountRegionBinding.region as region_id
				from QuantityDiscount
					%s QuantityDiscountRegionBinding on
					quantity_discount = QuantityDiscount.id and
					region = %s
				where QuantityDiscount.item = %s
				order by QuantityDiscount.quantity asc',
				$this->limit_by_region ? 'inner join' : 'left outer join',
				$this->db->quote($this->region->id, 'integer'),
				$this->db->quote($this->id, 'integer'));

			$quantity_discounts = SwatDB::query($this->db, $sql, $wrapper);
			if ($quantity_discounts !== null)
				foreach ($quantity_discounts as $discount)
					$discount->setRegion($this->region, $this->limit_by_region);
		}

		return $quantity_discounts;
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
