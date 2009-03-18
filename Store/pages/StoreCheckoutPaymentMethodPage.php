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
 * @copyright 2005-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutPaymentMethodPage extends StoreCheckoutEditPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-payment-method.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();
		$types = $this->getPaymentTypes();
		$this->initPaymentTypes($types);
	}

	// }}}
	// {{{ protected function initPaymentTypes()

	protected function initPaymentTypes(StorePaymentTypeWrapper $types)
	{
		$type_flydown = $this->ui->getWidget('payment_type');

		// payment types will determine whether or not the flydown is visible
		$type_flydown->parent->visible = false;

		// init payment types
		foreach ($types as $type) {
			$this->initPaymentType($type, $type_flydown);
		}

		// If no value was set, default to first payment type
		if ($type_flydown->value === null) {
			$type_flydown->value = $types->getFirst()->id;
		}
	}

	// }}}
	// {{{ protected function initPaymentType()

	protected function initPaymentType(StorePaymentType $type,
		SwatFlydown $flydown)
	{
		switch ($type->shortname) {
		case 'card':
			$this->initPaymentTypeCard($type, $flydown);
			break;

		default:
			$this->initPaymentTypeDefault($type, $flydown);
			break;
		}
	}

	// }}}
	// {{{ protected function initPaymentTypeDefault()

	protected function initPaymentTypeDefault(StorePaymentType $type,
		SwatFlydown $flydown)
	{
		$title = $this->getPaymentTypeTitle($type);
		$flydown->addOption(new SwatOption($type->id, $title, 'text/xml'));
		$flydown->parent->visible = true;
	}

	// }}}
	// {{{ protected function initPaymentTypeCard()

	protected function initPaymentTypeCard(StorePaymentType $type,
		SwatFlydown $flydown)
	{
		$title = $this->getPaymentTypeTitle($type);
		$flydown->addOption(new SwatOption($type->id, $title, 'text/xml'));

		// default to 'card' if it exists
		if ($flydown->value === null) {
			$flydown->value = $type->id;
		}

		// set up card types flydown
		$types = $this->getCardTypes();

		$type_flydown = $this->ui->getWidget('card_type');

		foreach ($types as $type) {
			$title = $this->getCardTypeTitle($type);

			$type_flydown->addOption(
				new SwatOption($type->id, $title, 'text/xml'));
		}

		if ($type_flydown->value === null) {
			$type_flydown->value = $types->getFirst()->id;
		}

		// make card fields visible
		$this->ui->getWidget('card_container')->visible = true;
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
		static $types = null;

		if ($types === null) {
			$sql = 'select PaymentType.* from PaymentType
				inner join PaymentTypeRegionBinding on
					payment_type = id and region = %s
				order by displayorder, title';

			$sql = sprintf($sql,
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

			$wrapper = SwatDBClassMap::get('StorePaymentTypeWrapper');
			$types = SwatDB::query($this->app->db, $sql, $wrapper);
		}

		return $types;
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
		static $types = null;

		if ($types === null) {
			$sql = 'select CardType.* from CardType
				inner join CardTypeRegionBinding on
					card_type = id and region = %s
				order by displayorder, title';

			$sql = sprintf($sql,
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

			$wrapper = SwatDBClassMap::get('StoreCardTypeWrapper');
			$types = SwatDB::query($this->app->db, $sql, $wrapper);
		}

		return $types;
	}

	// }}}

	// process phase
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
		$this->ui->getWidget('card_number')->setDatabase($this->app->db);

		$method_list = $this->ui->getWidget('payment_method_list');
		$method_list->process();
		$method_id = $method_list->value;

		if ($method_id === null || $method_id === 'new') {
			// the account card verification value only needs to be required for
			// saved cards
			$this->ui->getWidget('account_card_verification_value')->required =
				false;

			$payment_type = $this->getPaymentType();

			if ($payment_type !== null) {
				if ($payment_type->isCard()) {
					$widget = $this->ui->getWidget('card_verification_value');
					$this->setupCardVerificationValue($widget);

					// set debit card fields as required when a debit card is
					// used
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
					$this->ui->getWidget('card_fullname')->required = false;
					$this->ui->getWidget('card_inception')->required = false;
					$this->ui->getWidget('card_issue_number')->required = false;
					$this->ui->getWidget('card_verification_value')->required =
						false;
				}
			}
		} else {
			// set all fields as not required when an existing payment method
			// is selected
			$container = $this->ui->getWidget('payment_method_form');
			$controls = $container->getDescendants('SwatInputControl');
			foreach ($controls as $control)
				$control->required = false;

			$widget = $this->ui->getWidget('account_card_verification_value');
			$this->setupCardVerificationValue($widget);
		}
	}

	// }}}
	// {{{ protected function setupCardVerificationValue()

	protected function setupCardVerificationValue(
		StoreCardVerificationValueEntry $card_verification_value_widget)
	{
		$card_type = $this->getCardType();

		if ($card_type != null) {
			$card_verification_value_widget->setCardType($card_type);
			$card_verification_value_widget->process();
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
				'error'));
		}
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
		$this->saveDataToSession();
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$method_list = $this->ui->getWidget('payment_method_list');
		$order_payment_method =
			$this->app->session->order->payment_methods->getFirst();

		if ($method_list->value === null || $method_list->value === 'new') {
			if ($order_payment_method === null ||
				$order_payment_method->getAccountPaymentMethodId() !== null) {

				$class_name = SwatDBClassMap::get('StoreOrderPaymentMethod');
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
							'be a card_type when isCard() is true.');
			}

			$save_payment_method =
				$this->ui->getWidget('save_account_payment_method')->value;
		} else {
			$method_id = intval($method_list->value);

			$account_payment_method =
				$this->app->session->account->payment_methods->getByIndex(
					$method_id);

			if (!($account_payment_method instanceof StoreAccountPaymentMethod))
				throw new StoreException('Account payment method not found. '.
					"Method with id ‘{$method_id}’ not found.");

			$old_card_verification_value =
				$order_payment_method->card_verification_value;

			$class_name = SwatDBClassMap::get('StoreOrderPaymentMethod');
			$order_payment_method = new $class_name();
			$order_payment_method->copyFrom($account_payment_method);

			$this->updatePaymentMethodCardVerificationValue(
				'account_card_verification_value',
				$order_payment_method,
				$old_card_verification_value);

			// if its a saved method, we want the confirmation page to use the
			// save code-path so that default payment method gets set
			$save_payment_method = true;
		}

		$class_name = SwatDBClassMap::get('StoreOrderPaymentMethodWrapper');
		$this->app->session->order->payment_methods = new $class_name();

		if ($order_payment_method !== null) {
			$this->app->session->order->payment_methods->add(
				$order_payment_method);
		}

		if ($this->app->session->checkout_with_account) {
			$this->app->session->save_account_payment_method =
				$save_payment_method;
		}
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
		$payment_type = $this->getPaymentType();

		$payment_method->payment_type = $payment_type;
		$payment_method->surcharge = $payment_type->surcharge;

		if ($payment_type->isCard()) {
			$this->updatePaymentMethodCardNumber($payment_method);

			$this->updatePaymentMethodCardVerificationValue(
				'card_verification_value', $payment_method);

			$payment_method->card_issue_number =
				$this->ui->getWidget('card_issue_number')->value;

			$payment_method->card_expiry =
				$this->ui->getWidget('card_expiry')->value;

			$payment_method->card_inception =
				$this->ui->getWidget('card_inception')->value;

			$payment_method->card_fullname =
				$this->ui->getWidget('card_fullname')->value;
		} else {
			$payment_method->card_fullname = null;
			$payment_method->card_inception = null;
			$payment_method->card_expiry = null;
			$payment_method->card_issue_number = null;
			$payment_method->card_type = null;
			$payment_method->card_number = null;
			$payment_method->card_number_preview = null;
		}
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
		if ($card_number !== null) {
			$payment_method->setCardNumber($card_number);
			$payment_method->card_type = $this->getCardType();
		}
	}

	// }}}
	// {{{ protected function updatePaymentMethodCardVerificationValue()

	/**
	 * Updates session order payment method card verification value
	 *
	 * The card verification value is stored unencrypted in the payment method.
	 * Subclasses can override this method to optionally store an encrypted
	 * version of the card verification value.
	 *
	 * @param string $entry_widget_name
	 * @param StoreOrderPaymentMethod $payment_method
	 * @param string $old_payment_method
	 */
	protected function updatePaymentMethodCardVerificationValue(
		$entry_widget_name, StoreOrderPaymentMethod $payment_method,
		$old_card_verification_value = null)
	{
		$value = $this->ui->getWidget($entry_widget_name)->value;
		if ($value !== null) {
			$payment_method->setCardVerificationValue($value);
		} elseif ($old_card_verification_value !== null) {
			$payment_method->card_verification_value =
				$old_card_verification_value;
		}
	}

	// }}}
	// {{{ protected function getPaymentType()

	protected function getPaymentType()
	{
		// note: this only works for a single instance
		static $type = null;

		if ($type === null) {
			$type_list = $this->ui->getWidget('payment_type');
			if (isset($_POST['payment_type'])) {
				$type_list->process();
				$class_name = SwatDBClassMap::get('StorePaymentType');
				$type = new $class_name();
				$type->setDatabase($this->app->db);
				$type->load($type_list->value);
			} else {
				$type = $this->getPaymentTypes()->getFirst();
			}
		}

		return $type;
	}

	// }}}
	// {{{ protected function getCardType()

	protected function getCardType()
	{
		static $card_type = null;

		if ($card_type === null) {
			$method_list = $this->ui->getWidget('payment_method_list');
			$method_list->process();

			if ($method_list->value === null || $method_list->value === 'new') {
				$order = $this->app->session->order;
				$order_payment_method = $order->payment_methods->getFirst();
				$type_list = $this->ui->getWidget('card_type');

				if (isset($_POST['card_type'])) {
					$type_list->process();
					$card_type_id = $type_list->value;
				} else {
					$card_number = $this->ui->getWidget('card_number');
					$card_number->process();

					if ($card_number->value == null &&
						$order_payment_method != null) {
						$card_type_id =
							$order_payment_method->getInternalValue('card_type');
					} else {
						$card_type_id = $card_number->getCardType();
					}
				}

				$class_name = SwatDBClassMap::get('StoreCardType');
				$type = new $class_name();
				$type->setDatabase($this->app->db);
				if ($type->load($card_type_id))
					$card_type = $type;
			} else {
				$method_id = intval($method_list->value);

				$account_payment_method =
					$this->app->session->account->payment_methods->getByIndex(
						$method_id);

				$card_type = $account_payment_method->card_type;
			}
		}

		return $card_type;
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

		$method_list->visible = (count($method_list->options) > 1);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$types = $this->getPaymentTypes();
//		$this->buildPaymentTypes($types);

		if (count($types) === 1) {
			$types_flydown = $this->ui->getWidget('payment_type');
			$parent = $types_flydown->parent->getFirstAncestor(
				'SwatDisplayableContainer');

			$parent->classes[] = 'store-payment-method-single';
		}


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

		$card_ids = array();
		foreach ($this->getPaymentTypes() as $type) {
			if ($type->isCard()) {
				$card_ids[] = $type->id;
			}
		}

		return sprintf("var %s_obj = ".
			"new StoreCheckoutPaymentMethodPage('%s', [%s], [%s], [%s]);",
			$id,
			$id,
			implode(', ', $inception_date_ids),
			implode(', ', $issue_number_ids),
			implode(', ', $card_ids));
	}

	// }}}
	// {{{ protected function getNewPaymentMethodText()

	protected function getNewPaymentMethodText()
	{
		return Store::_('Add a New Payment Method');
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;
		$order_payment_method = $order->payment_methods->getFirst();

		if ($order_payment_method === null) {
			$this->ui->getWidget('card_fullname')->value =
				$this->app->session->account->fullname;

			$default_payment_method = $this->getDefaultPaymentMethod();
			if ($default_payment_method !== null) {
				$this->ui->getWidget('payment_method_list')->value =
					$default_payment_method->id;
			}
		} else {
			if ($order_payment_method->getAccountPaymentMethodId() === null) {
				$this->ui->getWidget('payment_type')->value =
					$order_payment_method->getInternalValue('payment_type');

				$this->ui->getWidget('card_type')->value =
					$order_payment_method->getInternalValue('card_type');

				/*
				 *  Note: We can't repopulate the card number entry since we
				 *        only store the encrypted number in the dataobject.
				 */
				if ($order_payment_method->hasCardNumber()) {
					$this->ui->getWidget('card_number')->show_blank_value =
						true;
				}

				if ($order_payment_method->hasCardVerificationValue()) {
					$cvv = $this->ui->getWidget('card_verification_value');
					$card_type = $this->getCardType();
					if ($card_type !== null) {
						$cvv->setCardType($card_type);
						$cvv->show_blank_value = true;
					}
				}

				$this->ui->getWidget('card_issue_number')->value =
					$order_payment_method->card_issue_number;

				$this->ui->getWidget('card_expiry')->value =
					$order_payment_method->card_expiry;

				$this->ui->getWidget('card_inception')->value =
					$order_payment_method->card_inception;

				$this->ui->getWidget('card_fullname')->value =
					$order_payment_method->card_fullname;
			} else {
				$this->ui->getWidget('payment_method_list')->value =
					$order_payment_method->getAccountPaymentMethodId();

				if ($order_payment_method->hasCardVerificationValue()) {
					$cvv =
						$this->ui->getWidget('account_card_verification_value');

					$card_type = $this->getCardType();
					if ($card_type !== null) {
						$cvv->setCardType($card_type);
						$cvv->show_blank_value = true;
					}
				}
			}
		}

		if ($this->app->session->checkout_with_account &&
			isset($this->app->session->save_account_payment_method)) {
			$this->ui->getWidget('save_account_payment_method')->value =
				$this->app->session->save_account_payment_method;
		}
	}

	// }}}
	// {{{ protected function getDefaultPaymentMethod()

	protected function getDefaultPaymentMethod()
	{
		$payment_method = null;

		if ($this->app->session->isLoggedIn()) {
			$default_payment_method =
				$this->app->session->account->getDefaultPaymentMethod();

			if ($default_payment_method !== null) {
				// only default to a payment method that appears in the list
				$payment_method_list =
					$this->ui->getWidget('payment_method_list');

				$options = $payment_method_list->getOptionsByValue(
					$default_payment_method->id);

				if (count($options) > 0)
					$payment_method = $default_payment_method;
			}
		}

		return $payment_method;
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
