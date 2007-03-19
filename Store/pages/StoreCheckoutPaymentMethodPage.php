<?php

require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/pages/StoreCheckoutEditPage.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';
require_once 'Store/dataobjects/StorePaymentTypeWrapper.php';

/**
 * Payment method edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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

	// process phase
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
		// set debit card fields as required when a debit card is used
		$type_list = $this->ui->getWidget('payment_type');
		$type_list->process();

		$class_map = StoreClassMap::instance();
		$class_name = $class_map->resolveClass('StorePaymentType');
		$payment_type = new $class_name();
		$payment_type->setDatabase($this->app->db);
		$payment_type->load($type_list->value);

		$this->ui->getWidget('card_inception')->required =
			$payment_type->hasInceptionDate();

		$this->ui->getWidget('card_issue_number')->required =
			$payment_type->hasIssueNumber();

		// set all fields as not required when an existing payment method is
		// selected
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
		// make sure expiry date is after (or equal) to the inception date
		$card_expiry = $this->ui->getWidget('credit_card_expiry');
		$card_inception = $this->ui->getWidget('card_inception');
		if ($card_expiry->value !== null && $card_inception->value !== null &&
			Date::compare($card_expiry->value, $card_inception->value) < 0) {
			$card_expiry->addMessage(new SwatMessage(Store::_(
				'The card expiry date must be after the card inception date.'),
				SwatMessage::ERROR));
		}

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
					$order_payment_method = 
						$this->app->session->order->payment_method;
			} else {
				$class_map = StoreClassMap::instance();
				$class_name =
					$class_map->resolveClass('StoreOrderPaymentMethod');

				$order_payment_method = new $class_name();
			}

			$this->updatePaymentMethod($order_payment_method);
		} else {
			$method_id = intval($method_list->value);

			$account_payment_method = 
				$this->app->session->account->payment_methods->getByIndex(
					$method_id);

			if (!($account_payment_method instanceof StoreAccountPaymentMethod))
				throw new StoreException('Account payment method not found. '.
					"Method with id ‘{$method_id}’ not found.");

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
	// {{{ protected function updatePaymentMethod()

	/**
	 * Updates session order payment method properties from form values
	 *
	 * @param StoreOrderPaymentMethod $payment_method
	 */
	protected function updatePaymentMethod(
		StoreOrderPaymentMethod $payment_method)
	{
		$payment_method->payment_type =
			$this->ui->getWidget('payment_type')->value;

		$this->updatePaymentMethodCardNumber($payment_method);

		$payment_method->setCardVerificationValue(
			$this->ui->getWidget('card_verification_value')->value);

		$payment_method->card_issue_number =
			$this->ui->getWidget('card_issue_number')->value;

		$payment_method->credit_card_expiry =
			$this->ui->getWidget('credit_card_expiry')->value;

		$payment_method->card_inception =
			$this->ui->getWidget('card_inception')->value;

		$payment_method->credit_card_fullname =
			$this->ui->getWidget('credit_card_fullname')->value;
	}

	// }}}
	// {{{ protected function updatePaymentMethodCardNumber()

	/**
	 * Updates session order payment method card number from form values
	 *
	 * The card number is stored encrypted in the payment method. Subclasses
	 * can override this method to optionally store an unencrypted version
	 * of the card number.
	 *
	 * @param StoreOrderPaymentMethod $payment_method
	 */
	protected function updatePaymentMethodCardNumber(
		StoreOrderPaymentMethod $payment_method)
	{
		$card_number = $this->ui->getWidget('credit_card_number')->value;
		if ($card_number !== null)
			$payment_method->setCreditCardNumber($card_number);
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
			$path = 'packages/store/javascript/';
			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				$path.'store-checkout-page.js', Store::PACKAGE_ID));

			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				$path.'store-checkout-payment-method-page.js',
				Store::PACKAGE_ID));

			$this->layout->startCapture('content');
			Swat::displayInlineJavaScript($this->getInlineJavaScript());
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
				$this->ui->getWidget('credit_card_number')->show_blank_value =
					true;

				$this->ui->getWidget('card_verification_value')->value =
					$order->payment_method->getCardVerificationValue();

				$this->ui->getWidget('card_issue_number')->value =
					$order->payment_method->card_issue_number;

				$this->ui->getWidget('credit_card_expiry')->value =
					$order->payment_method->credit_card_expiry;

				$this->ui->getWidget('card_inception')->value =
					$order->payment_method->card_inception;

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
			sprintf('<span class="add-new">%s</span>',
			$this->getNewPaymentMethodText()), 'text/xml');

		foreach ($this->getPaymentMethods() as $method) {
			ob_start();
			$method->display();
			$method_display = ob_get_clean();
			$method_list->addOption($method->id, $method_display, 'text/xml');
		}

		if ($this->app->session->isLoggedIn()) {
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
		$payment_types = $this->getPaymentTypes();
		$payment_type_flydown = $this->ui->getWidget('payment_type');
		foreach ($payment_types as $payment_type)
			$payment_type_flydown->addOption(
				new SwatOption($payment_type->id, $payment_type->title));

		if (!$this->ui->getWidget('form')->isProcessed())
			$this->loadDataFromSession();

		if ($this->app->session->checkout_with_account) {
			$this->ui->getWidget('save_account_payment_method_field')->visible =
				true;

			$this->ui->getWidget('payment_method_note')->content = sprintf(
				Store::_('%sSee our %sprivacy &amp; security policy%s for '.
				'more information about how your information will be used.%s'),
				'<p class="small-print">', '<a href="about/website/privacy">',
				'</a>', '</p>');
		}
	}

	// }}}
	// {{{ protected function getPaymentMethods()

	/**
	 * Gets available payment methods
	 *
	 * @return StoreAccountPaymentMethodWrapper
	 */
	protected function getPaymentMethods()
	{
		$class_map = StoreClassMap::instance();
		$wrapper = $class_map->resolveClass('StoreAccountPaymentMethodWrapper');
		$payment_methods = new $wrapper();

		if ($this->app->session->isLoggedIn()) {
			$region = $this->app->getRegion();
			$account = $this->app->session->account;
			foreach ($account->payment_methods as $method) {
				$payment_type = $method->payment_type;
				if ($payment_type->isAvailableInRegion($region))
					$payment_methods->add($method);
			}
		}

		return $payment_methods;
	}

	// }}}
	// {{{ protected function getPaymentTypes()

	/**
	 * Gets available payment types for new payment methods
	 *
	 * @return StorePaymentTypeWrapper
	 */
	protected function getPaymentTypes()
	{
		$payment_types_sql = sprintf('select id, title from PaymentType
			inner join PaymentTypeRegionBinding on
				payment_type = id and region = %s
			where enabled = true order by displayorder, title',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$class_map = StoreClassMap::instance();
		$wrapper = $class_map->resolveClass('StorePaymentTypeWrapper');
		return SwatDB::query($this->app->db, $payment_types_sql, $wrapper);
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$id = 'checkout_payment_method';
		$inception_date_ids = array();
		$issue_number_ids = array();
		foreach ($this->getPaymentTypes() as $type) {
			if ($type->hasInceptionDate())
				$inception_date_ids[] = $type->id;

			if ($type->hasIssueNumber())
				$issue_number_ids[] = $type->id;
		}

		return sprintf("var %s_obj = ".
			"new StoreCheckoutPaymentMethodPage('%s', [%s], [%s]);",
			$id,
			$id,
			implode(', ', $inception_date_ids),
			implode(', ', $issue_number_ids));
	}

	// }}}
	// {{{ protected function getNewPaymentMethodText()

	protected function getNewPaymentMethodText()
	{
		return Store::_('Add a New Payment Method');
	}

	// }}}
}

?>
