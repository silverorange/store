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
	 * not null default true,
	 *
	 * @var integer
	 */
	public $border = true;

	// }}}
	// {{{ public function hasOriginal()

	public function hasOriginal()
	{
		return false;

		/*
		TODO: handle this without stating files
		$filename = $this->getFilePath('original');
		return (file_exists($filename));
		*/
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
