<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/StoreItemStatusList.php';

/**
 * Cell renderer that displays a summary of the status of an item
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemStatusCellRenderer extends SwatCellRenderer
{
	/**
	 * @var StoreItemStatus
	 */
	public $status;

	public function render()
	{
		echo SwatString::minimizeEntities($this->status->title);
	}
}

?>
