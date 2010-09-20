<?php

require_once 'Store/dataobjects/StoreImage.php';

/**
 * An image data object for products
 *
 * @package Store
 * @copyright silverorange 2006-2010
 */
class StoreProductImage extends StoreImage
{
	// {{{ public function getUri()

    public function getUri($shortname = 'large', $prefix = null)
	{
		return parent::getUri($shortname, $prefix);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->image_set_shortname = 'products';
	}

	// }}}
}

?>
