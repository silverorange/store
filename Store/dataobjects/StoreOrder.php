<?php

require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteAd.php';
require_once 'Store/StoreOrderConfirmationMailMessage.php';
require_once 'Store/StoreOrderStatus.php';
require_once 'Store/StoreOrderStatusList.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrderAddress.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethodWrapper.php';
require_once 'Store/dataobjects/StorePaymentMethodTransactionWrapper.php';
require_once 'Store/dataobjects/StoreShippingType.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';
require_once 'Store/dataobjects/StoreLocale.php';
require_once 'Store/dataobjects/StoreInvoice.php';

/**
 *
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrder extends SwatDBDataObject
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
	 * Extra email address to which the order confirmation email is CC'd
	 *
	 * @var string
	 */
	public $cc_email;

	/**
	 * Snapshot of the customer's company name
	 *
	 * @var string
	 */
	public $company;

	/**
	 * Snapshot of the customer's phone number
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
	 * Admin Notes
	 *
	 * @var string
	 */
	public $notes;

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
	 * Surcharge total
	 *
	 * @var float
	 */
	public $surcharge_total;

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

	/**
	 * Whether or not this order is a failed order attempt stored only
	 * for debugging and recordkeeping
	 *
	 * @var boolean
	 */
	public $failed_attempt = false;

	// }}}
	// {{{ protected properties

	/**
	 * The id of the {@link StoreOrderStatus} of this order
	 *
	 * @var integer
	 *
	 * @see StoreOrder::getStatus()
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
			$store->add($ds);
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
	// {{{ public function getReceiptHeaderXml()

	/**
	 * Gets the header text for order receipts
	 *
	 * Subclasses should return a string from this method if they wish to
	 * display a header on all order receipts. By default, an empty string is
	 * returned so no header is displayed on order receipts.
	 *
	 * @return string the header text for order receipts.
	 */
	public function getReceiptHeaderXml()
	{
		return '';
	}

	// }}}
	// {{{ public function getReceiptHeaderText()

	/**
	 * Gets the header text for order receipts
	 *
	 * Subclasses should return a string from this method if they wish to
	 * display a header on all order receipts. By default, an empty string is
	 * returned so no header is displayed on order receipts.
	 *
	 * @return string the header text for order receipts.
	 */
	public function getReceiptHeaderText()
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
		return sprintf(Store::_('All prices are in %s.'),
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
	// {{{ public function unserialize()

	public function unserialize($data)
	{
		parent::unserialize($data);

		// TODO: remove this
		// temp to migrate payment methods in sessions
		if ($this->hasSubDataObject('payment_method') &&
			!$this->hasSubDataObject('payment_methods')) {

			$payment_method = $this->getSubDataObject('payment_method');

			if ($payment_method !== null) {
				$this->payment_methods = new StoreOrderPaymentMethodWrapper();
				$this->payment_methods->add($payment_method);
			}
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('status');
		$this->registerInternalProperty('account',
			SwatDBClassMap::get('StoreAccount'));

		$this->registerInternalProperty('billing_address',
			SwatDBClassMap::get('StoreOrderAddress'), true);

		$this->registerInternalProperty('shipping_address',
			SwatDBClassMap::get('StoreOrderAddress'), true);

		// TODO: remove this field
		$this->registerDeprecatedProperty('payment_method');
		/*
		$this->registerInternalProperty('payment_method',
			SwatDBClassMap::get('StoreOrderPaymentMethod'), true);
		*/

		$this->registerInternalProperty('shipping_type',
			SwatDBClassMap::get('StoreShippingType'));

		$this->registerInternalProperty('locale',
			SwatDBClassMap::get('StoreLocale'), true);

		$this->registerInternalProperty('ad',
			SwatDBClassMap::get('SiteAd'), true);

		$this->registerInternalProperty('invoice',
			SwatDBClassMap::get('StoreInvoice'));

		$this->registerInternalProperty('previous_attempt',
			SwatDBClassMap::get('StoreOrder'));

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
			'payment_methods',
			'items',
		);
	}

	// }}}
	// {{{ protected function getOrderItemDetailsStore()

	public function getOrderItemDetailsStore($order_item)
	{
		$ds = new SwatDetailsStore($order_item);
		$ds->item = $order_item;
		$ds->description = $order_item->getDescription();

		if ($order_item->alias_sku !== null &&
			$order_item->alias_sku != '')
			$ds->sku.= sprintf(' (%s)', $order_item->alias_sku);

		$item = $order_item->getAvailableItem($this->locale->region);

		if ($item !== null && $item->product->primary_image !== null) {
			$image = $item->product->primary_image;
			$ds->image = $image->getUri($this->getImageDimension());
			$ds->image_width = $image->getWidth($this->getImageDimension());
			$ds->image_height = $image->getHeight($this->getImageDimension());
		} else {
			$ds->image = null;
			$ds->image_width = null;
			$ds->image_height = null;
		}

		$ds->item_count = $this->getProductItemCount($order_item->product);

		return $ds;
	}

	// }}}
	// {{{ protected function getImageDimension()

	/**
	 * @return string Image dimension shortname
	 */
	protected function getImageDimension()
	{
		return 'pinky';
	}

	// }}}
	// {{{ protected function setDeprecatedProperty()

	protected function setDeprecatedProperty($key, $value)
	{
		// TODO: remove this
		// temp to migrate payment methods in sessions
		if ($key === 'payment_method' && $this->payment_methods === null) {
			$this->payment_methods = new StoreOrderPaymentMethodWrapper();
			$this->payment_methods->add($value);
		}
	}

	// }}}
	// {{{ protected function getDeprecatedProperty()

	protected function getDeprecatedProperty($key)
	{
		// TODO: remove this
		// temp to migrate payment methods in sessions
		if ($key === 'payment_method')
			return $this->payment_methods->getFirst();
	}

	// }}}
	// {{{ private function getProductItemCount()

	private function getProductItemCount($product_id)
	{
		$count = 0;
		$items = clone $this->items;

		foreach ($items as $item)
			if ($product_id == $item->product)
				$count++;

		return $count;
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
		return (!$this->failed_attempt && !$this->cancelled &&
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
		return (!$this->failed_attempt && !$this->cancelled &&
			$this->getStatus() === StoreOrderStatusList::status('billed'));
	}

	// }}}
	// {{{ public function isFinished()

	/**
	 * Gets whether or not this order is finished being processed
	 *
	 * This order is finished being processed if the order has been shipped and
	 * is not cancelled.
	 *
	 * @return boolean true if this order is finished and false if it is not.
	 */
	public function isFinished()
	{
		return (!$this->failed_attempt && !$this->cancelled &&
			$this->getStatus() === StoreOrderStatusList::status('shipped'));
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
			SwatDBClassMap::get('StoreOrderItemWrapper'));
	}

	// }}}
	// {{{ protected function loadPaymentMethods()

	protected function loadPaymentMethods()
	{
		$sql = sprintf('select * from OrderPaymentMethod
			inner join PaymentType on
				OrderPaymentMethod.payment_type = PaymentType.id
			where ordernum = %s
			order by PaymentType.displayorder, PaymentType.title',
			$this->db->quote($this->id, 'integer'));

		$payment_methods = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreOrderPaymentMethodWrapper'));

		// efficiently load transactions for all payment methods
		$payment_methods->loadAllSubRecordsets('transactions',
			SwatDBClassMap::get('StorePaymentMethodTransactionWrapper'),
			'PaymentMethodTransaction', 'payment_method', '', 'createdate, id');

		return $payment_methods;
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
	// {{{ protected function savePaymentMethods()

	/**
	 * Automatically saves StoreOrderPaymentMethod sub-data-objects when this
	 * StoreOrder object is saved
	 */
	protected function savePaymentMethods()
	{
		foreach ($this->payment_methods as $payment_method)
			$payment_method->ordernum = $this;

		$this->payment_methods->setDatabase($this->db);
		$this->payment_methods->save();
	}

	// }}}
}

?>
