<?php

require_once 'Store/dataobjects/StoreDataObject.php';

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
class StoreQuantityDiscount extends SwatDBDataObject
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
}

?>
