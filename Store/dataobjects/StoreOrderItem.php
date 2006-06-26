<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * An item in an order
 *
 * A single order contains multiple order items. An order item contains all
 * price, product, quantity and discount information from when the order was
 * placed. An order item is a combination of important fields from an item,
 * a cart entry and a product.
 *
 * You can automatically create StoreOrderItem objects from StoreCartEntry
 * objects using the {@link StoreCartEntry::createOrderItem()} method.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCartEntry::createOrderItem()
 */
class StoreOrderItem extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Merchant's stocking keeping unit (SKU)
	 *
	 * @var string
	 */
	public $sku;

	/**
	 * Quantity
	 *
	 * @var integer
	 */
	public $quantity;

	/**
	 * Price
	 *
	 * @var string
	 */
	public $price;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Extension
	 *
	 * @var float
	 */
	public $extension;

	/**
	 * Product identifier
	 *
	 * @var integer
	 */
	public $product;

	/**
	 * Product title
	 *
	 * @var string
	 */
	public $product_title;

	/**
	 * Whether or not this item was ordered through the quick-order tool
	 *
	 * @var boolean
	 */
	public $quick_order;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('ordernum',
			$this->class_map->resolveClass('StoreOrder'));

		$this->table = 'OrderItem';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
