<?php

require_once 'Admin/AdminListDependency.php';

/**
 * Custom dependency for account addresses
 *
 * Subclassed to fix pluralization.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountAddressDependency extends AdminListDependency
{
	protected function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case self::DELETE:
			$message = sprintf(Store::_('Delete the following %s?'),
				$this->title);
			break;

		default:
			parent::getStatusLevelText($status_level, $count);
		}
		return $message;
	}
}

?>
