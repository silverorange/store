<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/StoreClassMap.php';

/**
 * Cell renderer that displays a summary of the status of an item
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemStatusCellRenderer extends SwatCellRenderer
{
	public $status = null;

	public function render()
	{
		$class_map = StoreClassMap::instance();
		$item_class = $class_map->resolveClass('StoreItem');
		$title = call_user_func(array($item_class, 'getStatusTitle'),
			$this->status);

		echo SwatString::minimizeEntities($title);
	}
}

?>
