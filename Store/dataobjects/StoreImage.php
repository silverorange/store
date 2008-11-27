<?php

require_once 'Site/dataobjects/SiteImage.php';

/**
 * An image data object
 *
 * @package   Store
 * @copyright 2005-2008 silverorange
 */
class StoreImage extends SiteImage
{
	// {{{ public properties

	/**
	 * Whether to display with a border
	 *
	 * @var integer
	 */
	public $border;

	// }}}
	// {{{ public function hasOriginal()

	/**
	 * Whether dimension exists for this image
	 *
 	 * @deprecated Use {@link SiteImage::hasDimension()} instead.
	 */
	public function hasOriginal()
	{
		return false;
	}

	// }}}
	// {{{ public function getImgTag()

	public function getImgTag($shortname, $prefix = null)
	{
		$img_tag = parent::getImgTag($shortname, $prefix);

		$img_tag->class = $this->border ?
			'store-border-on' : 'store-border-off';

		return $img_tag;
	}

	// }}}
}

?>
