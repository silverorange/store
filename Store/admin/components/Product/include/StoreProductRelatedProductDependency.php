<?php

require_once 'Admin/AdminListDependency.php';

/**
 * A dependency for deleting related products
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductRelatedProductDependency extends AdminListDependency
{
	public $product_title = '';

	protected function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case AdminDependency::DELETE:
			$message = Store::ngettext(
				'Remove the following related product from %s?',
				'Remove the following related products from %s?', $count);

			$message = sprintf($message, $this->product_title);
			break;

		default:
			$message = parent::getStatusLevelText($status_level, $count);
		}

		return $message;
	}
}

?>
