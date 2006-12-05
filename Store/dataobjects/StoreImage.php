<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Image/Transform.php';

/**
 * An image data object
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
abstract class StoreImage extends StoreDataObject
{
	// {{{ constants

	const COMPRESSION_QUALITY = 85;

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
	 * @param Image_Transform $image the image transformer to work with. The
	 *                                tranformer should already have an image
	 *                                loaded.
	 *
	 * @param integer $width the width of a thumbnail image.
	 * @param integer $height the height of a thumbnail image, if null
	 *                         thumbnail is square.
	 *
	 * @throws SwatException if no image is loaded in the transformer.
	 */
	public static function processThumbnail(
		Image_Transform $image, $width, $height = null)
	{
		if ($image->image === null)
			throw new SwatException('No image loaded.');

		if ($height === null)
			$height = $width;

		if ($image->img_x / $width > $image->img_y / $height) {
			$new_y = $height;
			$new_x = ceil(($new_y / $image->img_y) * $image->img_x);
		} else {
			$new_x = $width;
			$new_y = ceil(($new_x / $image->img_x) * $image->img_y);
		}

		$image->resize($new_x, $new_y);

		// crop to fit
		if ($image->new_x != $width || $image->new_y != $height) {
			$offset_x = 0;
			$offset_y = 0;

			if ($image->new_x > $width)
				$offset_x = ceil(($image->new_x - $width) / 2);

			if ($image->new_y > $height)
				$offset_y = ceil(($image->new_y - $height) / 2);

			$image->crop($width, $height, $offset_x, $offset_y);
		}
	}

	// }}}
	// {{{ public static function processImage()

	/**
	 * Does resizing for images
	 *
	 * The image is resized to fit within a maximum width.
	 *
	 * @param Image_Transform $image the image transformer to work with. The
	 *                                tranformer should already have an image
	 *                                loaded.
	 *
	 * @param integer $max_width the max width for the image.
	 * @param integer $max_height the max height for the image.
	 *
	 * @throws SwatException if no image is loaded in the transformer.
	 */
	public static function processImage(Image_Transform $image,
		$max_width = null, $max_height = null)
	{
		if ($image->image === null)
			throw new SwatException('No image loaded.');

		if ($max_width !== null)
			$image->fitX($max_width);

		if ($max_height !== null)
			$image->fitY($max_height);
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
