<?php

require_once 'Swat/SwatDetailsStore.php';

require_once 'Store/StoreUI.php';
require_once 'Store/pages/StoreAccountPage.php';
require_once 'Store/StoreAddressView.php';
require_once 'Store/StorePaymentMethodView.php';
require_once 'Store/dataobjects/StoreAccount.php';

/**
 * Page for viewing account details
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreAccountDetailsPage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-details.xml';

	protected $ui;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);

		$this->initInternal($this->app->session->account);

		$this->ui->init();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal(StoreAccount $account)
	{
		$this->initPaymentMethodViews($account);
		$this->initAddressViews($account);
	}

	// }}}
	// {{{ protected function initAddressViews()

	protected function initAddressViews(StoreAccount $account)
	{
		$container = $this->ui->getWidget('account_address_views');

		foreach ($account->addresses as $address) {
			$view_id = 'address'.$address->id;
			$view = new StoreAddressView($view_id);
			$view->classes[] = 'compact-button';
			$view->address = $address;
			$container->addChild($view);
		}
	}

	// }}}
	// {{{ protected function initPaymentMethodViews()

	protected function initPaymentMethodViews(StoreAccount $account)
	{
		$container = $this->ui->getWidget('account_payment_method_views');

		foreach ($account->payment_methods as $payment_method) {
			$payment_type = $payment_method->payment_type;
			if ($payment_type->isAvailableInRegion($this->app->getRegion())) {
				$view_id = 'payment_method'.$payment_method->id;
				$view = new StorePaymentMethodView($view_id);
				$view->paymentMethodConfirmText = 
					$this->getPaymentMethodText('confirm');

				$view->classes[] = 'compact-button';
				$view->payment_method = $payment_method;
				$container->addChild($view);
			}
		}
	}

	// }}}
	// {{{ protected function getPaymentMethodText()

	protected function getPaymentMethodText($text)
	{
		switch ($text) {
		case 'confirm' :
			return Store::_('Are you sure you want to remove the following '.
			'payment method?');

		case 'removed' :
			return Store::_('One payment method has been removed.');
		}
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->ui->process();

		$account = $this->app->session->account;
		$this->processInternal($account);

		if ($account->isModified()) {
			$account->save();
			$this->app->relocate('account');
		}
	}

	// }}}
	// {{{ protected function processInternal()

	protected function processInternal(StoreAccount $account)
	{
		$this->processAddressViews($account);
		$this->processPaymentMethodViews($account);
	}

	// }}}
	// {{{ protected function processAddressViews()

	protected function processAddressViews(StoreAccount $account)
	{
		$container = $this->ui->getWidget('account_address_container');
		$views = $container->getDescendants('StoreAddressView');

		foreach ($views as $view) {
			if ($view->hasBeenClicked()) {
				ob_start();
				$view->address->displayCondensed();
				$address_condensed = ob_get_clean();

				$account->addresses->remove($view->address);
				$view->visible = false;

				$message = new SwatMessage(
					Store::_('One address has been removed.'));

				$message->secondary_content = $address_condensed;
				$message->content_type = 'text/xml';
				$this->app->messages->add($message);
			}
		}
	}

	// }}}
	// {{{ protected function processPaymentMethodViews()

	protected function processPaymentMethodViews(StoreAccount $account)
	{
		$container = $this->ui->getWidget('account_payment_method_container');
		$views = $container->getDescendants('StorePaymentMethodView');
		
		foreach ($views as $view) {
			if ($view->hasBeenClicked()) {
				ob_start();
				$view->payment_method->display();
				$payment_condensed = ob_get_clean();

				$account->payment_methods->remove($view->payment_method);
				$view->visible = false;

				$message = new SwatMessage(
					$this->getPaymentMethodText('removed'));

				$message->secondary_content = $payment_condensed;
				$message->content_type = 'text/xml';
				$this->app->messages->add($message);
			}
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();
		$this->buildInternal();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-account-details-page.css',
			Store::PACKAGE_ID));

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$this->buildAccountDetails();
		$this->buildSavedCartMessage();
		$this->buildOrders();

		foreach ($this->app->messages->getAll() as $message)
			$this->ui->getWidget('message_display')->add($message);

		$this->ui->getWidget('account_form')->action = $this->source;
	}

	// }}}
	// {{{ protected function buildAccountDetails()

	protected function buildAccountDetails() 
	{
		$account = $this->app->session->account;

		$ds = new SwatDetailsStore($account);

		$details_view = $this->ui->getWidget('account_details_view');
		$details_view->data = $ds;

		if ($account->phone === null)
			$details_view->getField('phone')->visible = false;
	}

	// }}}
	// {{{ protected function buildSavedCartMessage()

	protected function buildSavedCartMessage()
	{
		$count = $this->app->cart->saved->getEntryCount();

		if ($count > 0) {
			$message = new SwatMessage(sprintf(Store::ngettext(
				'You have an item saved for later. View your '.
				'%sShopping Cart%s to add this item to your order.',
				'You have items saved for later. View your '.
				'%sShopping Cart%s to add these items to your order.',
				$count), '<a href="cart">', '</a>'));

			$message->content_type = 'text/xml';

			$message_display = $this->ui->getWidget('saved_cart_message');
			$message_display->add($message);
		}
	}

	// }}}
	// {{{ protected function buildOrders()

	protected function buildOrders() 
	{
		ob_start();

		$orders = $this->app->session->account->orders;

		if (count($orders) > 0) {
			$ul = new SwatHtmlTag('ul');
			$li = new SwatHtmlTag('li');

			$ul->open();

			foreach ($orders as $order) {
				$li->setContent($this->getOrderSummary($order), 'text/xml');
				$li->display();
			}

			$ul->close();
		} else {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'swat-none';
			$div_tag->setContent(Store::_('<none>'));
			$div_tag->display();
		}

		$this->ui->getWidget('account_order')->content_type = 'text/xml';
		$this->ui->getWidget('account_order')->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function getOrderSummary()

	protected function getOrderSummary($order)
	{
		ob_start();

		$createdate = clone $order->createdate;
		$createdate->convertTZ($this->app->default_time_zone);

		$a = new SwatHtmlTag('a');
		$a->href = sprintf('account/order%s', $order->id);
		$a->setContent($order->getTitle());
		$a->display();

		echo ' - ', $createdate->format(SwatDate::DF_DATE);

		return ob_get_clean();
	}

	// }}}
}

?>
