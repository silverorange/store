<?php

require_once 'Admin/AdminListDependency.php';

/**
 * A dependency for deleting member products of a collection
 *
 * @package   Store
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductCollectionDependency extends AdminListDependency
{
	public $product_title = '';

	protected function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case AdminDependency::DELETE:
			$message = Store::ngettext(
				'Remove the following collection member product from %s?',
				'Remove the following collection member products from %s?',
				$count);

			$message = sprintf($message, $this->product_title);
			break;

		default:
			$message = parent::getStatusLevelText($status_level, $count);
		}

		return $message;
	}
}

?>
