<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Confirmation page of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCheckoutThankYouPage extends StoreCheckoutPage
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->resetProgress();
	}

	// }}}
	// {{{ protected function checkCart()

	protected function checkCart()
	{
		// do nothing - cart should be empty now
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout/confirmation');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		// clear the order and logout
		unset($this->app->session->order);
		$this->app->session->logout();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-thank-you-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$this->ui = new StoreUI();
		$this->ui->loadFromXML('Store/pages/checkout-thank-you.xml');

		$this->ui->init();

		$this->ui->getWidget('header')->content =
			SwatString::toXHTML($this->app->session->order->getReceiptHeader()).
			'<div style="page-break-after: always"></div>';

		if ($this->app->session->order->account !== null) {
			$message = new SwatMessage('Your Account',
				SwatMessage::NOTIFICATION);

			$message->secondary_content = 'By logging into your account the '.
				'next time you visit our website, you can edit your addresses '.
				'and payment methods, view previously placed orders, re-order '.
				'items from your previous orders, and checkout without '.
				'having to re-enter all of your address and payment '.
				'information.';

			$this->ui->getWidget('message_display')->add($message);
		}

		$this->buildOrderDetails();
	}

	// }}}
	// {{{ protected function buildOrderDetails()

	protected function buildOrderDetails()
	{
		$order = $this->app->session->order;

		$details_view =  $this->ui->getWidget('order_details');
		$details_view->data = new SwatDetailsStore($order);

		$createdate_column = $details_view->getField('createdate');
		$createdate_renderer = $createdate_column->getFirstRenderer();
		$createdate_renderer->display_time_zone =
			$this->app->default_time_zone;

		if ($order->email=== null)
			$details_view->getField('email')->visible = false;

		if ($order->comments === null)
			$details_view->getField('comments')->visible = false;

		if ($order->phone === null)
			$details_view->getField('phone')->visible = false;

		$items_view = $this->ui->getWidget('items_view');
		$items_view->model = $order->getOrderDetailsTableStore();

		$items_view->getRow('shipping')->value = $order->shipping_total;
		$items_view->getRow('subtotal')->value = $order->getSubtotal();

		$items_view->getRow('total')->value = $order->total;
	}

	// }}}
}

?>
