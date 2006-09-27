<?php

require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/pages/StoreCheckoutEditPage.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';

/**
 * Payment method edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCheckoutPaymentMethodPage extends StoreCheckoutEditPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->ui_xml = 'Store/pages/checkout-payment-method.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		$expiry = $this->ui->getWidget('credit_card_expiry');
		$expiry->valid_range_start = new Date();
	}

	// }}}

	// process phase
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
		$method_list = $this->ui->getWidget('payment_method_list');
		$method_list->process();

		if ($method_list->value !== null && $method_list->value !== 'new') {
			$container = $this->ui->getWidget('payment_method_form');
			$controls = $container->getDescendants('SwatInputControl');
			foreach ($controls as $control)
				$control->required = false;
		}
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
		if ($this->ui->getWidget('form')->hasMessage())
			return;

		$this->saveDataToSession();
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$method_list = $this->ui->getWidget('payment_method_list');

		if ($method_list->value === null || $method_list->value === 'new') {

			if ($this->app->session->order->payment_method !== null &&
				$this->app->session->order->payment_method->getAccountPaymentMethodId() === null) {
					$order_payment_method = $this->app->session->order->payment_method;
			} else {
				$class_map = StoreClassMap::instance();
				$class_name = $class_map->resolveClass('StoreOrderPaymentMethod');
				$order_payment_method = new $class_name();
			}

			$order_payment_method->payment_type =
				$this->ui->getWidget('payment_type')->value;

			$credit_card_number = $this->ui->getWidget('credit_card_number')->value;
			if ($credit_card_number !== null)
				$order_payment_method->setCreditCardNumber(
					$credit_card_number);

			$order_payment_method->credit_card_expiry =
				$this->ui->getWidget('credit_card_expiry')->value;

			$order_payment_method->credit_card_fullname =
				$this->ui->getWidget('credit_card_fullname')->value;

		} else {
			$method_id = intval($method_list->value);

			$account_payment_method = 
				$this->app->session->account->payment_methods->getByIndex($method_id);

			if (!($account_payment_method instanceof StoreAccountPaymentMethod))
				throw new StoreException('Account payment method not found. '.
					"Method with id '$method_id' not found.");

			$class_map = StoreClassMap::instance();
			$class_name = $class_map->resolveClass('StoreOrderPaymentMethod');
			$order_payment_method = new $class_name();
			$order_payment_method->copyFrom($account_payment_method);
		}

		$this->app->session->order->payment_method = $order_payment_method;

		if ($this->app->session->checkout_with_account)
			$this->app->session->save_account_payment_method =
				$this->ui->getWidget('save_account_payment_method')->value;
	}

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		$this->layout->addHtmlHeadEntry(
			new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-payment-method-page.css',
			Store::PACKAGE_ID));

		$this->buildList();
		$this->buildForm();
	}

	// }}}
	// {{{ public function postBuildCommon()

	public function postBuildCommon()
	{
		$method_list = $this->ui->getWidget('payment_method_list');

		if ($method_list->visible) {
			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/store/javascript/store-checkout-page.js',
				Store::PACKAGE_ID));

			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/store/javascript/store-checkout-payment-method.js',
				Store::PACKAGE_ID));

			$this->layout->startCapture('content');
			$this->displayJavaScript();
			$this->layout->endCapture();
		}
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;

		if ($order->payment_method === null) {
			$this->ui->getWidget('credit_card_fullname')->value =
				$this->app->session->account->fullname;
		} else {
			if ($order->payment_method->getAccountPaymentMethodId() === null) {

				$this->ui->getWidget('payment_type')->value =
					$order->payment_method->getInternalValue('payment_type');

				/*
				 *  Note: We can't repopulate the credit card number entry
				 *        since we only store the encrypted number in the
				 *        dataobject.
				 */
				$this->ui->getWidget('credit_card_number')->show_blank_value = true;

				$this->ui->getWidget('credit_card_expiry')->value =
					$order->payment_method->credit_card_expiry;

				$this->ui->getWidget('credit_card_fullname')->value =
					$order->payment_method->credit_card_fullname;
			} else {
				$this->ui->getWidget('payment_method_list')->value =
					$order->payment_method->getAccountPaymentMethodId();
			}
		}

		if ($this->app->session->checkout_with_account &&
			isset($this->app->session->save_account_payment_method)) {
			$this->ui->getWidget('save_account_payment_method')->value =
				$this->app->session->save_account_payment_method;
		}
	}

	// }}}
	// {{{ protected function buildList()

	protected function buildList()
	{
		$method_list = $this->ui->getWidget('payment_method_list');
		$method_list->addOption('new',
			'<span class="add-new">Add a New Credit Card</span>', 'text/xml');

		if ($this->app->session->isLoggedIn()) {
			foreach ($this->app->session->account->payment_methods as $method) {
				$payment_type = $method->payment_type;
				if ($payment_type->isAvailableInRegion($this->app->getRegion())) {
					ob_start();
					$method->display();
					$method_display = ob_get_clean();
					$method_list->addOption($method->id, $method_display,
						'text/xml');
				}
			}

			if ($this->app->session->account->default_payment_method !== null)
				$method_list->value =
					$this->app->session->account->default_payment_method;
		}

		$method_list->visible = (count($method_list->options) > 1);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$payment_type_flydown = $this->ui->getWidget('payment_type');
		$payment_types_sql = sprintf('select id, title from PaymentType
			inner join PaymentTypeRegionBinding on
				payment_type = id and region = %s
			where enabled = true order by title',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$payment_types = SwatDB::query($this->app->db, $payment_types_sql);
		foreach ($payment_types as $payment_type)
			$payment_type_flydown->addOption(
				new SwatOption($payment_type->id, $payment_type->title));

		if (!$this->ui->getWidget('form')->isProcessed())
			$this->loadDataFromSession();

		if ($this->app->session->checkout_with_account) {
			$this->ui->getWidget('save_account_payment_method_field')->visible = true;
			$this->ui->getWidget('payment_method_note')->content = 
				'<p class="smallprint">See our <a href="about/website/privacy">'.
				'privacy &amp; security policy</a> for more information about '.
				'how your information will be used.</p>';
		}
	}

	// }}}
	// {{{ protected function displayJavaScript()

	protected function displayJavaScript()
	{
		$id = 'checkout_payment_method';
		echo '<script type="text/javascript">'."\n";
		printf("var %s_obj = new StoreCheckoutPaymentMethod('%s');\n",
			$id, $id);

		echo '</script>';
	}

	// }}}
}

?>
