<?php

require_once 'Admin/AdminListDependency.php';

/**
 * Custom dependency for accountpayment methods 
 *
 * Subclassed to fix pluralization.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountPaymentMethodDependency extends AdminListDependency
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
