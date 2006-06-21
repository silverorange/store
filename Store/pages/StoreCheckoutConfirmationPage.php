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
		$wrapper = $this->class_map->resolveClass('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		foreach ($this->app->cart->checkout->getEntries() as $entry) {
			$item = $this->createOrderItem($entry);
			$order->items->add($item);
		}
	}

	// }}}
	// {{{ private function createOrderItem()

	private function createOrderItem(StoreCartEntry $entry)
	{
		$class = $this->class_map->resolveClass('StoreOrderItem');
		$order_item = new $class();

		$order_item->price = $entry->item->getCalculatedItemPrice();
		$order_item->quantity = $entry->item->getQuantity();
		$order_item->extension = $entry->item->getExtension();
		$order_item->description = $entry->item->description;
		$order_item->product = $entry->item->product->id;
		$order_item->product_title = $entry->item->product->title;
		$order_item->quick_order = $entry->quick_order;

		return $order_item;
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
