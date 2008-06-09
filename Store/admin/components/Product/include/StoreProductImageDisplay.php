<?php

require_once 'Swat/SwatImageDisplay.php';
require_once 'Swat/SwatToolbar.php';

/**
 * A special image display with tools for product images
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
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
	// {{{ private properties

	/**
	 * @var SwatToolbar
	 */
	private $toolbar;

	/**
	 * @var boolean
	 */
	private $widgets_created = false;

	// }}}

	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->createEmbeddedWidgets();
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this image
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		$this->createEmbeddedWidgets();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'store-product-image-display';
		$div_tag->open();

		parent::display();

		if ($this->category_id === null) {
			$get_vars = sprintf('product=%s', $this->product_id);
		} else {
			$get_vars = sprintf('product=%s&category=%s',
				$this->product_id, $this->category_id);
		}

		$this->toolbar->setToolLinkValues(
			sprintf('%s&%s', $this->image_id, $get_vars));

		$this->toolbar->display();

		$div_tag->close();
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();
		$set->addEntrySet($this->toolbar->getHtmlHeadEntrySet());

		return $set;
	}

	// }}}
	// {{{ public function getToolbar()

	public function getToolbar()
	{
		return $this->toolbar;
	}

	// }}}
	// {{{ private function createEmbeddedWidgets()

	private function createEmbeddedWidgets()
	{
		if (!$this->widgets_created) {
			$this->toolbar = new SwatToolbar();

			$edit = new SwatToolLink();
			$edit->link = 'Product/ImageEdit?id=%s';
			$edit->setFromStock('edit');
			$edit->title = Store::_('Edit');
			$this->toolbar->addChild($edit);
	
			$delete = new SwatToolLink();
			$delete->link = 'Product/ImageDelete?id=%s';
			$delete->setFromStock('delete');
			$delete->title = Store::_('Remove');
			$this->toolbar->addChild($delete);

			$this->widgets_created = true;
		}
	}

	// }}}
}

?>
