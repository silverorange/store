<?php

/**
 * A possible item status.
 *
 * Item statuses are all defined in
 * {@link StoreItemStatusList::getDefinedStatuses()} or in a subclass of
 * StoreItemStatusList.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreItem::getStatus()
 * @see       StoreItem::setStatus()
 * @see       StoreStatusList
 */
class StoreItemStatus extends StoreStatus {}
