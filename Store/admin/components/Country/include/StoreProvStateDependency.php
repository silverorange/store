<?php

require_once 'Admin/AdminListDependency.php';

/**
 * A dependency entry for provinces and states
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreProvStateDependency extends AdminListDependency
{
	protected function getDependencyText($count)
	{
		$message = Store::ngettext('Dependent province or state:',
			'Dependent provinces or states:', $count);

		return $message;
	}
}

?>
