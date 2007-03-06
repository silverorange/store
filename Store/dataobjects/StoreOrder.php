<?php

require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/StoreOrderConfirmationMailMessage.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrderAddress.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';
require_once 'Store/dataobjects/StorePaymentTransactionWrapper.php';
require_once 'Store/dataobjects/StoreAd.php';
require_once 'Store/dataobjects/StoreLocale.php';

/**
 *
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrder extends StoreDataObject
{
	// {{{ class constants

	/**
	 * Payment not authorized or received, the default state for orders
	 */
	const STATUS_INITIALIZED = 1;

	/**
	 * Payment is authorized but not received
	 */
	const STATUS_AUTHORIZED  = 2;

	/**
	 * Payment is received and order is ready for shipping
	 */
	const STATUS_BILLED      = 3;

	/**
	 * Order is shipped
	 */
	const STATUS_SHIPPED     = 4;

	/**
	 * Shipping provider has confirmed delivery of the order
	 */
	const STATUS_DELIVERED   = 5;
	
	// }}}
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
	 * Status of the order
	 *
	 * One of the StoreOrder::STATUS_* constants.
	 *
	 * @var integer
	 */
	public $status = self::STATUS_INITIALIZED;

	/**
	 * Whether or not this order is cancelled
	 *
	 * @var boolean
	 */
	public $cancelled = false;

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
	// {{{ public function getReceiptHeader()

	public function getReceiptHeader()
	{
		return 'Thank you for placing an order online.';
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
	// {{{ protected function init()

	protected function init()
	{
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
			$this->status == self::STATUS_AUTHORIZED);
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
		return (!$this->cancelled && $this->status == self::STATUS_BILLED);
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
