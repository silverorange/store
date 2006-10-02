<?php

require_once 'Admin/AdminListDependency.php';

/**
 * A dependency entry for countries
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class CountryDependency extends AdminListDependency
{
	public function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case AdminDependency::DELETE:
			$message = ngettext('Delete the following country?',
			'Delete the following countries?', $count);
			break;

		case AdminDependency::NODELETE:
			$message = ngettext('The following country can not be deleted:',
			'The following countries can not be deleted:', $count);
			break;

		default:
			$message = parent::getStatusLevelTitle($count, $status_level);
		}

		return $message;
	}
}

?>
