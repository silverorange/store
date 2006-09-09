<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'Store/pages/StoreCheckoutPage.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';
require_once 'Store/dataobjects/StoreCartEntry.php';

/**
 * Confirmation page of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCheckoutConfirmationPage extends StoreCheckoutPage
{
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

		$message = new SwatMessage('Please Review Your Order');
		$message->content_type= 'text/xml';
		$message->secondary_content = 'Press the <em>Place Order</em> button to complete your order.';
		$this->ui->getWidget('message_display')->add($message);

		if ($form->isProcessed()) {
			$transaction = new SwatDBTransaction($this->app->db);
			try {
				$this->processAccount();
				$this->processOrder();
			} catch (Exception $e) {
				$transaction->rollback();
				throw $e;
			}
			$transaction->commit();

			$this->updateProgress();
			$this->app->relocate('checkout/thankyou');
		}
	}

	// }}}
	// {{{ protected function processOrder()

	/**
	 * @return StoreOrder the order object
	 */
	protected function processOrder()
	{
		$order = $this->app->session->order;

		// attach order to account
		if ($this->app->session->checkout_with_account)
			$order->account = $this->app->session->account;

		// set createdate to now
		$order->createdate = new SwatDate();

		// save order
		$order->save();

		// remove entries from cart that were ordered
		$this->removeCartEntries($order);

		unset($this->app->session->ad);

		return $order;
	}

	// }}}
	// {{{ protected function processAccount()

	protected function processAccount()
	{
		$account = $this->app->session->account;
		$order = $this->app->session->order;

		// store new addresses and payment methods in account
		if ($this->app->session->checkout_with_account) {
			$this->addAddressToAccount($order->billing_address);

			if ($order->shipping_address->id !== $order->billing_address->id)
				$this->addAddressToAccount($order->shipping_address);

			$this->addPaymentMethodToAccount($order->payment_method);
		}

		$account->save();
	}

	// }}}
	// {{{ protected function addAddressToAccount()

	protected function addAddressToAccount(StoreOrderAddress $order_address)
	{
		// check that address is not already in account
		if ($order_address->getAccountAddressId() === null) {
			$account_address = new StoreAccountAddress();
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
			$account_payment_method = new AccountPaymentMethod();
			$account_payment_method->copyFrom($order_payment_method);

			$this->app->session->account->payment_methods->add(
				$account_payment_method);
		}
	}

	// }}}
	// {{{ protected function removeCartEntries()

	protected function removeCartEntries($order)
	{
		foreach ($order->items as $order_item) {
			$entry_id = $order_item->getCartEntryId();
			$this->app->cart->checkout->removeEntryById($entry_id);
		}

		$this->app->cart->save();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->createOrder();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-confirmation-page.css',
			Store::PACKAGE_ID));
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

		$tax_provstate = $this->app->cart->checkout->getTaxProvState(
			$order->billing_address, $order->shipping_address);

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$order_item = $entry->createOrderItem($tax_provstate);
			$order->items->add($order_item);
		}
	}

	// }}}
}

?>
