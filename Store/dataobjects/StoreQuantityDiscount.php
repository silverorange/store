<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreQuantityDiscountRegionBindingWrapper.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * Quantity discount object
 *
 * Quantity discounts are a discount scheme whereby the unit price of an item
 * changes when more items are purchased at the same time. For example:
 *
 * - 1  item  at $5.00 each
 * - 5  items at $4.00 each
 * - 10 items at $3.00 each
 *
 * @package   Store 
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityDiscount extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier of this quantity discount 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Identifier of the item this discount applies to
	 *
	 * @var integer
	 */
	public $item;

	/**
	 * Quantity required for this discount to apply
	 *
	 * @var integer
	 */
	public $quantity;

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
	 * Cache of price of item to use at this quantity indexed by region id
	 *
	 * This is an array of floats.
	 *
	 * @var array
	 */
	protected $price = array();

	// }}}
	// {{{ public function setRegion()

	/**
	 * Sets the region to use when loading region-specific fields for this
	 * quantity discount
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to not load this quantity
	 *                           discount if it is not available in the given
	 *                           region.
	 */
	public function setRegion(StoreRegion $region, $limiting = true)
	{
		$this->region = $region;
		$this->limit_by_region = $limiting;
	}

	// }}}
	// {{{ public function getPrice()

	/**
	 * Gets the price of this quantity discount in a region
	 *
	 * @param StoreRegion $region optional. Region for which to get price. If
	 *                             no region is specified, the region set using
	 *                             {@link StoreQuantityDiscount::setRegion()}
	 *                             is used.
	 *
	 * @return double the price of this quantity discount in the given region
	 *                 or null if this quantity discount has no price in the
	 *                 given region.
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
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'QuantityDiscount';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function initFromRow()

	protected function initFromRow($row)
	{
		parent::initFromRow($row);

		if (is_object($row))
			$row = get_object_vars($row);

		if (isset($row['region_id'])) {
			if (isset($row['price']))
				$this->price[$row['region_id']] = $row['price'];
		}
	}

	// }}}
	// {{{ protected function loadRegionBindings()

	protected function loadRegionBindings()
	{
		$sql = 'select * from QuantityDiscountRegionBinding
			where quantity_discount = %s';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper = SwatDBClassMap::get(
			'StoreQuantityDiscountRegionBindingWrapper');

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
}

?>
