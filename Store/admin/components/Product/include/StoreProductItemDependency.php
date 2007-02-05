<?php

require_once 'Swat/SwatString.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * A dependency for items
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductItemDependency extends AdminSummaryDependency
{
	protected function getDependencyText($count)
	{
		$message = Store::ngettext('contains one item',
			'contains %s items', $count);

		$message = sprintf($message, SwatString::numberFormat($count));

		return $message;
	}
}

?>
