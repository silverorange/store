<?php

require_once 'Swat/SwatImageDisplay.php';

/**
 * A special image display with tools for product images
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageDisplay extends SwatImageDisplay
{
	// {{{ public properties

	/**
	 * Image Id
	 *
	 * The image id for the product image.
	 *
	 * @var integer
	 */
	public $image_id;

	/**
	 * Product Id
	 *
	 * The product id for the product image.
	 *
	 * @var integer
	 */
	public $product_id;

	/**
	 * Category Id
	 *
	 * The category id for the product.
	 *
	 * @var integer
	 */
	public $category_id;

	// }}}
	// {{{ public function display()

	/**
	 * Displays this image
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'store-product-image-display';
		$div_tag->open();

		parent::display();

		$toolbar = new SwatToolBar();

		if ($this->category_id === null)
			$get_vars = sprintf('product=%s',
				$this->product_id);
		else
			$get_vars = sprintf('product=%s&category=%s',
				$this->product_id, $this->category_id);

		$edit = new SwatToolLink();
		$edit->link = sprintf('Product/ImageEdit?id=%s&%s',
			$this->image_id,
			$get_vars);

		$edit->setFromStock('edit');
		$edit->title = Store::_('Edit');
		$toolbar->addChild($edit);

		$delete = new SwatToolLink();
		$delete->link = sprintf('Product/ImageDelete?id=%s&%s',
			$this->image_id,
			$get_vars);

		$delete->setFromStock('delete');
		$delete->title = Store::_('Remove');
		$toolbar->addChild($delete);

		$toolbar->display();

		//$set = parent::getHtmlHeadEntrySet();
		//$set->addEntrySet($toolbar->getHtmlHeadEntrySet());

		$div_tag->close();
	}

	// }}}
}

?>
