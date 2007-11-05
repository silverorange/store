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
			$link = $this->path.'/'.$product->shortname;
			$product->displayAsIcon($link);
			echo '</li>';
		}

		$ul_tag->close();
	}

	// }}}
}

?>
