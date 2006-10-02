<?php

require_once 'Admin/AdminSummaryDependency.php';

/**
 * A dependency entry for addresses 
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class AddressDependency extends AdminSummaryDependency
{
	protected function getDependencyText($count)
	{
		$message = ngettext('%d dependent %s address',
			'%s dependent %s addresses', $count);

		$message = sprintf($message,
			SwatString::numberFormat($count),
			$this->title);

		return $message;
	}
}

?>
