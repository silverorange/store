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

	public function getURI($set = 'thumb', $prefix = null)
	{
		$uri = 'images/categories/'.$set.'/'.$this->id.'.jpg';

		if ($prefix != null)
			$uri = $prefix.$uri;

		return $uri;
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
