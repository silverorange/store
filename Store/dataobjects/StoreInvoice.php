<?php

require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/StoreInvoiceAnnouncementMailMessage.php';
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
	// {{{ public function getSubtotal()

	/**
	 * Gets the subtotal for this invoice
	 *
	 * By default this is defined as item_total. Site-specific sub-classes may
	 * include other values in addition to item_total.
	 *
	 * @return integer this invoice's subtotal.
	 */
	public function getSubtotal()
	{
		return $this->item_total;
	}

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

		return $ds;
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
