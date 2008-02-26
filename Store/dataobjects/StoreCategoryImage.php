<?php

require_once 'Store/dataobjects/StoreImage.php';

/**
 * An image data object for categories
 *
 * @package Store
 * @copyright silverorange 2006-2008
 */
class StoreCategoryImage extends StoreImage
{
	// {{{ public function getURI()

	public function getURI($set = 'thumb')
	{
		return 'images/categories/'.$set.'/'.$this->id.'.jpg';
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->image_set_shortname = 'categories';
	}

	// }}}
}

?>
