<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/pages/StoreCheckoutPage.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';
require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/exceptions/StorePaymentAddressException.php';
require_once 'Store/exceptions/StorePaymentPostalCodeException.php';
require_once 'Store/exceptions/StorePaymentCvvException.php';
require_once 'Store/exceptions/StorePaymentTotalException.php';

/**
 * Confirmation page of checkout
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutConfirmationPage extends StoreCheckoutPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-confirmation.xml';
	}

	// }}}

	// init phase
	// {{{ protected function init()

	protected function initInternal()
	{
		parent::initInternal();

		$this->checkOrder();

		if ($this->ui->hasWidget('checkout_progress')) {
			$checkout_progress = $this->ui->getWidget('checkout_progress');
			$checkout_progress->current_step = 2;
		}
	}

	// }}}
	// {{{ protected function checkOrder()

	protected function checkOrder()
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
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$form = $this->ui->getWidget('form');

		if ($this->validate() && $form->isProcessed() && !$form->hasMessage())
			$this->processOrder();
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		$valid = true;
		$valid = $this->validateBillingAddress() && $valid;
		$valid = $this->validateShippingAddress() && $valid;
		$valid = $this->validatePaymentMethod() && $valid;

		return $valid;
	}

	// }}}
	// {{{ protected function validateBillingAddress()

	protected function validateBillingAddress()
	{
		$valid = true;

		$address = $this->app->session->order->billing_address;
		$valid = $this->validateBillingAddressCountry($address) && $valid;
		$valid = $this->validateBillingAddressProvState($address) && $valid;

		return $valid;
	}

	// }}}
	// {{{ protected function validateShippingAddress()

	protected function validateShippingAddress()
	{
		$valid = true;

		$address = $this->app->session->order->shipping_address;
		$valid = $this->validateShippingAddressCountry($address) && $valid;
		$valid = $this->validateShippingAddressProvState($address) && $valid;

		return $valid;
	}

	// }}}
	// {{{ protected function validateBillingAddressCountry()

	protected function validateBillingAddressCountry(StoreOrderAddress $address)
	{
		$valid = true;
		$country_ids = array();
		foreach ($this->app->getRegion()->billing_countries as $country)
			$country_ids[] = $country->id;

		if (!in_array($address->getInternalValue('country'), $country_ids)) {
			$valid = false;
			$message = new SwatMessage('Billing Address', 'error');

			$message->secondary_content = sprintf(
				'Orders can not be billed to %s. Please select a different '.
				'billing address or enter a new billing address.',
				$address->country->title);

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateBillingAddressProvState()

	protected function validateBillingAddressProvState(
		StoreOrderAddress $address)
	{
		$valid = true;
		$billing_provstate = $address->getInternalValue('provstate');

		/* If provstate is null, it means it's either not required, or
		 * provstate_other is set. In either case, we don't need to check
		 * against valid provstates.
		 */
		if ($billing_provstate === null)
			return true;

		$provstate_ids = array();
		foreach ($this->app->getRegion()->billing_provstates as $provstate)
			$provstate_ids[] = $provstate->id;

		if (!in_array($billing_provstate, $provstate_ids)) {
			$valid = false;
			$message = new SwatMessage('Billing Address', 'error');
			$message->secondary_content = sprintf(
				'Orders can not be billed to %s. Please select a different '.
				'billing address or enter a new billing address.',
				$address->provstate->title);

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateShippingAddressCountry()

	protected function validateShippingAddressCountry(
		StoreOrderAddress $address)
	{
		$valid = true;
		$country_ids = array();
		foreach ($this->app->getRegion()->shipping_countries as $country)
			$country_ids[] = $country->id;

		if (!in_array($address->getInternalValue('country'), $country_ids)) {
			$valid = false;
			$message = new SwatMessage('Shipping Address', 'error');
			$message->secondary_content = sprintf(
				'Orders can not be shipped to %s. Please select a different '.
				'shipping address or enter a new shipping address.',
				$address->country->title);

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateShippingAddressProvState()

	protected function validateShippingAddressProvState(
		StoreOrderAddress $address)
	{
		$valid = true;
		$shipping_provstate = $address->getInternalValue('provstate');

		/* If provstate is null, it means it's either not required, or
		 * provstate_other is set. In either case, we don't need to check
		 * against valid provstates.
		 */
		if ($shipping_provstate === null)
			return true;

		$provstate_ids = array();
		foreach ($this->app->getRegion()->shipping_provstates as $provstate)
			$provstate_ids[] = $provstate->id;

		if (!in_array($shipping_provstate, $provstate_ids)) {
			$valid = false;
			$message = new SwatMessage('Shipping Address', 'error');
			$message->secondary_content = sprintf(
				'Orders can not be shipped to %s. Please select a different '.
				'shipping address or enter a new shipping address.',
				$address->provstate->title);

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validatePaymentMethod()

	protected function validatePaymentMethod($show_message = false)
	{
		$valid = true;
		$payment_total = 0;
		$order = $this->app->session->order;

		if ($this->app->config->store->multiple_payment_support) {
			foreach ($order->payment_methods as $payment_method)
				$payment_total+= $payment_method->amount;

			$valid = ($payment_total >= $order_total);
		} elseif (count($order->payment_methods) == 0) {
			$valid = false;
		}

		if (!$valid && $show_message) {
			$message = $this->getPaymentMethodValidationMessage();

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function getPaymentMethodValidationMessage()

	protected function getPaymentMethodValidationMessage()
	{
		$message = new SwatMessage('Payment', 'error');
		$message->secondary_content =
			'The payments on this order do not cover the order total. '.
			'Please edit an existing payment or add another '.
			'payment method.';

		return $message;
	}

	// }}}
	// {{{ protected function processOrder()

	protected function processOrder()
	{
		$saved = $this->save();

		if (!$saved)
			return;

		$order = $this->app->session->order;
		$order->sendConfirmationEmail($this->app);

		$this->removeCartEntries();
		$this->cleanupSession();
		$this->updateProgress();

		$this->app->relocate('checkout/thankyou');
	}

	// }}}
	// {{{ protected function processPayment()

	/**
	 * Does automatic card payment processing for an order
	 *
	 * By default, no automatic payment processing is done. Subclasses should
	 * override this method to perform automatic payment processing.
	 *
	 * @see StorePaymentProvider
	 */
	protected function processPayment()
	{
	}

	// }}}
	// {{{ protected function save()

	protected function save()
	{
		if ($this->app->session->checkout_with_account) {
			$db_transaction = new SwatDBTransaction($this->app->db);
			$duplicate_account = $this->app->session->account->duplicate();
			try {
				$this->saveAccount();
				$db_transaction->commit();
			} catch (Exception $e) {
				$db_transaction->rollback();
				$this->app->session->account = $duplicate_account;

				if (!($e instanceof SwatException))
					$e = new SwatException($e);

				$e->process();

				$message = $this->getErrorMessage('account-error');
				$this->ui->getWidget('message_display')->add($message);

				return false;
			}
		}

		$db_transaction = new SwatDBTransaction($this->app->db);
		$duplicate_order = $this->app->session->order->duplicate();

		try {
			$this->saveOrder();
			$this->processPayment();
			$db_transaction->commit();
		} catch (Exception $e) {
			$db_transaction->rollback();
			$this->app->session->order = $duplicate_order;

			if ($this->handleException($e)) {
				// log the exception
				if (!($e instanceof SwatException)) {
					$e = new SwatException($e);
				}
				$e->process(false);
			} else {
				// exception was not handled, rethrow
				throw $e;
			}

			return false;
		}

		return true;
	}

	// }}}
	// {{{ protected function saveAccount()

	protected function saveAccount()
	{
		// if we are checking out with an account, store new addresses and
		// payment methods in the account
		$account = $this->app->session->account;
		$order = $this->app->session->order;

		$address = $this->addAddressToAccount($order->billing_address);
		$account->setDefaultBillingAddress($address);

		// shipping address is only added if it differs from billing address
		if ($order->shipping_address !== $order->billing_address &&
			$order->shipping_address->getAccountAddressId() === null) {
			$address = $this->addAddressToAccount($order->shipping_address);
		}

		$account->setDefaultShippingAddress($address);

		// new payment methods are only added if a session flag is set and true
		if (isset($this->app->session->save_account_payment_method) &&
			$this->app->session->save_account_payment_method) {

			foreach ($order->payment_methods as $payment_method) {
				if ($payment_method->isSaveableWithAccount()) {
					$payment_method =
						$this->addPaymentMethodToAccount($payment_method);

					$account->setDefaultPaymentMethod($payment_method);
				}
			}
		}

		$new_account = ($account->id === null);

		// if this is a new account, set createdate to now
		if ($new_account) {
			$account->createdate = new SwatDate();
			$account->createdate->toUTC();

			if ($this->app->hasModule('SiteMultipleInstanceModule'))
				$account->instance = $this->app->instance->getInstance();
		}

		// save account
		$account->save();

		// if this is a new account, log it in
		if ($new_account) {
			// clear account from session so we appear to not be logged in now
			// that the account is saved
			$this->app->session->account = null;
			$this->app->session->loginById($account->id);
		}
	}

	// }}}
	// {{{ protected function saveOrder()

	protected function saveOrder()
	{
		$order = $this->app->session->order;

		if ($this->app->hasModule('SiteMultipleInstanceModule'))
			$order->instance = $this->app->instance->getInstance();

		// attach order to account
		if ($this->app->session->checkout_with_account)
			$order->account = $this->app->session->account;

		// set createdate to now
		$order->createdate = new SwatDate();
		$order->createdate->toUTC();

		// save order
		$order->save();

		return true;
	}

	// }}}
	// {{{ protected function addAddressToAccount()

	/**
	 * @return StoreAccountAddress the account address used for this order.
	 */
	protected function addAddressToAccount(StoreOrderAddress $order_address)
	{
		$account = $this->app->session->account;

		// check that address is not already in account
		if ($order_address->getAccountAddressId() === null) {
			$class_name = SwatDBClassMap::get('StoreAccountAddress');
			$account_address = new $class_name();
			$account_address->copyFrom($order_address);
			$account_address->createdate = new SwatDate();
			$account_address->createdate->toUTC();
			$account->addresses->add($account_address);
		} else {
			$account_address = $account->addresses->getByIndex(
				$order_address->getAccountAddressId());
		}

		return $account_address;
	}

	// }}}
	// {{{ protected function addPaymentMethodToAccount()

	protected function addPaymentMethodToAccount(
		StoreOrderPaymentMethod $order_payment_method)
	{
		$account = $this->app->session->account;

		// check that payment method is not already in account
		if ($order_payment_method->getAccountPaymentMethodId() === null) {
			$class_name = SwatDBClassMap::get('StoreAccountPaymentMethod');
			$account_payment_method = new $class_name();
			$account_payment_method->copyFrom($order_payment_method);
			$account->payment_methods->add($account_payment_method);
		} else {
			$account_payment_method = $account->payment_methods->getByIndex(
				$order_payment_method->getAccountPaymentMethodId());
		}

		return $account_payment_method;
	}

	// }}}
	// {{{ protected function removeCartEntries()

	protected function removeCartEntries()
	{
		$order = $this->app->session->order;

		// remove entries from cart that were ordered
		foreach ($order->items as $order_item) {
			$entry_id = $order_item->getCartEntryId();
			$this->app->cart->checkout->removeEntryById($entry_id);
		}

		$this->app->cart->save();
	}

	// }}}
	// {{{ protected function cleanupSession()

	protected function cleanupSession()
	{
		// unset session variable flags
		$this->app->analytics->clearAd();
		unset($this->app->session->save_account_payment_method);
	}

	// }}}
	// {{{ protected function handleException()

	/**
	 * Handles exceptions produced by order processing
	 *
	 * @param Exception $e
	 *
	 * @return boolean true if the exception was handled and false if it was
	 *                 not. Unhandled excepions are rethrown.
	 *
	 * @see StorePaymentProvider
	 */
	protected function handleException(Exception $e)
	{
		if ($e instanceof StorePaymentAddressException) {
			$message = $this->getErrorMessage('address-mismatch');
		} elseif ($e instanceof StorePaymentPostalCodeException) {
			$message = $this->getErrorMessage('postal-code-mismatch');
		} elseif ($e instanceof StorePaymentCvvException) {
			$message = $this->getErrorMessage('card-verification-value');
		} elseif ($e instanceof StorePaymentCardTypeException) {
			$message = $this->getErrorMessage('card-type');
		} elseif ($e instanceof StorePaymentTotalException) {
			$message = $this->getErrorMessage('total');
		} elseif ($e instanceof StorePaymentException) {
			$message = $this->getErrorMessage('payment-error');
		} else {
			$message = $this->getErrorMessage('order-error');
		}

		$message_display = $this->ui->getWidget('message_display');
		$message_display->add($message);

		// exceptions are always handled
		return true;
	}

	// }}}
	// {{{ protected function getErrorMessage()

	/**
	 * Gets the error message for an error
	 *
	 * Message ids defined in this class are:
	 *
	 * <kbd>address-mismatch</kdb>        - for address AVS mismatch errors.
	 * <kbd>postal-code-mismatch</kbd>    - for postal/zip code AVS mismatch
	 *                                      errors.
	 * <kbd>card-verification-value</kbd> - for CVS, CV2 mismatch errors.
	 * <kbd>card-type</kbd>               - for invalid card types.
	 * <kbd>total</kbd>                   - for invalid order totals.
	 * <kbd>payment-error</kbd>           - for an unknown error processing
	 *                                      payment for orders.
	 * <kbd>order-error</kbd>             - for an unknown error saving orders.
	 * <kbd>account-error</kbd>           - for an unknown error saving
	 *                                      accounts.
	 *
	 * Subclasses may define additional error message ids.
	 *
	 * @param string $message_id the id of the message to get.
	 *
	 * @return SwatMessage the error message corresponding to the specified
	 *                      <kbd>$message_id</kbd> or null if no such message
	 *                      exists.
	 */
	protected function getErrorMessage($message_id)
	{
		$message = null;

		switch ($message_id) {
		case 'address-mismatch':
			$message = new SwatMessage(
				Store::_('There was a problem processing your payment.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content =
				'<p>'.sprintf(
				Store::_('%sBilling address does not correspond with card '.
					'number.%s Your order has %snot%s been placed. '.
					'Please edit your %sbilling address%s and try again.'),
					'<strong>', '</strong>', '<em>', '</em>',
					'<a href="checkout/confirmation/billingaddress">', '</a>').
				' '.Store::_('No funds have been removed from your card.').
				'</p><p>'.sprintf(
				Store::_('If you are still unable to complete your order '.
					'after confirming your payment information, please '.
					'%scontact us%s.'),
					'<a href="about/contact">', '</a>').
				'</p>';

			break;
		case 'postal-code-mismatch':
			$message = new SwatMessage(
				Store::_('There was a problem processing your payment.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content =
				'<p>'.sprintf(
				Store::_('%sBilling postal code / ZIP code does not correspond '.
					'with card number.%s Your order has %snot%s been placed. '.
					'Please edit your %sbilling address%s and try again.'),
					'<strong>', '</strong>', '<em>', '</em>',
					'<a href="checkout/confirmation/billingaddress">', '</a>').
				' '.Store::_('No funds have been removed from your card.').
				'</p><p>'.sprintf(
				Store::_('If you are still unable to complete your order '.
					'after confirming your payment information, please '.
					'%scontact us%s.'),
					'<a href="about/contact">', '</a>').
				'</p>';

			break;
		case 'card-verification-value':
			$message = new SwatMessage(
				Store::_('There was a problem processing your payment.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content =
				'<p>'.sprintf(
				Store::_('%sCard security code does not correspond with card '.
					'number.%s Your order has %snot%s been placed. '.
					'Please edit your %spayment information%s and try again.'),
					'<strong>', '</strong>', '<em>', '</em>',
					'<a href="checkout/confirmation/paymentmethod">', '</a>').
				' '.Store::_('No funds have been removed from your card.').
				'</p><p>'.sprintf(
				Store::_('If you are still unable to complete your order '.
					'after confirming your payment information, please '.
					'%scontact us%s.'),
					'<a href="about/contact">', '</a>').
				'</p>';

			break;
		case 'card-type':
			$message = new SwatMessage(
				Store::_('There was a problem processing your payment.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content =
				'<p>'.sprintf(
				Store::_('%sCard type does not correspond with card '.
					'number.%s Your order has %snot%s been placed. '.
					'Please edit your %spayment information%s and try again.'),
					'<strong>', '</strong>', '<em>', '</em>',
					'<a href="checkout/confirmation/paymentmethod">', '</a>').
				' '.Store::_('No funds have been removed from your card.').
				'</p><p>'.sprintf(
				Store::_('If you are still unable to complete your order '.
					'after confirming your payment information, please '.
					'%scontact us%s.'),
					'<a href="about/contact">', '</a>').
				'</p>';

			break;
		case 'total':
			$message = new SwatMessage(
				Store::_('There was a problem processing your payment.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content =
				'<p>'.sprintf(
				Store::_('%sYour order total is too large to process.%s '.
					'Your order has %snot%s been placed. Please remove some '.
					'items from %syour cart%s or %scontact us%s to continue.'),
					'<strong>', '</strong>', '<em>', '</em>',
					'<a href="checkout/confirmation/cart">', '</a>',
					'<a href="about/contact">', '</a>').
				' '.Store::_('No funds have been removed from your card.').
				'</p>';

			break;
		case 'payment-error':
			$message = new SwatMessage(
				Store::_('There was a problem processing your payment.'),
				'error');

			$message->content_type = 'text/xml';
			$message->secondary_content =
				sprintf(
				Store::_('%sYour payment details are correct, but we were '.
					'unable to process your payment.%s Your order has %snot%s '.
					'been placed. Please %scontact us%s to complete your '.
					'order.'),
					'<strong>', '</strong>', '<em>', '</em>',
					'<a href="about/contact">', '</a>').
				' '.Store::_('No funds have been removed from your card.');

			break;
		case 'order-error':
			$message = new SwatMessage(
				Store::_('A system error occurred while processing your order'),
				'system-error');

			// TODO: only display account stuff if account was created
			$message->content_type = 'text/xml';
			$message->secondary_content = sprintf(
				Store::_(
					'Your account has been created, but your order has '.
					'%snot%s been placed and you have %snot%s been billed. '.
					'The error has been recorded and and we will attempt '.
					'to fix it as quickly as possible.'),
					'<em>', '</em>', '<em>', '</em>');

			break;
		case 'account-error':
			$message = new SwatMessage(
				Store::_('A system error occurred while processing your order'),
				'system-error');

			$message->content_type = 'text/xml';
			$message->secondary_content = sprintf(
				Store::_(
					'Your account has not been created, your order has '.
					'%snot%s been placed, and you have %snot%s been billed. '.
					'The error has been recorded and we will attempt to '.
					'fix it as quickly as possible.'),
					'<em>', '</em>', '<em>', '</em>');

			break;
		}

		return $message;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$this->buildOrder();

		$order = $this->app->session->order;
		$this->buildItems($order);
		$this->buildBasicInfo($order);
		$this->buildBillingAddress($order);
		$this->buildShippingAddress($order);
		$this->buildShippingType($order);
		$this->buildPaymentMethod($order);

		$this->buildMessage();
	}

	// }}}
	// {{{ protected function buildMessages()

	protected function buildMessages()
	{
	}

	// }}}
	// {{{ protected function buildMessage()

	protected function buildMessage()
	{
		if ($this->ui->getWidget('message_display')->getMessageCount() > 0) {
			// if there are messages, order cannot be placed
			$this->ui->getWidget('submit')->sensitive = false;
		} else {
			$message = new SwatMessage(Store::_('Please Review Your Order'));
			$message->content_type= 'text/xml';
			$message->secondary_content = sprintf(Store::_('Press the '.
				'%sPlace Order%s button to complete your order.'),
				'<em>', '</em>');

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}
	}

	// }}}
	// {{{ protected function buildOrder()

	protected function buildOrder()
	{
		if ($this->app->session->order->isFromInvoice())
			$this->createOrderFromInvoice();
		else
			$this->createOrder();
	}

	// }}}
	// {{{ protected function buildBasicInfo()

	protected function buildBasicInfo($order)
	{
		$ds = new SwatDetailsStore($order);
		$view = $this->ui->getWidget('basic_info_details');

		if ($this->app->session->isLoggedIn())
			$ds->fullname = $this->app->session->account->fullname;
		else
			$view->getField('fullname_field')->visible = false;

		$view->data = $ds;
	}

	// }}}
	// {{{ protected function buildBillingAddress()

	protected function buildBillingAddress($order)
	{
		ob_start();
		$order->billing_address->display();

		$this->ui->getWidget('billing_address')->content = ob_get_clean();
		$this->ui->getWidget('billing_address')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildShippingAddress()

	protected function buildShippingAddress($order)
	{
		ob_start();
		// compare references since these are not saved yet
		if ($order->shipping_address === $order->billing_address) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-none';
			$span_tag->setContent(Store::_('<ship to billing address>'));
			$span_tag->display();
		} else {
			$order->shipping_address->display();
		}

		$this->ui->getWidget('shipping_address')->content = ob_get_clean();
		$this->ui->getWidget('shipping_address')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildShippingType()

	protected function buildShippingType($order)
	{
		if (!$this->ui->hasWidget('shipping_type'))
			return;

		ob_start();

		if ($order->shipping_type instanceof StoreShippingType) {
			$order->shipping_type->display();
		} else {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-none';
			$span_tag->setContent(Store::_('<none>'));
			$span_tag->display();
		}

		$this->ui->getWidget('shipping_type')->content = ob_get_clean();
		$this->ui->getWidget('shipping_type')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildItems()

	protected function buildItems($order)
	{
		$items_view = $this->ui->getWidget('items_view');
		$items_view->model = $order->getOrderDetailsTableStore();

		$items_view->getRow('subtotal')->value = $order->getSubtotal();
		$items_view->getRow('shipping')->value = $order->shipping_total;
		if ($order->surcharge_total > 0)
			$items_view->getRow('surcharge')->value = $order->surcharge_total;

		$items_view->getRow('total')->value = $order->total;

		// invoice the items can not be edited
		if ($this->app->session->order->isFromInvoice())
			$this->ui->getWidget('item_link')->visible = false;
	}

	// }}}

	// build phase - payment method
	// {{{ protected function buildPaymentMethod()

	protected function buildPaymentMethod($order)
	{
		ob_start();

		if (count($order->payment_methods) > 0) {
			if ($this->app->config->store->multiple_payment_support) {
				$this->calculateMultiplePaymentMethods($order);
				$this->validatePaymentMethod(true);
				$this->displayMultiplePaymentMethods($order);
				$this->displayNewPaymentLinks($order);
			} else {
				$payment_method =  $order->payment_methods->getFirst();
				$payment_method->display();
				$this->displayNewPaymentLinks($order);
			}
		} else {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'swat-none';
			$span_tag->setContent(Store::_('<none>'));
			$span_tag->display();
			$this->displayNewPaymentLinks($order);
		}

		if ($this->app->config->store->multiple_payment_support)
			$this->ui->getWidget('payment_method_edit')->visible = false;

		$this->ui->getWidget('payment_method')->content = ob_get_clean();
		$this->ui->getWidget('payment_method')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function calculateMultiplePaymentMethods()

	protected function calculateMultiplePaymentMethods($order)
	{
		$payment_total = 0;
		$adjustable_payment_methods = array();

		foreach ($order->payment_methods as $payment_method) {
			if ($payment_method->amount === null) {
				$payment_method->setAdjustable();
			}

			if ($payment_method->isAdjustable()) {
				$payment_method->amount = 0;
				$adjustable_payment_methods[] = $payment_method;
			} else {
				$payment_total+= $payment_method->amount;
			}
		}

		if ($payment_total < $order->total) {
			// need more payment
			// add to adjustable payment, in order of payment priority
			$adjustable_payment_methods_by_priority =
				$this->sortPaymentMethodsByPriority($adjustable_payment_methods);

			$adjustment = $order->total - $payment_total;
			foreach ($adjustable_payment_methods_by_priority as $payment_method) {
				$max = $payment_method->getMaxAmount();
				if ($max === null || $adjustment <= $max) {
					$payment_method->amount = $adjustment;
					break;
				} else {
					$payment_method->amount = $max;
					$adjustment-= $max;
				}
			}

		} elseif ($payment_total > $order->total) {
			// too much payment, reduce in order of payment type priority
			$payment_methods_by_priority =
				$this->sortPaymentMethodsByPriority($order->payment_methods);

			$partial_payment_total = 0;
			$done = false;
			foreach ($payment_methods_by_priority as $payment_method) {
				if ($done) {
					$payment_method->amount = 0;
				} elseif ($partial_payment_total + $payment_method->amount > $order->total) {
					$payment_method->amount = $order->total - $partial_payment_total;
					$done = true;
				}

				$partial_payment_total+= $payment_method->amount;
			}
		}
	}

	// }}}
	// {{{ protected function sortPaymentMethodsByPriority()

	protected function sortPaymentMethodsByPriority($payment_methods)
	{
		$payment_methods_by_priority = array();
		$count = 0;

		foreach ($payment_methods as $payment_method) {
			$priority = $payment_method->payment_type->priority.'_'.$count;
			$payment_methods_by_priority[$priority] =  $payment_method;
			$count++;
		}

		krsort($payment_methods_by_priority);

		return $payment_methods_by_priority;
	}

	// }}}
	// {{{ protected function displayMultiplePaymentMethods()

	protected function displayMultiplePaymentMethods($order)
	{
		$payment_methods = array_reverse($order->payment_methods->getArray());

		echo '<table><tbody>';

		$payment_total = 0;
		foreach ($payment_methods as $payment_method) {
			$payment_total+= $payment_method->amount;

			echo '<tr><td>';
			$payment_method->display();
			echo '</td><td>';
			$payment_method->displayAmount();
			echo '</td><td>';
			$this->displayPaymentMethodToolLink($payment_method);
			echo '</td></tr>';
		}

		echo '</tbody><tfoot>';

		$locale = SwatI18NLocale::get();

		if (count($payment_methods) > 1) {
			echo '<tr><td>Payment Total</td><td>';
			echo $locale->formatCurrency($payment_total);
			echo '</td></tr>';
		}

		$balance = $order->total - $payment_total;
		if ($balance > 0) {
			echo '<tr><td>Remaining Balance</td><td>';
			echo $locale->formatCurrency($balance);
			echo '</td></tr>';
		}
		echo '</tfoot></table>';
	}

	// }}}
	// {{{ protected function displayNewPaymentLinks()

	protected function displayNewPaymentLinks($order)
	{
		$links = $this->getNewPaymentLinks($order);

		foreach ($links as $link) {
			$anchor = new SwatHtmlTag('a');
			$anchor->href = $link['href'];
			$anchor->setContent($link['title']);

			echo '<p>';
			$anchor->display();

			if (strlen($link['note']) > 0)
				echo '<br />', $link['note'];

			echo '</p>';
		}
	}

	// }}}
	// {{{ protected function getNewPaymentLinks()

	protected function getNewPaymentLinks($order)
	{
		$links = array();

		if ($this->app->config->store->multiple_payment_support &&
			($this->app->config->store->multiple_payment_ui ||
			!$this->hasSimplePaymentMethod($order))) {

			$links['payment_method'] = array(
				'href' => 'checkout/confirmation/paymentmethod/new',
				'title' => 'Add a New Payment',
				'note' => '',
			);
		}

		return $links;
	}

	// }}}
	// {{{ protected function hasSimplePaymentMethod()

	protected function hasSimplePaymentMethod($order)
	{
		$found = false;
		$region = $this->app->getRegion();

		// find a payment method that is edited with CheckoutPaymentMethodPage
		foreach ($order->payment_methods as $payment_method) {
			if ($payment_method->payment_type->isAvailableInRegion($region)) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	// }}}
	// {{{ protected function displayPaymentMethodToolLink()

	protected function displayPaymentMethodToolLink($payment_method)
	{
		$tag = uniqid();
		$payment_method->setTag($tag);
		$tool = new SwatToolLink();
		$tool->class = 'payment_method_edit';
		$tool->title = 'Edit';
		$tool->link = sprintf('checkout/confirmation/paymentmethod/%s', $tag);
		$tool->stock_id = 'edit';
		$tool->display();
	}

	// }}}

	// build phase - order creation
	// {{{ protected function createOrder()

	protected function createOrder()
	{
		$cart = $this->app->cart->checkout;
		$order = $this->app->session->order;

		$this->createOrderItems($order);

		$order->locale = $this->app->getLocale();

		$order->item_total = $cart->getItemTotal();

		$order->surcharge_total = $cart->getSurchargeTotal();

		$order->shipping_total = $cart->getShippingTotal(
			$order->billing_address, $order->shipping_address,
			$order->shipping_type);

		$order->tax_total = $cart->getTaxTotal($order->billing_address,
			 $order->shipping_address, $order->shipping_type);

		$order->total = $cart->getTotal($order->billing_address,
			$order->shipping_address, $order->shipping_type);

		// Reload ad from the database to esure it exists before trying to save
		// the order. This prevents order failure when a deleted ad ends up in
		// the session.
		$session_ad = $this->app->analytics->getAd();
		if ($session_ad !== null) {
			$ad_class = SwatDBClassMap::get('SiteAd');
			$ad = new $ad_class();
			$ad->setDatabase($this->app->db);
			if ($ad->load($session_ad->id)) {
				$order->ad = $ad;
			}
		}
	}

	// }}}
	// {{{ protected function createOrderItems()

	protected function createOrderItems($order)
	{
		$wrapper = SwatDBClassMap::get('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$order_item = $entry->createOrderItem();
			$order_item->setDatabase($this->app->db);
			$order->items->add($order_item);
		}
	}

	// }}}
	// {{{ protected function createOrderFromInvoice()

	protected function createOrderFromInvoice()
	{
		$order = $this->app->session->order;
		$invoice = $order->invoice;

		$this->createOrderItemsFromInvoice($order);

		$order->locale = $this->app->getLocale();

		$order->item_total = $invoice->getItemTotal();

		$order->shipping_total = $invoice->getShippingTotal(
			$order->billing_address, $order->shipping_address,
			$this->app->getRegion());

		$order->tax_total = $invoice->getTaxTotal($order->billing_address,
			 $order->shipping_address);

		$order->total = $invoice->getTotal($order->billing_address,
			$order->shipping_address, $this->app->getRegion());

		$order->ad = $this->app->app->analytics->getAd();
	}

	// }}}
	// {{{ protected function createOrderItemsFromInvoice()

	protected function createOrderItemsFromInvoice($order)
	{
		$wrapper = SwatDBClassMap::get('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		foreach ($order->invoice->items as $invoice_item) {
			$order_item = $invoice_item->createOrderItem();
			$order_item->setDatabase($this->app->db);
			$order->items->add($order_item);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-confirmation-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
