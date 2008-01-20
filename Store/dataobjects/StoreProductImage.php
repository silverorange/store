<?php

require_once 'Store/dataobjects/StoreImage.php';

/**
 * An image data object for products
 *
 * @package Store
 * @copyright silverorange 2006-2008
 */
class StoreProductImage extends StoreImage
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->image_set_shortname = 'products';
	}

	// }}}
}

?>
