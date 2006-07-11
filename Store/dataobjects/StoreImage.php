<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Image/Transform.php';

/**
 * An image data object
 *
 * @package veseys2
 * @copyright silverorange 2005
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
	 * Thumbnail images are always 100 pixels by 100 pixels. This resizes the
	 * image so the smallest dimension is 100 pixels and then crops the image
	 * at 100 pixels by 100 pixels in the center.
	 *
	 * @param Image_Transform $image the image transformer to work with. The
	 *                                tranformer should already have an image
	 *                                loaded.
	 *
	 * @param integer $max_dimension the max dimension for a thumbnail image.
	 *
	 * @throws SwatException if no image is loaded in the transformer.
	 */
	public static function processThumbnail(
		Image_Transform $image, $max_dimension = 100)
	{
		if ($image->image === null)
			throw new SwatException('No image loaded.');

		// resize so smallest side is $max_dimension pixels
		if ($image->img_x >= $image->img_y) {
			$new_y = $max_dimension;
			$new_x = round(($new_y / $image->img_y) * $image->img_x, 0);
		} else {
			$new_x = $max_dimension;
			$new_y = round(($new_x / $image->img_x) * $image->img_y, 0);
		}
		$image->resize($new_x, $new_y);

		// crop if not square
		if ($image->new_x != $image->new_y) {
			if ($image->new_x > $image->new_y) {
				$crop_x = round(($image->new_x - $max_dimension) / 2, 0);
				$crop_y = 0;
			} elseif ($image->new_x < $image->new_y) {
				$crop_y = round(($image->new_y - $max_dimension) / 2, 0);
				$crop_x = 0;
			}
			$image->crop($max_dimension, $max_dimension,
				$crop_x, $crop_y);
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
	 *
	 * @throws SwatException if no image is loaded in the transformer.
	 */
	public static function processImage(Image_Transform $image, $max_width)
	{
		if ($image->image === null)
			throw new SwatException('No image loaded.');

		$image->fitX($max_width);
	}

	// }}}
	// {{{ abstract public static function getSizes()

	/**
	 * Gets an array of size identifiers for an image
	 *
	 * These are small text descriptions of the valid sizes. They are commonly
	 * used to separate images and thumbnails into separate directories. For
	 * example, the returned array might be:
	 *
	 * <code>
	 * array('thumb' => 100, 'small' => 200, 'large' => 400);
	 * </code>
	 *
	 * @return array textual identifiers of the valid sizes for images.
	 */
	abstract public static function &getSizes();

	// }}}
}

?>
