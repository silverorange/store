<?php

require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
//TODO require_once 'Store/StoreInvoiceAnnouncementMailMessage.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreInvoiceItemWrapper.php';

/**
 * An Invoice
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoice extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Comments
	 *
	 * @var string
	 */
	public $comments;

	/**
	 * Creation date
	 *
	 * @var date
	 */
	public $createdate;

	/**
	 * Total amount
	 *
	 * @var float
	 */
	public $total;

	/**
	 * Item total
	 *
	 * @var float
	 */
	public $item_total;

	/**
	 * Shipping total
	 *
	 * @var float
	 */
	public $shipping_total;

	/**
	 * Tax total
	 *
	 * @var float
	 */
	public $tax_total;

	// }}}
	// {{{ public function getInvoiceDetailsTableStore()

	public function getInvoiceDetailsTableStore()
	{
		$store = new SwatTableStore();

		foreach ($this->items as $item) {
			$ds = $this->getInvoiceItemDetailsStore($item);
			$store->addRow($ds);
		}

		return $store;
	}

	// }}}
	// {{{ public function sendConfirmationEmail()

	public function sendAnnouncementEmail(SiteApplication $app)
	{
		// This is demo code. StoreInvoiveAnnouncmentMailMessage is
		// abstract and the site-specific version must be used.

		if ($this->email === null)
			return;

		try {
			$email = new StoreInvoiceAnnouncementMessage($app, $this);
			$email->send();
		} catch (SiteMailException $e) {
			$e->process(false);
		}
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		return sprintf('Invoice %s', $this->id);
	}

	// }}}
	// {{{ public function getDescription()

	/**
	 * Gets a short, textual description of this invoice
	 *
	 * For example: "Example Company Invoice #12345".
	 *
	 * @return string a short, textual description of this invoice.
	 */
	public function getDescription()
	{
		return sprintf('Invoice #%s', $this->id);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('account',
			$this->class_map->resolveClass('StoreAccount'));

		$this->registerInternalProperty('locale',
			$this->class_map->resolveClass('StoreLocale'));

		$this->registerDateProperty('createdate');

		$this->table = 'Invoice';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array(
			'items',
		);
	}

	// }}}
	// {{{ protected function getInvoiceItemDetailsStore()

	public function getInvoiceItemDetailsStore($item)
	{
		$ds = new SwatDetailsStore($item);
		$ds->item = $item;
		$ds->extension = $item->getExtension();

		return $ds;
	}

	// }}}

	// price calculation methods
	// {{{ public function getTotal()

	/**
	 * Gets the total cost for this invoice
	 *
	 * By default, the total is calculated as item total + tax + shipping.
	 * Subclasses may override this to calculate totals differently.
	 *
	 * @param StoreAddress $billing_address the billing address of the order.
	 * @param StoreAddress $shipping_address the shipping address of the order.
	 *
	 * @return double the cost of this invoice.
	 */
	public function getTotal(StoreAddress $billing_address = null,
		StoreAddress $shipping_address = null)
	{
		if ($this->total === null) {
			$total = 0;
			$total += $this->getItemTotal();

			$total += $this->getTaxTotal(
				$billing_address, $shipping_address);

			$total += $this->getShippingTotal(
				$billing_address, $shipping_address);
		} else {
			$total = $this->total;
		}

		return $total;
	}

	// }}}
	// {{{ public function getSubtotal()

	public function getSubtotal()
	{
		$total = 0;
		$total += $this->getItemTotal();

		return $total;
	}

	// }}}
	// {{{ public function getItemTotal()

	/**
	 * Gets the cost of the invoice items on this invoice
	 *
	 * @return double the sum of the extensions of all InvoiceItem objects.
	 */
	public function getItemTotal()
	{
		if ($this->item_total === null) {
			$total = 0;
			foreach ($this->items as $item)
				$total += $item->getExtension();

		} else {
			$total = $this->item_total;
		}

		return $total;
	}

	// }}}
	// {{{ public function getShippingTotal()

	/**
	 * Gets the cost of shipping this invoice
	 *
	 * @param StoreAddress $billing_address the billing address.
	 * @param StoreAddress $shipping_address the shipping address.
	 *
	 * @return double the cost of shipping this invoice.
	 */
	public function getShippingTotal(StoreAddress $billing_address = null,
		StoreAddress $shipping_address = null)
	{
		if ($this->shipping_total === null) {
			$total = null;
		} else {
			$total = $this->shipping_total;
		}

		return $total;
	}

	// }}}
	// {{{ public function getTaxTotal()

	/**
	 * Gets the total amount of taxes for this invoice
	 *
	 * Calculates applicable taxes based on the items in this invoice. Tax
	 * calculations need to know where purchase is made in order to correctly
	 * apply tax.
	 *
	 * @param StoreAddress $billing_address the billing address.
	 * @param StoreAddress $shipping_address the shipping address.
	 *
	 * @return double the tax charged for the items of this invoice.
	 */
	public function getTaxTotal(StoreAddress $billing_address = null,
		StoreAddress $shipping_address = null)
	{
		if ($this->tax_total === null) {
			$total = null;
		} else {
			$total = $this->tax_total;
		}

		return $total;
	}
	
	// }}}

	// loader methods
	// {{{ protected function loadItems()

	protected function loadItems()
	{
		$sql = sprintf('select * from InvoiceItem
			where invoice = %s
			order by id asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			$this->class_map->resolveClass('StoreInvoiceItemWrapper'));
	}

	// }}}

	// saver methods
	// {{{ protected function saveItems()

	/**
	 * Automatically saves StoreInvoiceItem sub-data-objects when this
	 * StoreInvoice object is saved
	 */
	protected function saveItems()
	{
		foreach ($this->items as $item)
			$item->invoice = $this;

		$this->items->setDatabase($this->db);
		$this->items->save();
	}

	// }}}
}

?>
