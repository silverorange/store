<?php

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
		$this->createOrderTotals();
		$this->createOrderItems();
	}

	// }}}
	// {{{ protected function createOrderTotals()

	protected function createOrderTotals()
	{
		$cart = $this->app->cart->checkout;
		$order = $this->app->session->order;

		$billing_provstate = $order->billing_address->provstate;
		$shipping_provstate = $order->shipping_address->provstate;

		$order->item_total = $cart->getItemTotal();
		$order->shipping = $cart->getShippingTotal($shipping_provstate);
		$order->total = $cart->getTotal($billing_provstate, $shipping_provstate);
	}

	// }}}
	// {{{ protected function createOrderItems()

	protected function createOrderItems()
	{
		$order = $this->app->session->order;
		$class_map = StoreClassMap::instance();
		$wrapper = $class_map->resolveClass('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		$billing_provstate = $order->billing_address->provstate;
		$shipping_provstate = $order->shipping_address->provstate;

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$order_item = $entry->createOrderItem(
				$billing_provstate, $shipping_provstate);

			$order->items->add($order_item);
		}
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
			$this->updateProgress();
			$this->app->relocate('checkout/thankyou');
		}
	}

	// }}}
}

?>
