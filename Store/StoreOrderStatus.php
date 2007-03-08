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
}

?>
