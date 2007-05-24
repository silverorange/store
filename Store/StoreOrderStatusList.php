<?php

require_once 'Store/Store.php';
require_once 'Store/StoreStatusList.php';
require_once 'Store/StoreOrderStatus.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * A list of {@link StoreOrderStatus} objects
 *
 * Order statuses are progressive. That means if one status is greater than
 * another, the lesser status is included in the greated status. For example,
 * if shipped is greater than billed then an order that is shipped is also
 * billed.
 *
 * The order of order statuses are defined in the
 * {@link StoreOrderStatusList::getDefinedStatuses()} method.
 *
 * This list defines all order statuses and has methods to get a particular
 * status.
 *
 * By default, the following order statuses are defined:
 * - 'initialized'
 * - 'authorized'
 * - 'billed'
 * - 'shipped'
 *
 * If site code needs different or additional statuses it should subclass this
 * class and override the {@link StoreOrderStatusList::getDefinedStatuses()}
 * method.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreOrderStatus
 */
class StoreOrderStatusList extends StoreStatusList
{
	// {{{ private properties

	/**
	 * Static collection of available statuses for this class of status list 
	 *
	 * @var array
	 */
	private static $defined_statuses;

	/**
	 * The order status list instance used for the singleton pattern
	 *
	 * @var StoreOrderStatusList
	 *
	 * @see StoreOrderStatusList::statuses()
	 */
	private static $instance;

	// }}}
	// {{{ public static function status()

	/**
	 * Convenience function to get a status by shortname without having to
	 * create a list instance
	 *
	 * Example usage:
	 *
	 * <code>
	 * $order->status = StoreOrderStatusList::status('complete');
	 * </code>
	 *
	 * @param string $status_shortname the shortname of the status to retrieve.
	 *
	 * @return StoreOrderStatus the order status corresponding to the shortname
	 *                          or null if no such status exists.
	 */
	public static function status($status_shortname)
	{
		return self::statuses()->getByShortname($status_shortname);
	}

	// }}}
	// {{{ public static function statuses()

	/**
	 * Gets the list of defined order statuses
	 *
	 * Example usage:
	 *
	 * <code>
	 * foreach (StoreOrderStatusList::statuses() as $status) {
	 *     echo $status->title, "\n";
	 * }
	 * </code>
	 *
	 * @return StoreOrderStatusList the list of order statuses.
	 */
	public static function statuses()
	{
		if (self::$instance === null) {
			$class_map = SwatDBClassMap::instance();
			$list_class = $class_map->resolveClass('StoreOrderStatusList');
			self::$instance = new $list_class();
		}
		return self::$instance;
	}

	// }}}
	// {{{ protected function getDefinedStatuses()

	/**
	 * Gets an array of defined order statuses for this class of list
	 *
	 * Subclasses are encoraged to override this method to change the default
	 * set of order statuses or to provide additional statuses.
	 *
	 * @return array an array of {@link StoreOrderStatus} objects representing
	 *                all defined order statuses for this class of list.
	 */
	protected function getDefinedStatuses()
	{
		if (self::$defined_statuses === null) {
			self::$defined_statuses = array();

			$class_map = SwatDBClassMap::instance();
			$status_class = $class_map->resolveClass('StoreOrderStatus');

			$initilized_status =
				new $status_class(1, 'initialized', Store::_('Initialized'));

			$authorized_status =
				new $status_class(2, 'authorized', Store::_('Authorized'));

			$billed_status =
				new $status_class(3, 'billed', Store::_('Billed'));

			$shipped_status =
				new $status_class(4, 'shipped', Store::_('Shipped'));

			self::$defined_statuses[] = $initilized_status;
			self::$defined_statuses[] = $authorized_status;
			self::$defined_statuses[] = $billed_status;
			self::$defined_statuses[] = $shipped_status;
		}

		return self::$defined_statuses;
	}

	// }}}
}

?>
