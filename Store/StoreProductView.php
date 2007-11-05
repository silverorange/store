<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';

/**
 * Control to display a recordset of products
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreProductView extends SwatControl
{
	// {{{ public properties

	/**
	 * The category path of products displayed in this product view
	 *
	 * This category path is prepended to the shortname of every product
	 * displayed by this view and then used in links to products. If no path
	 * is specified or the path is specified as null, the product path is
	 * loaded for each product object prior to displaying the product.
	 *
	 * @var string
	 */
	public $path;

	// }}}
	// {{{ protected properties

	/**
	 * The products displayed by this product view
	 *
	 * @var StoreProductWrapper
	 */
	protected $products;

	// }}}
	// {{{ public function setProducts()

	/**
	 * Sets the products of this product view
	 *
	 * @param StoreProductWrapper $products the products of this product view.
	 */
	public function setProducts(StoreProductWrapper $products)
	{
		$this->products = $products;
	}

	// }}}
}

?>
