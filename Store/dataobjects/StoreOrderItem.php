<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/*
 * @package   Store
 * @copyright silverorange 2006
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
	 * Product
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
	 * Quickorder?
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
