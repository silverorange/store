<?php

require_once 'Admin/AdminSummaryDependency.php';

/**
 * A dependency entry for addresses
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddressDependency extends AdminSummaryDependency
{
	protected function getDependencyText($count)
	{
		$message = Store::ngettext('%d dependent %s address',
			'%s dependent %s addresses', $count);

		$message = sprintf($message,
			SwatString::numberFormat($count),
			$this->title);

		return $message;
	}
}

?>
