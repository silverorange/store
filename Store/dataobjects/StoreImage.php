<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An image data object
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
abstract class StoreImage extends SwatDBDataObject
{
	// {{{ constants

	const COMPRESSION_QUALITY = 85;
	const DPI = 72;

	// }}}
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * Whether to display with a border
	 *
	 * not null default true,
	 *
	 * @var integer
	 */
	public $border = true;

	/**
	 * Title 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $thumb_width;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $thumb_height;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $small_width;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $small_height;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $large_width;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $large_height;

	// }}}
	// {{{ abstract public function getURI()

	abstract public function getURI($set = 'large');

	// }}}
	// {{{ public function hasOriginal()

	public function hasOriginal()
	{
		$uri = $this->getURI('original');
		return (file_exists($uri));
	}

	// }}}
	// {{{ public function getImgTag()

	public function getImgTag($set)
	{
		$img_tag = new SwatHtmlTag('img');

		if ($this->title !== null) {
			$img_tag->alt = 'Photo of '.$this->title;
			$img_tag->title = $this->title;
		} else {
			$img_tag->alt = '';
		}

		$width = $set.'_width';
		$height = $set.'_height';

		$img_tag->src = $this->getURI($set);
		$img_tag->width = $this->$width;
		$img_tag->height = $this->$height;
		$img_tag->class = $this->border ?
			'store-border-on' : 'store-border-off';

		return $img_tag;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Image';
		$this->id_field = 'integer:id';
	}

	// }}}

	// static processing methods
	// {{{ public static function processThumbnail()

	/**
	 * Does special resizing and cropping for thumbnail images
	 *
	 * Thumbnail images are always a fixed size. This resizes and crops the
	 * image so that the largest portion from the center image used.
	 *
	 * @param Imagick $imagick the imagick instance to work with. The instance
	 *                          should already have an image loaded.
	 *
	 * @param integer $width the width of a thumbnail image.
	 * @param integer $height the height of a thumbnail image, if null
	 *                         thumbnail is square.
	 */
	public static function processThumbnail(
		Imagick $imagick, $width, $height = null)
	{
		if ($height === null)
			$height = $width;

		if ($imagick->getImageWidth() / $width > $imagick->getImageHeight() / $height) {
			$new_y = $height;
			$new_x = ceil(($new_y / $imagick->getImageHeight()) * $imagick->getImageWidth());
		} else {
			$new_x = $width;
			$new_y = ceil(($new_x / $imagick->getImageWidth()) * $imagick->getImageHeight());
		}

		$imagick->resizeImage($new_x, $new_y, Imagick::FILTER_LANCZOS, 1);

		// crop to fit
		if ($imagick->getImageWidth() != $width || $imagick->getImageHeight() != $height) {
			$offset_x = 0;
			$offset_y = 0;

			if ($imagick->getImageWidth() > $width)
				$offset_x = ceil(($imagick->getImageWidth() - $width) / 2);

			if ($imagick->getImageHeight() > $height)
				$offset_y = ceil(($imagick->getImageHeight() - $height) / 2);

			$imagick->cropImage($width, $height, $offset_x, $offset_y);
		}

		$imagick->setResolution(self::DPI, self::DPI);
		$imagick->stripImage();
	}

	// }}}
	// {{{ public static function processImage()

	/**
	 * Does resizing for images
	 *
	 * The image is resized to fit within a maximum width.
	 *
	 * @param Imagick $imagick the imagick instance to work with. The instance
	 *                          should already have an image loaded.
	 *
	 * @param integer $max_width the max width for the image.
	 * @param integer $max_height the max height for the image.
	 */
	public static function processImage(Imagick $imagick,
		$max_width = null, $max_height = null)
	{
		if ($max_width !== null)
			self::fitX($imagick, $max_width);

		if ($max_height !== null)
			self::fitY($imagick, $max_height);

		$imagick->setResolution(self::DPI, self::DPI);
		$imagick->stripImage();
	}

	// }}}
	// {{{ protected static function fitX()

	protected static function fitX(Imagick $imagick, $max_width)
	{
		if ($imagick->getImageWidth() > $max_width) {

			$new_height = ceil($imagick->getImageHeight() *
				($max_width / $imagick->getImageWidth()));

			$imagick->resizeImage($max_width, $new_height,
				Imagick::FILTER_LANCZOS, 1);
		}
	}

	// }}}
	// {{{ protected static function fitY()

	protected static function fitY(Imagick $imagick, $max_height)
	{
		if ($imagick->getImageHeight() > $max_height) {

			$new_width = ceil($imagick->getImageWidth() *
				($max_height / $imagick->getImageHeight()));

			$imagick->resizeImage($new_width, $max_height,
				Imagick::FILTER_LANCZOS, 1);
		}
	}

	// }}}
	// {{{ public static function processManualImage()

	/**
	 * Processing for manually resized images
	 *
	 * @param Imagick $imagick the imagick instance to work with. The instance
	 *                          should already have an image loaded.
	 *
	 * @param string $size the size tag.
	 */
	public static function processManualImage(Imagick $imagick, $size)
	{
		$imagick->setResolution(self::DPI, self::DPI);
		$imagick->stripImage();
	}

	// }}}
	// {{{ abstract public static function getSizes()

	/**
	 * Gets an array of size identifiers for an image
	 *
	 * The keys are small text descriptions of the valid sizes and the values
	 * are two-element arrays storing the dimensions.  The keys are commonly
	 * used to separate images and thumbnails into separate directories. For
	 * example, the returned array might be:
	 *
	 * <code>
	 * array(
	 *     'thumb' => array(100, 150),
	 *     'small' => array(200, null),
	 *     'large' => array(400, null),
	 * );
	 * </code>
	 *
	 * @return array textual identifiers of the valid sizes for images.
	 */
	abstract public static function &getSizes();

	// }}}
}

?>
