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
		$this->createOrderItems();
	}

	// }}}
	// {{{ private function createOrderItems()

	private function createOrderItems()
	{
		$order = $this->app->session->order;
		$class_map = StoreClassMap::instance();
		$wrapper = $class_map->resolveClass('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$order_item = 
				$entry->createOrderItem($order->billing_address->provstate);

			$order->items->add($order_item);
		}
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
