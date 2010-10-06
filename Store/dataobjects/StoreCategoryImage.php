<?php

require_once 'Store/dataobjects/StoreImage.php';

/**
 * An image data object for categories
 *
 * @package   Store
 * @copyright 2006-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryImage extends StoreImage
{
	// {{{ public function getUri()

	public function getUri($shortname = 'thumb', $prefix = null)
	{
		return parent::getUri($shortname, $prefix);
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
