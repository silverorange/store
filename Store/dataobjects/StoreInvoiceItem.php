<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * An item in an invoice
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceItem extends StoreDataObject
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
	 * @var float
	 */
	public $price;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	// }}}
	// {{{ public function getDetailedDescription()

	/**
	 * Gets a detailed description of this invoice item
	 *
	 * In store, this is formatted as:
	 *
	 * SKU: Description
	 *
	 * @return string a detailed description of this invoice item.
	 */
	public function getDetailedDescription()
	{
		$description = '';

		if ($this->sku !== null)
			$description.= $this->sku;

		if ($this->sku !== null && $this->description !== null)
			$description.= ': ';

		if ($this->description !== null)
			$description.= $this->description;

		return $description;
	} 

	// }}}
	// {{{ public function getExtension()

	/**
	 * Gets the extension price of this invoice item
	 *
	 * The cost is calculated as this invoice item's price multiplied
	 * by the quantity. This value is called the extension.
	 *
	 * @return double the extension price of this invoice item.
	 */
	public function getExtension()
	{
		return $this->price * $this->quantity;
	}

	// }}}
	// {{{ public function createOrderItem()

	/**
	 * Creates a new order item dataobject that corresponds to this invoice item
	 *
	 * @return StoreOrderItem a new StoreOrderItem object that corresponds to
	 *                         this invoice item.
	 */
	public function createOrderItem()
	{
		$class = SwatDBClassMap::get('StoreOrderItem');
		$order_item = new $class();

		$order_item->sku = $this->sku;
		$order_item->price = $this->price;
		$order_item->quantity = $this->quantity;
		$order_item->extension = $this->getExtension();
		$order_item->description = $this->description;
		$order_item->product = null;
		$order_item->product_title = null;
		$order_item->quick_order = false;

		// set database if it exists
		if ($this->db !== null)
			$order_item->setDatabase($this->db);

		return $order_item;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('invoice',
			SwatDBClassMap::get('StoreInvoice'));

		$this->table = 'InvoiceItem';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
