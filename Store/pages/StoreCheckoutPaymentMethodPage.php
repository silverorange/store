<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/pages/StoreCheckoutEditPage.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/dataobjects/StoreOrderPaymentMethod.php';
require_once 'Store/dataobjects/StorePaymentTypeWrapper.php';
require_once 'Store/dataobjects/StoreCardTypeWrapper.php';

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

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);
		$this->ui_xml = 'Store/pages/checkout-payment-method.xml';
	}

	// }}}

	// process phase
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
		$method_list = $this->ui->getWidget('payment_method_list');
		$method_list->process();

		if ($method_list->value === null || $method_list->value === 'new') {
			$payment_type = $this->getPaymentType();

			if ($payment_type !== null) {
				if ($payment_type->isCard()) {

					// set debit card fields as required when a debit card is used
					$card_type = $this->getCardType();
					if ($card_type !== null) {
						$this->ui->getWidget('card_inception')->required =
							$card_type->hasInceptionDate();

						$this->ui->getWidget('card_issue_number')->required =
							$card_type->hasIssueNumber();
					}
				} else {
					$this->ui->getWidget('card_type')->required = false;
					$this->ui->getWidget('card_number')->required = false;
					$this->ui->getWidget('card_expiry')->required = false;
					$this->ui->getWidget('card_verification_value')->required = false;
					$this->ui->getWidget('card_fullname')->required = false;
					$this->ui->getWidget('card_inception')->required = false;
					$this->ui->getWidget('card_issue_number')->required = false;
				}
			}
		} else {
			// set all fields as not required when an existing payment method
			// is selected
			$container = $this->ui->getWidget('payment_method_form');
			$controls = $container->getDescendants('SwatInputControl');
			foreach ($controls as $control)
				$control->required = false;
		}
	}

	// }}}
	// {{{ public function validateCommon()

	public function validateCommon()
	{
		// make sure expiry date is after (or equal) to the inception date
		$card_expiry = $this->ui->getWidget('card_expiry');
		$card_inception = $this->ui->getWidget('card_inception');
		if ($card_expiry->value !== null && $card_inception->value !== null &&
			Date::compare($card_expiry->value, $card_inception->value) < 0) {
			$card_expiry->addMessage(new SwatMessage(Store::_(
				'The card expiry date must be after the card inception date.'),
				SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
		$payment_type = $this->getPaymentType();
		if ($payment_type !== null && $payment_type->isCard()) {
			$card_type_list = $this->ui->getWidget('card_type');
			// determine card type automatically if type flydown is hidden
			if (!$card_type_list->visible)
				$this->processCardType();
		}

		$this->saveDataToSession();
	}

	// }}}
	// {{{ protected function processCardType()

	protected function processCardType()
	{
		$card_number = $this->ui->getWidget('card_number');
		if ($card_number->show_blank_value)
			return;

		$card_type = $this->ui->getWidget('card_type');
		$message = null;

		$info = StoreCardType::getInfoFromCardNumber($card_number->value);

		if ($info !== null) {
			$class_name = SwatDBClassMap::get('StoreCardType');
			$type = new $class_name();
			$type->setDatabase($this->app->db);
			$found = $type->loadFromShortname($info->shortname);

			if ($found)
				$card_type->value = $type->id;
			else
				$message = sprintf('Sorry, we don’t accept %s payments.',
					SwatString::minimizeEntities($info->description));
		} else {
			$message = 'Sorry, we don’t accept your card type.';
		}

		if ($message !== null) {
			$message = new SwatMessage(sprintf('%s %s', $message,
				$this->getAcceptedCardTypesMessage()), SwatMessage::ERROR);

			$card_number->addMessage($message);
		}
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
				$class_name =
					SwatDBClassMap::get('StoreOrderPaymentMethod');

				$order_payment_method = new $class_name();
				$order_payment_method->setDatabase($this->app->db);
			}

			$this->updatePaymentMethod($order_payment_method);

			if ($order_payment_method->payment_type === null) {
				$order_payment_method = null;
			} else {
				if ($order_payment_method->payment_type->isCard() &&
					$order_payment_method->card_type === null)
						throw new StoreException('Order payment method must '.
							'a card_type when isCard() is true.');
			}
		} else {
			$method_id = intval($method_list->value);

			$account_payment_method =
				$this->app->session->account->payment_methods->getByIndex(
					$method_id);

			if (!($account_payment_method instanceof StoreAccountPaymentMethod))
				throw new StoreException('Account payment method not found. '.
					"Method with id ‘{$method_id}’ not found.");

			$class_name = SwatDBClassMap::get('StoreOrderPaymentMethod');
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

		$payment_method->surcharge =
			$payment_method->payment_type->surcharge;

		$this->updatePaymentMethodCardNumber($payment_method);

		$payment_method->card_type =
			$this->ui->getWidget('card_type')->value;

		$payment_method->setCardVerificationValue(
			$this->ui->getWidget('card_verification_value')->value);

		$payment_method->card_issue_number =
			$this->ui->getWidget('card_issue_number')->value;

		$payment_method->card_expiry =
			$this->ui->getWidget('card_expiry')->value;

		$payment_method->card_inception =
			$this->ui->getWidget('card_inception')->value;

		$payment_method->card_fullname =
			$this->ui->getWidget('card_fullname')->value;
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
		$card_number = $this->ui->getWidget('card_number')->value;
		if ($card_number !== null)
			$payment_method->setCardNumber($card_number);
	}

	// }}}
	// {{{ protected function getAcceptedCardTypesMessage()

	protected function getAcceptedCardTypesMessage()
	{
		$types = SwatDB::getOptionArray($this->app->db,
			'CardType', 'title', 'shortname', 'title');

		if (count($types) > 2) {
			array_push($types, sprintf('and %s',
				array_pop($types)));

			$type_list = implode(', ', $types);
		} else {
			$type_list = implode(' and ', $types);
		}

		return sprintf('We accept %s.', $type_list);
	}

	// }}}
	// {{{ protected function getPaymentType()

	protected function getPaymentType()
	{
		static $type = null;

		if ($type === null) {
			$type_list = $this->ui->getWidget('payment_type');
			$type_list->process();
			$class_name = SwatDBClassMap::get('StorePaymentType');
			$type = new $class_name();
			$type->setDatabase($this->app->db);
			$type->load($type_list->value);
		}

		return $type;
	}

	// }}}
	// {{{ protected function getCardType()

	protected function getCardType()
	{
		static $type = null;

		if ($type === null) {
			$type_list = $this->ui->getWidget('card_type');
			$type_list->process();
			$class_name = SwatDBClassMap::get('StoreCardType');
			$type = new $class_name();
			$type->setDatabase($this->app->db);
			$type->load($type_list->value);
		}

		return $type;
	}

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		$this->buildList();
		$this->buildForm();
	}

	// }}}
	// {{{ public function postBuildCommon()

	public function postBuildCommon()
	{
		$this->layout->startCapture('content');
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		/*
		 * Set page to two-column layout when page is stand-alone even when
		 * there is no address list. The narrower layout of the form fields
		 * looks better even withour a select list on the left.
		 */
		$this->ui->getWidget('form')->classes[] = 'checkout-no-column';
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;

		if ($order->payment_method === null) {
			$this->ui->getWidget('card_fullname')->value =
				$this->app->session->account->fullname;
		} else {
			if ($order->payment_method->getAccountPaymentMethodId() === null) {

				$this->ui->getWidget('payment_type')->value =
					$order->payment_method->getInternalValue('payment_type');

				$this->ui->getWidget('card_type')->value =
					$order->payment_method->getInternalValue('card_type');

				/*
				 *  Note: We can't repopulate the card number entry since we
				 *        only store the encrypted number in the dataobject.
				 */
				if ($order->payment_method->card_number !== null)
					$this->ui->getWidget('card_number')->show_blank_value = true;

				$this->ui->getWidget('card_verification_value')->value =
					$order->payment_method->getCardVerificationValue();

				$this->ui->getWidget('card_issue_number')->value =
					$order->payment_method->card_issue_number;

				$this->ui->getWidget('card_expiry')->value =
					$order->payment_method->card_expiry;

				$this->ui->getWidget('card_inception')->value =
					$order->payment_method->card_inception;

				$this->ui->getWidget('card_fullname')->value =
					$order->payment_method->card_fullname;
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
		$this->buildPaymentTypes();
		$this->buildCardTypes();

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
		$wrapper = SwatDBClassMap::get('StoreAccountPaymentMethodWrapper');
		$payment_methods = new $wrapper();

		if ($this->app->session->isLoggedIn()) {
			$region = $this->app->getRegion();
			$account = $this->app->session->account;
			foreach ($account->payment_methods as $method) {
				$payment_type = $method->payment_type;
				$card_type = $method->card_type;
				if ($payment_type->isAvailableInRegion($region) &&
					($card_type === null || $card_type->isAvailableInRegion($region)))
						$payment_methods->add($method);
			}
		}

		return $payment_methods;
	}

	// }}}
	// {{{ protected function buildPaymentTypes()

	protected function buildPaymentTypes()
	{
		$types = $this->getPaymentTypes();
		$type_flydown = $this->ui->getWidget('payment_type');

		foreach ($types as $type) {
			$title = $this->getPaymentTypeTitle($type);

			$type_flydown->addOption(
				new SwatOption($type->id, $title, 'text/xml'));
		}
	}

	// }}}
	// {{{ protected function getPaymentTypeTitle()

	protected function getPaymentTypeTitle(StorePaymentType $type)
	{
		$title = $type->title;

		if (strlen($type->note) > 0) {
			$title.= sprintf('<br /><span class="swat-note">%s</span>',
				$type->note);
		}

		return $title;
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
		$sql = 'select PaymentType.* from PaymentType
			inner join PaymentTypeRegionBinding on
				payment_type = id and region = %s
			order by displayorder, title';

		$sql = sprintf($sql,
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$wrapper = SwatDBClassMap::get('StorePaymentTypeWrapper');
		$types = SwatDB::query($this->app->db, $sql, $wrapper);

		return $types;
	}

	// }}}
	// {{{ protected function buildCardTypes()

	protected function buildCardTypes()
	{
		$types = $this->getCardTypes();
		$type_flydown = $this->ui->getWidget('card_type');

		foreach ($types as $type) {
			$title = $this->getCardTypeTitle($type);

			$type_flydown->addOption(
				new SwatOption($type->id, $title, 'text/xml'));
		}
	}

	// }}}
	// {{{ protected function getCardTypeTitle()

	protected function getCardTypeTitle(StoreCardType $type)
	{
		$title = $type->title;

		return $title;
	}

	// }}}
	// {{{ protected function getCardTypes()

	/**
	 * Gets available card types for new payment methods
	 *
	 * @return StoreCardTypeWrapper
	 */
	protected function getCardTypes()
	{
		$sql = 'select CardType.* from CardType
			inner join CardTypeRegionBinding on
				card_type = id and region = %s
			order by displayorder, title';

		$sql = sprintf($sql,
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$wrapper = SwatDBClassMap::get('StoreCardTypeWrapper');
		$types = SwatDB::query($this->app->db, $sql, $wrapper);

		return $types;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$id = 'checkout_payment_method';
		$inception_date_ids = array();
		$issue_number_ids = array();
		foreach ($this->getCardTypes() as $type) {
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

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-payment-method-page.css',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$path = 'packages/store/javascript/';
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			$path.'store-checkout-page.js', Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			$path.'store-checkout-payment-method-page.js',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
