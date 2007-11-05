<?php

require_once 'Store/StoreProductView.php';

/**
 * Product view that displays a recordset of products as icons
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductIconView extends StoreProductView
{
	// {{{ public properties

	/**
	 * The image size shortname of product icon images for this product icon
	 * view
	 *
	 * Image size shortnames are site-specific. Most sites define a 'thumb'
	 * size.
	 *
	 * @var string
	 */
	public $image_size = 'thumb';

	// }}}
	// {{{ public function display()

	/**
	 * Displays this product icon view
	 *
	 * Each product is displayed as an icon.
	 */
	public function display()
	{
		parent::display();

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->id = $this->id;
		$ul_tag->class = 'store-product-list';
		$ul_tag->open();

		foreach ($this->products as $product) {
			echo '<li class="store-product-icon">';
			if ($this->path === null)
				$link = 'store/'.$product->path;
			else
				$link = $this->path.'/'.$product->shortname;

			$product->displayAsIcon($link, $this->image_size);
			echo '</li>';
		}

		$ul_tag->close();
	}

	// }}}
}

?>
