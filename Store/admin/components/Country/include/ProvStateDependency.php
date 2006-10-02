<?php

require_once 'Admin/AdminListDependency.php';

/**
 * A dependency entry for provinces and states
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class ProvStateDependency extends AdminListDependency
{
	protected function getDependencyText($count)
	{
		$message = ngettext('Dependent province or state:',
			'Dependent provinces or states:', $count);

		return $message;
	}
}

?>
