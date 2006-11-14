<?php

require_once 'Admin/AdminListDependency.php';

/**
 * A dependency for categories
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryDependency extends AdminListDependency
{
	public function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case AdminDependency::DELETE:
			$message = Store::ngettext('Delete the following category?',
				'Delete the following categories?', $count);

			break;

		case AdminDependency::NODELETE:
			$message = Store::ngettext(
				'The following category can not be deleted:',
				'The following categories can not be deleted:', $count);

			break;

		default:
			$message = parent::getStatusLevelTitle($count, $status_level);
		}

		return $message;
	}

	protected function getDependencyText($count)
	{
		$message = Store::ngettext('Dependent sub-category:',
			'Dependent sub-categories:', $count);

		return $message;
	}
}

?>
