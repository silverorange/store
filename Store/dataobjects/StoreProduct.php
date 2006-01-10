<?php

/**
 * A product for an e-commerce web application
 *
 * Products are in the middle of the product structure. Each product can have
 * multiple items and can belong to multiple categories. Procucts are usually
 * displayed on product pages. A product is different from an item in that
 * the product contains a very general idea of what is available and an item
 * describes an exact item that a customer can purchase.
 *
 * <pre>
 * Category
 * |
 * -- Product
 *    |
 *    -- Item
 * </pre>
 *
 * Ideally, products are displayed one to a page but it is possible to display
 * many products on one page.
 *
 * The load one product, use something like the following:
 *
 * <code>
 * $sql = '-- select a product here';
 * $product = $db->query($sql, null, true, 'StoreProduct');
 * </code>
 *
 * If there are many StoreProduct objects that must be loaded for a page
 * request, the MDB2 wrapper class called StoreProductWrapper should be used to
 * load the objects.
 *
 * To load many products, use something like the following:
 *
 * <code>
 * $sql = '-- select many products here';
 * $products = $db->query($sql, null, true, 'StoreProductWrapper');
 * foreach ($products as $product) {
 *     // do something with each product here ...
 * }
 * </code>
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreProductWrapper
 */
class StoreProduct extends SwatDBDataObject
{
	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;
}

?>
