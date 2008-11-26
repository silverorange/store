<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 * Product review for a product
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 */
class StoreProductReview extends SiteComment
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('product', 'Product');

		$this->table = 'ProductReview';
	}

	// }}}
}

?>
