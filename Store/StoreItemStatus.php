<?php

require_once 'Store/StoreStatus.php';

/**
 * A possible item status
 *
 * Item statuses are all defined in
 * {@link StoreItemStatusList::getDefinedStatuses()} or in a subclass of
 * StoreItemStatusList.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreItem::getStatus()
 * @see       StoreItem::setStatus()
 * @see       StoreStatusList
 */
class StoreItemStatus extends StoreStatus
{
}

?>
