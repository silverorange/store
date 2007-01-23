<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreQuantityDiscountRegionBindingWrapper.php';

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

	/**
	 * Price of item to use at this quantity
	 *
	 * @var integer
	 */
	public $price;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'QuantityDiscount';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadRegionBindings()

	protected function loadRegionBindings()
	{
		$sql = 'select * from QuantityDiscountRegionBinding
			where quantity_discount = %s';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		$wrapper = $this->class_map->resolveClass(
			'StoreQuantityDiscountRegionBindingWrapper');

		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
}

?>
