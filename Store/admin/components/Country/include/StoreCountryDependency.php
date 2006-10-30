<?php

require_once 'Admin/AdminListDependency.php';

/**
 * A dependency entry for countries
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCountryDependency extends AdminListDependency
{
	public function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case AdminDependency::DELETE:
			$message = Store::ngettext('Delete the following country?',
			'Delete the following countries?', $count);
			break;

		case AdminDependency::NODELETE:
			$message = Store::ngettext(
			'The following country can not be deleted:',
			'The following countries can not be deleted:', $count);
			break;

		default:
			$message = parent::getStatusLevelTitle($count, $status_level);
		}

		return $message;
	}
}

?>
