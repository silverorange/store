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

	// }}}
	// {{{ public function loadExtension()

	public function loadExtension()
	{
		return $this->price * $this->quantity;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('invoice',
			$this->class_map->resolveClass('StoreInvoice'));

		$this->table = 'InvoiceItem';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
