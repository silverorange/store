<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreQuantityDiscountWrapper.php';
require_once 'Store/dataobjects/StoreItemRegionBindingWrapper.php';
require_once 'Store/dataobjects/StoreItemGroup.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/dataobjects/StoreRegion.php';
require_once 'Store/dataobjects/StoreSaleDiscount.php';
require_once 'Store/dataobjects/StoreItemAliasWrapper.php';
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
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItemWrapper
 */
class StoreItem extends SwatDBDataObject
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

	/**
	 * The number of parts a single unit of this item contains
	 *
	 * @var integer
	 */
	public $part_count;

	/**
	 * A user visible unit for each part of this item
	 *
	 * @var string
	 */
	public $part_unit;

	/**
	 * User visible singular unit
	 *
	 * @var string
	 */
	public $singular_unit;

	/**
	 * User visible plural unit
	 *
	 * @var string
	 */
	public $plural_unit;

	/**
	 * Minimum quantity that can be ordered
	 *
	 * @var integer
	 */
	public $minimum_quantity;

	/**
	 * Whether ordering a multiple of minquantity is required
	 *
	 * @var boolean
	 */
	public $minimum_multiple;

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
	 * @param StoreRegion $region optional. The region in which to check if this
	 *                             item is available. If not specified, the
	 *                             last region specified by the
	 *                             {@link StoreItem::setRegion()} method is
	 *                             used.
	 *
	 * @return boolean true if this item is available for purchase in the
	 *                  given region and false if it is not.
	 *
	 * @throws StoreException if this item has no id defined.
	 */
	public function isAvailableInRegion(StoreRegion $region = null)
	{
		if ($this->id === null)
			throw new StoreException('Item must have an id set before region '.
				'availability can be determined.');

		if ($region === null)
			$region = $this->region;

		if ($region === null)
			throw new SwatException('Region must be specified or region must '.
				'be set on this item before availability is known.');

		if ($region->id === null)
			throw new StoreException('Region have an id set before '.
				'availability can be determined for this item.');

		// if this item has an is_available value set for the given region use
		// it instead of performing another query
		if ($this->region !== null && $this->region->id == $region->id &&
			isset($this->is_available[$region->id])) {
			$available = $this->is_available[$region->id];
		} else {
			$this->checkDB();

			if ($this->id === null)
				throw new StoreException('Item must have an id set before '.
					'availability can be determined for this region.');

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

		if ($this->region !== null && $this->region->id == $region->id &&
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

		if ($this->region !== null && $this->region->id == $region->id &&
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
	// {{{ public function getDisplayPrice()

	/**
	 * Gets the displayable price of this item including any sale discounts
	 *
	 * @param StoreRegion $region optional. Region for which to get price. If
	 *                             no region is specified, the region set using
	 *                             {@link StoreItem::setRegion()} is used.
	 *
	 * @return double the displayable price of this item in the given region or
	 *                 null if this item has no price in the given region.
	 */
	public function getDisplayPrice($region = null)
	{
		$price = $this->getPrice($region);

		$sale = $this->getActiveSaleDiscount();
		if ($sale !== null)
			$price -= ($price * $sale->discount_percentage);

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
	// {{{ public function getDescription()

	/**
	 * Gets the description for this item
	 *
	 * @param boolean $check_if_description_matches_title Whether to check
	 *        product title and suppress it if it matches item description.
	 *
	 * @return string a description for this item.
	 */
	public function getDescription(
		$check_if_description_matches_title = true)
	{
		return ($check_if_description_matches_title &&
			$this->description == $this->product->title) ?
				null : $this->description;
	}

	// }}}
	// {{{ public function getDescriptionArray()

	/**
	 * Get an array of descriptive elements for this item.
	 *
	 * The array is indexed by identifiers for each description type. Possible
	 * keys are: description, part_count, group.
	 *
	 * @param boolean $check_if_description_matches_title Whether to check
	 *        product title and suppress it if it matches item description.
	 *
	 * @return array descriptive elements for this item.
	 */
	public function getDescriptionArray(
		$check_if_description_matches_title = true)
	{
		$description = array();

		$item_description =
			$this->getDescription($check_if_description_matches_title);

		if (strlen($item_description) > 0)
			$description['description'] = $item_description;

		if ($this->part_count > 1)
			$description['part_count'] = sprintf(
				Store::_('%s %s per %s'),
				$this->part_count, $this->part_unit, $this->singular_unit);

		if ($this->item_group !== null && strlen($this->item_group->title) > 0)
			$description['group'] = $this->item_group->title;

		return $description;
	}

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
	// {{{ public function getActiveSaleDiscount()

	/**
	 * Gets an active sale discount if one exists on this item
	 *
	 * @return SaleDiscount
	 */
	public function getActiveSaleDiscount()
	{
		$sale_discount = null;

		if ($this->sale_discount !== null && $this->sale_discount->isActive())
			$sale_discount = $this->sale_discount;

		return $sale_discount;
	}

	// }}}
	// {{{ public function getSaleDiscountNote()

	/**
	 * Gets a note about the active sale discount if one exists
	 *
	 * @return string
	 */
	public function getSaleDiscountNote()
	{
		$note = null;

		$sale = $this->getActiveSaleDiscount();
		if ($sale !== null && $sale->end_date !== null) {
			$now = new SwatDate();
			$span = new Date_Span();
			$span->setFromDateDiff($now, $sale->end_date);

			if ($span->toHours() < 2) {
				if ($span->toHours() < 1)
					$format = '%m minutes';
				else
					$format = '%h hours and %m minutes';

				$note = sprintf('%s sale on this item ends in %s',
					$sale->title, $span->format($format));
			}
		}

		return $note;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('status');
		$this->registerInternalProperty('product',
			SwatDBClassMap::get('StoreProduct'));

		$this->registerInternalProperty('item_group',
			SwatDBClassMap::get('StoreItemGroup'));

		$this->registerInternalProperty('sale_discount',
			SwatDBClassMap::get('StoreSaleDiscount'));

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
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array_merge(parent::getSerializableSubDataObjects(),
			array('item_alias', 'region_bindings', 'quantity_discounts',
				'item_group', 'sale_discount', 'status'));
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(parent::getSerializablePrivateProperties(),
			array('region', 'limit_by_region', 'is_available', 'is_enabled',
				'price'));
	}

	// }}}

	// loader methods
	// {{{ protected function loadQuantityDiscounts()

	protected function loadQuantityDiscounts()
	{
		$wrapper_class = SwatDBClassMap::get('StoreQuantityDiscountWrapper');
		$wrapper = new $wrapper_class();
		$quantity_discounts = $wrapper->loadSetFromDB($this->db,
			array($this->id), $this->region, $this->limit_by_region);

		if ($quantity_discounts !== null)
			foreach ($quantity_discounts as $discount)
				$discount->item = $this;

		return $quantity_discounts;
	}

	// }}}
	// {{{ protected function loadRegionBindings()

	protected function loadRegionBindings()
	{
		$sql = 'select * from ItemRegionBinding where item = %s';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper = SwatDBClassMap::get('StoreItemRegionBindingWrapper');

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function loadItemAlias()

	protected function loadItemAliases()
	{
		$sql = 'select * from ItemAlias where item = %s';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql, 'StoreItemAliasWrapper');
	}

	// }}}

	// saver methods
	// {{{ protected function saveQuantityDiscounts()

	/**
	 * Automatically saves StoreQuantityDiscount sub-data-objects when this
	 * StoreItem object is saved
	 */
	protected function saveQuantityDiscounts()
	{
		foreach ($this->quantity_discounts as $discount)
			$discount->item = $this;

		$this->quantity_discounts->setDatabase($this->db);
		$this->quantity_discounts->save();
	}

	// }}}
	// {{{ protected function saveRegionBindings()

	/**
	 * Automatically saves StoreItemRegionBinding sub-data-objects when this
	 * StoreItem object is saved
	 */
	protected function saveRegionBindings()
	{
		foreach ($this->region_bindings as $binding)
			$binding->item = $this;

		$this->region_bindings->setDatabase($this->db);
		$this->region_bindings->save();
	}

	// }}}
	// {{{ protected function saveItemAliases()

	/**
	 * Automatically saves StoreItemAlias sub-data-objects when this StoreItem
	 * object is saved
	 */
	protected function saveItemAliases()
	{
		foreach ($this->item_aliases as $alias)
			$alias->item = $this;

		$this->item_aliases->setDatabase($this->db);
		$this->item_aliases->save();
	}

	// }}}

	// serialization
	// {{{ public function unserialize()

	public function unserialize($data)
	{
		parent::unserialize($data);

		foreach ($this->quantity_discounts as $discount)
			$discount->item = $this;
	}

	// }}}
}

?>
