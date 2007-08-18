<?php

require_once 'Store/StoreStatus.php';

/**
 * A possible order status
 *
 * Order statuses are all defined in
 * {@link StoreOrderStatusList::getDefinedStatuses()} or in a subclass of
 * StoreOrderStatusList.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreOrder::getStatus()
 * @see       StoreOrder::setStatus()
 * @see       StoreStatusList
 */
class StoreOrderStatus extends StoreStatus
{
	// {{{ public static function compare()

	/**
	 * Compares two order statuses
	 *
	 * @param StoreOrderStatus $x
	 * @param StoreOrderStatus $y
	 *
	 * @return integer a -1 if <i>$x</i> is less than <i>$y</i>, a 1 if
	 *                  <i>$x</i> is greater than <i>$y</i>, and 0 if <i>$x</i>
	 *                  is equal to <i>$y</i>.
	 */
	public static function compare(StoreOrderStatus $x, StoreOrderStatus $y)
	{
		$value = 0;

		if ($x->id > $y->id)
			$value = 1;
		elseif ($x->id < $y->id)
			$value = -1;

		return $value;
	}

	// }}}
}

?>
