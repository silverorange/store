<?php

require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/StoreOrderConfirmationMailMessage.php';
require_once 'Store/StoreOrderStatus.php';
require_once 'Store/StoreOrderStatusList.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrderAddress.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';
require_once 'Store/dataobjects/StorePaymentTransactionWrapper.php';
require_once 'Store/dataobjects/StoreAd.php';
require_once 'Store/dataobjects/StoreLocale.php';
require_once 'Store/dataobjects/StoreInvoice.php';

/**
 *
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrder extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Snapshot of the customer's email address
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Snapshot of the customer's Phone Number
	 *
	 * @var string
	 */
	public $phone;

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

	/**
	 * Whether or not this order is cancelled
	 *
	 * @var boolean
	 */
	public $cancelled = false;

	// }}}
	// {{{ protected properties

	/**
	 * Status of the order
	 *
	 * One of the StoreOrder::STATUS_* constants.
	 *
	 * @var integer
	 */
	protected $status;

	// }}}
	// {{{ public function getSubtotal()

	/**
	 * Gets the subtotal for this order
	 *
	 * By default this is defined as item_total. Site-specific sub-classes may
	 * include other values in addition to item_total.
	 *
	 * @return integer this order's subtotal.
	 */
	public function getSubtotal()
	{
		return $this->item_total;
	}

	// }}}
	// {{{ public function getOrderDetailsTableStore()

	public function getOrderDetailsTableStore()
	{
		$store = new SwatTableStore();

		foreach ($this->items as $item) {
			$ds = $this->getOrderItemDetailsStore($item);
			$store->addRow($ds);
		}

		return $store;
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		return sprintf('Order %s', $this->id);
	}

	// }}}
	// {{{ public function sendConfirmationEmail()

	public function sendConfirmationEmail(SiteApplication $app)
	{
		// This is demo code. StoreOrderConfirmationMailMessage is
		// abstract and the site-specific version must be used.

		if ($this->email === null)
			return;

		try {
			$email = new StoreOrderConfirmationMailMessage($app, $this);
			$email->send();
		} catch (SiteMailException $e) {
			$e->process(false);
		}
	}

	// }}}
	// {{{ public function sendPaymentFailedEmail()

	public function sendPaymentFailedEmail(SiteApplication $app)
	{
		// This is demo code. StoreOrderConfirmationMailMessage is
		// abstract and the site-specific version must be used.

		if ($this->email === null)
			return;

		try {
			$email = new StoreOrderConfirmationMailMessage($app, $this);
			$email->send();
		} catch (SiteMailException $e) {
			$e->process(false);
		}
	}

	// }}}
	// {{{ public function getReceiptHeader()

	/**
	 * Gets the header text for order receipts
	 *
	 * Subclasses should return a string from this method if they wish to
	 * display a header on all order receipts. By default, an empty string is
	 * returned so no header is displayed on order receipts.
	 *
	 * @return string the header text for order receipts.
	 */
	public function getReceiptHeader()
	{
		return '';
	}

	// }}}
	// {{{ public function getReceiptFooter()

	/**
	 * Gets the footer text for order receipts
	 *
	 * This text will be displayed as footer on all order receipts. By default,
	 * a note indicating in which currency prices are displayed is returned.
	 *
	 * @return string the footer text for order receipts.
	 */
	public function getReceiptFooter()
	{
		$locale_id = $this->getInternalValue('locale');
		return sprintf(Store::_('All displayed prices are in %s.'),
			SwatString::getInternationalCurrencySymbol($locale_id));
	}

	// }}}
	// {{{ public function getDescription()

	/**
	 * Gets a short, textual description of this order
	 *
	 * For example: "Example Company Order #12345".
	 *
	 * This description is used for various purposes including financial
	 * transaction records.
	 *
	 * @return string a short, textual description of this order.
	 */
	public function getDescription()
	{
		return sprintf('Order #%s', $this->id);
	}

	// }}}
	// {{{ public function duplicate()

	public function duplicate()
	{
		$new_order = parent::duplicate();

		if ($this->shipping_address === $this->billing_address)
			$new_order->shipping_address = $new_order->billing_address;

		return $new_order;
	}

	// }}}
	// {{{ public function isFromInvoice()

	/**
	 * Whether this order is generated from an invoice
	 *
	 * @return boolean true if this order is from an invoice.
	 */
	public function isFromInvoice()
	{
		return ($this->getInternalValue('invoice') !== null);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('status');
		$this->registerInternalProperty('account',
			$this->class_map->resolveClass('StoreAccount'));

		$this->registerInternalProperty('billing_address',
			$this->class_map->resolveClass('StoreOrderAddress'), true);

		$this->registerInternalProperty('shipping_address',
			$this->class_map->resolveClass('StoreOrderAddress'), true);

		$this->registerInternalProperty('payment_method',
			$this->class_map->resolveClass('StoreOrderPaymentMethod'), true);

		$this->registerInternalProperty('locale',
			$this->class_map->resolveClass('StoreLocale'), true);

		$this->registerInternalProperty('ad',
			$this->class_map->resolveClass('StoreAd'), true);

		$this->registerInternalProperty('invoice',
			$this->class_map->resolveClass('StoreInvoice'));

		$this->registerDateProperty('createdate');

		$this->table = 'Orders';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array(
			'shipping_address',
			'billing_address',
			'payment_method',
			'items',
		);
	}

	// }}}
	// {{{ protected function getOrderItemDetailsStore()

	public function getOrderItemDetailsStore($item)
	{
		$ds = new SwatDetailsStore($item);
		$ds->item = $item;

		return $ds;
	}

	// }}}

	// order status methods
	// {{{ public function getStatus()

	/** 
	 * Gets the status of this order
	 *
	 * @return StoreOrderStatus the status of this order or null if this
	 *                           orders's status is undefined.
	 */
	public function getStatus()
	{
		if ($this->status === null && $this->hasInternalValue('status')) {
			$list = StoreOrderStatusList::statuses();
			$this->status = $list->getById($this->getInternalValue('status'));
		}

		return $this->status;
	}

	// }}}
	// {{{ public function setStatus()

	public function setStatus(StoreOrderStatus $status)
	{
		$this->status = $status;
		$this->setInternalValue('status', $status->id);
	}

	// }}}
	// {{{ public function isBillable()

	/**
	 * Gets whether or not this order is ready to bill
	 *
	 * This order is ready to bill if payment is authorized and this order is
	 * not cancelled.
	 *
	 * @return boolean true if this order is ready to be billed and false if it
	 *                  is not.
	 */
	public function isBillable()
	{
		return (!$this->cancelled &&
			$this->getStatus() === StoreOrderStatusList::status('authorized'));
	}

	// }}}
	// {{{ public function isShippable()

	/**
	 * Gets whether or not this order is ready to ship 
	 *
	 * This order is ready to ship if payment is completed and this order is
	 * not cancelled.
	 *
	 * @return boolean true if this order is ready to be shipped and false if
	 *                  it is not.
	 */
	public function isShippable()
	{
		return (!$this->cancelled &&
			$this->getStatus() === StoreOrderStatusList::status('billed'));
	}

	// }}}

	// loader methods
	// {{{ protected function loadItems()

	protected function loadItems()
	{
		$sql = sprintf('select * from OrderItem
			where ordernum = %s
			order by sku asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			$this->class_map->resolveClass('StoreOrderItemWrapper'));
	}

	// }}}
	// {{{ protected function loadTransactions()

	protected function loadTransactions()
	{
		$sql = sprintf('select * from PaymentTransaction
			where ordernum = %s
			order by createdate asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			$this->class_map->resolveClass('StorePaymentTransactionWrapper'));
	}

	// }}}

	// saver methods
	// {{{ protected function saveItems()

	/**
	 * Automatically saves StoreOrderItem sub-data-objects when this
	 * StoreOrder object is saved
	 */
	protected function saveItems()
	{
		foreach ($this->items as $item)
			$item->ordernum = $this;

		$this->items->setDatabase($this->db);
		$this->items->save();
	}

	// }}}
}

?>
