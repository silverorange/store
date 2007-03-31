<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/pages/StoreCheckoutUIPage.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';
require_once 'Store/dataobjects/StoreCartEntry.php';

/**
 * Confirmation page of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCheckoutConfirmationPage extends StoreCheckoutUIPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->ui_xml = 'Store/pages/checkout-confirmation.xml';
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->checkOrder();
	}

	// }}}
	// {{{ private function checkOrder()

	private function checkOrder()
	{
		$order = $this->app->session->order;

		if (!($order->billing_address instanceof StoreOrderAddress))
			throw new StoreException('Missing billing address.  '.
				'StoreOrder::billing_address must be a valid reference to a '.
				'StoreOrderAddress object by this point.');

		if (!($order->shipping_address instanceof StoreOrderAddress))
			throw new StoreException('Missing shipping address.  '.
				'StoreOrder::shipping_address must be a valid reference to a '.
				'StoreOrderAddress object by this point.');
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->ui->process();

		$form = $this->ui->getWidget('form');

		if ($form->isProcessed()) {
			$transaction = new SwatDBTransaction($this->app->db);
			try {
				$this->save();
			} catch (Exception $e) {
				$transaction->rollback();
				throw $e;
			}
			$transaction->commit();

			try {
				$this->processPayment();
				$payment_processing_successful = true;
			} catch (StorePaymentException $e) {
				$payment_processing_successful = false;
				$this->handlePaymentException($e);
			}

			$order = $this->app->session->order;
			$order->sendConfirmationEmail($this->app,
				$payment_processing_successful);

			if ($payment_processing_successful) {
				$this->removeCartEntries();
				$this->cleanupOnSuccess();
				$this->updateProgress();
				$this->app->relocate('checkout/thankyou');
			} else {
				$new_order = $order->duplicate();
				$this->app->session->order = $new_order;
			}
		}
	}

	// }}}
	// {{{ protected function processPayment()

	/**
	 * Does automatic card payment processing for an order
	 *
	 * By default, no automatic payment processing is done. Subclasses should
	 * override this method to perform automatic payment processing.
	 *
	 * @see StorePaymentProvider
	 */
	protected function processPayment()
	{
	}

	// }}}
	// {{{ protected function save()

	protected function save()
	{
		$this->saveAccount();
		$this->saveOrder();
	}

	// }}}
	// {{{ protected function saveAccount()

	protected function saveAccount()
	{
		$account = $this->app->session->account;

		// if we are creating a new account, store new addresses and payment
		// methods in account and set the createdate to now
		if ($this->app->session->checkout_with_account) {
			$order = $this->app->session->order;

			$this->addAddressToAccount($order->billing_address);

	 		// shipping address is only added if it differs from billing address
			if ($order->shipping_address->id !== $order->billing_address->id)
				$this->addAddressToAccount($order->shipping_address);

	 		// new payment methods are only added if a session flag is set
			if ($this->app->session->save_account_payment_method)
				$this->addPaymentMethodToAccount($order->payment_method);

			// set createdate and last_login to now
			$account->createdate = new SwatDate();
			$account->createdate->toUTC();
			$account->last_login = new SwatDate();
			$account->last_login->toUTC();
		}

		// save account
		$account->save();
	}

	// }}}
	// {{{ protected function saveOrder()

	protected function saveOrder()
	{
		$order = $this->app->session->order;

		// attach order to account
		if ($this->app->session->checkout_with_account)
			$order->account = $this->app->session->account;

		// set createdate to now
		$order->createdate = new SwatDate();
		$order->createdate->toUTC();

		// save order
		$order->save();
	}

	// }}}
	// {{{ protected function addAddressToAccount()

	protected function addAddressToAccount(StoreOrderAddress $order_address)
	{
		// check that address is not already in account
		if ($order_address->getAccountAddressId() === null) {
			$class_map = StoreClassMap::instance();
			$class_name = $class_map->resolveClass('StoreAccountAddress');
			$account_address = new $class_name();
			$account_address->copyFrom($order_address);
			$this->app->session->account->addresses->add($account_address);
		}
	}

	// }}}
	// {{{ protected function addPaymentMethodToAccount()

	protected function addPaymentMethodToAccount(
		StoreOrderPaymentMethod $order_payment_method)
	{
		// check that payment method is not already in account
		if ($order_payment_method->getAccountPaymentMethodId() === null) {
			$class_map = StoreClassMap::instance();
			$class_name = $class_map->resolveClass('StoreAccountPaymentMethod');
			$account_payment_method = new $class_name();
			$account_payment_method->copyFrom($order_payment_method);

			$this->app->session->account->payment_methods->add(
				$account_payment_method);
		}
	}

	// }}}
	// {{{ protected function removeCartEntries()

	protected function removeCartEntries()
	{
		$order = $this->app->session->order;

		// remove entries from cart that were ordered
		foreach ($order->items as $order_item) {
			$entry_id = $order_item->getCartEntryId();
			$this->app->cart->checkout->removeEntryById($entry_id);
		}

		$this->app->cart->save();
	}

	// }}}
	// {{{ protected function cleanupOnSuccess()

	protected function cleanupOnSuccess()
	{
		// unset session variable flags
		unset($this->app->session->ad);
		unset($this->app->session->save_account_payment_method);
	}

	// }}}
	// {{{ protected function handlePaymentException()

	/**
	 * Handles exceptions produced by automatic card payment processing
	 *
	 * @see StorePaymentProvider
	 */
	protected function handlePaymentException($e)
	{
		$message = new SwatMessage('', SwatMessage::ERROR);
		$message->content_type= 'text/xml';

		$message->primary_content =
			Store::_('There was a problem processing your payment.');

		// TODO: review/mangle/replace error messages
		$message->secondary_content = $e->getMessage();

		$this->ui->getWidget('message_display')->add($message);

		// TODO: possibly relocate on some payment processing errors
		//       and give no opportunity to edit the order
		//$this->app->relocate('checkout/paymentfailed');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-confirmation-page.css',
			Store::PACKAGE_ID));

		$message = new SwatMessage(Store::_('Please Review Your Order'));
		$message->content_type= 'text/xml';
		$message->secondary_content = sprintf(Store::_('Press the '.
			'%sPlace Order%s button to complete your order.'),
			'<em>', '</em>');

		$this->ui->getWidget('message_display')->add($message);

		$this->createOrder();
		$order = $this->app->session->order;

		$this->buildItems($order);
		$this->buildBasicInfo($order);
		$this->buildBillingAddress($order);
		$this->buildShippingAddress($order);
		$this->buildPaymentMethod($order);
	}

	// }}}
	// {{{ protected function buildBasicInfo()

	protected function buildBasicInfo($order)
	{
		$ds = new SwatDetailsStore($order);
		$view = $this->ui->getWidget('basic_info_details');

		if ($this->app->session->isLoggedIn())
			$ds->fullname = $this->app->session->account->fullname;
		else
			$view->getField('fullname_field')->visible = false;

		$view->data = $ds;
	}

	// }}}
	// {{{ protected function buildBillingAddress()

	protected function buildBillingAddress($order)
	{
		ob_start();	
		$order->billing_address->display();

		$this->ui->getWidget('billing_address')->content = ob_get_clean();
		$this->ui->getWidget('billing_address')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildShippingAddress()

	protected function buildShippingAddress($order)
	{
		ob_start();	
		// compare references since these are not saved yet
		if ($order->shipping_address === $order->billing_address) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-none';
			$span_tag->setContent(Store::_('<ship to billing address>'));
			$span_tag->display();
		} else {
			$order->shipping_address->display();
		}

		$this->ui->getWidget('shipping_address')->content = ob_get_clean();
		$this->ui->getWidget('shipping_address')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildPaymentMethod()

	protected function buildPaymentMethod($order)
	{
		ob_start();	
		$order->payment_method->display();

		$this->ui->getWidget('payment_method')->content = ob_get_clean();
		$this->ui->getWidget('payment_method')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildItems()

	protected function buildItems($order)
	{
		$items_view = $this->ui->getWidget('items_view');
		$items_view->model = $order->getOrderDetailsTableStore();

		$items_view->getRow('shipping')->value = $order->shipping_total;
		$items_view->getRow('subtotal')->value = $order->getSubtotal();

		$items_view->getRow('total')->value = $order->total;
	}

	// }}}
	// {{{ protected function createOrder()

	protected function createOrder()
	{
		$cart = $this->app->cart->checkout;
		$order = $this->app->session->order;

		$this->createOrderItems($order);

		$order->locale = $this->app->getLocale();

		if (isset($this->session->ad))
			$order->ad = $this->session->ad;

		$order->item_total = $cart->getItemTotal();

		$order->shipping_total = $cart->getShippingTotal(
			$order->billing_address, $order->shipping_address);

		$order->tax_total = $cart->getTaxTotal($order->billing_address,
			 $order->shipping_address);

		$order->total = $cart->getTotal($order->billing_address,
			$order->shipping_address);

		if (isset($this->app->session->ad))
			$order->ad = $this->app->session->ad;
	}

	// }}}
	// {{{ protected function createOrderItems()

	protected function createOrderItems($order)
	{
		$class_map = StoreClassMap::instance();
		$wrapper = $class_map->resolveClass('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$order_item = $entry->createOrderItem();
			$order->items->add($order_item);
		}
	}

	// }}}
}

?>
