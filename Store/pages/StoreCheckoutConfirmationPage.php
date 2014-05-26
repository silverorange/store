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
 * @copyright 2006-2014 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutConfirmationPage extends StoreCheckoutPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/pages/checkout-confirmation.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->checkOrder();

		if ($this->ui->hasWidget('checkout_progress')) {
			$checkout_progress = $this->ui->getWidget('checkout_progress');
			$checkout_progress->current_step = 2;
		}

		if (isset($this->layout->cart_lightbox)) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'empty-message';
			$div_tag->setContent(
				sprintf(
					Store::_(
						'%sView and edit your shopping cart%s.'
					),
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/cart'
					).'">',
					'</a>'
				),
				'text/xml'
			);

			$this->layout->cart_lightbox->override_content =
				$div_tag->__toString();
		}
	}

	// }}}
	// {{{ protected function checkOrder()

	protected function checkOrder()
	{
		$order = $this->app->session->order;

		if ($order->billing_address instanceof StoreOrderAddress) {
			$this->checkAddress($order->billing_address);
		}

		if ($order->shipping_address instanceof StoreOrderAddress) {
			$this->checkAddress($order->shipping_address);
		}
	}

	// }}}
	// {{{ protected function checkAddress()

	protected function checkAddress(StoreOrderAddress $address)
	{
		$account = $this->app->session->account;

		// Check to make sure the address hasn't been deleted from the account.
		// If it has, remove the reference to the AccountAddress
		if ($address->getAccountAddressId() !== null) {
			$account_address = $account->addresses->getByIndex(
				$address->getAccountAddressId());

			if ($account_address === null)
				$address->clearAccountAddress();
		}
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array($this->getCheckoutSource().'/first');
	}

	// }}}
	// {{{ protected function getOrderTotal()

	protected function getOrderTotal()
	{
		return $this->app->cart->checkout->getTotal(
			$this->app->session->order->billing_address,
			$this->app->session->order->shipping_address,
			$this->app->session->order->shipping_type,
			$this->app->session->order->payment_methods
		);
	}

	// }}}
	// {{{ protected function isOrderFree()

	protected function isOrderFree()
	{
		return ($this->getOrderTotal() <= 0);
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

		$valid = $this->validatePaymentMethod() && $valid;
		$valid = $this->validateBillingAddress() && $valid;
		$valid = $this->validateShippingAddress() && $valid;
		$valid = $this->validateShippingType() && $valid;

		return $valid;
	}

	// }}}

	// validate billing
	// {{{ protected function validateBillingAddress()

	protected function validateBillingAddress()
	{
		$valid = true;

		$order = $this->app->session->order;
		if ($order->billing_address instanceof StoreOrderAddress) {
			$address = $order->billing_address;
			$valid = ($this->validateBillingAddressRequiredFields($address) &&
				$valid);

			$valid = ($this->validateBillingAddressCountry($address) &&
				$valid);

			$valid = ($this->validateBillingAddressProvState($address) &&
				$valid);
		} else {
			$this->ui->getWidget('message_display')->add(
				$this->getBillingAddressRequiredMessage(),
				SwatMessageDisplay::DISMISS_OFF
			);
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateBillingAddressRequiredFields()

	protected function validateBillingAddressRequiredFields(
		StoreOrderAddress $address)
	{
		$valid = true;
		$required_fields = $this->getRequiredBillingAddressFields($address);

		foreach ($required_fields as $field) {
			if (!isset($address->$field)) {

				$message = new SwatMessage(
					Store::_('Billing Address'), 'error');

				$message->secondary_content = sprintf(
					Store::_(
						'Billing address is missing required fields. Please '.
						'%sselect a different billing address or enter a new '.
						'billing address%s.'
					),
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/billingaddress'
					).'">',
					'</a>'
				);

				$message->content_type = 'text/xml';

				$this->ui->getWidget('message_display')->add(
					$message,
					SwatMessageDisplay::DISMISS_OFF
				);

				$valid = false;
				break;
			}
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateBillingAddressCountry()

	protected function validateBillingAddressCountry(StoreOrderAddress $address)
	{
		$valid = true;

		$country_ids = array();
		foreach ($this->app->getRegion()->billing_countries as $country) {
			$country_ids[] = $country->id;
		}

		$country_id = $address->getInternalValue('country');

		if ($country_id !== null && !in_array($country_id, $country_ids)) {
			$valid = false;
			$message = new SwatMessage(Store::_('Billing Address'), 'error');

			$message->secondary_content = sprintf(Store::_(
				'Orders can not be billed to %s. Please select a different '.
				'billing address or enter a new billing address.'),
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

		/*
		 * If provstate is null, it means it's either not required, or
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
			$message = new SwatMessage(Store::_('Billing Address'), 'error');
			$message->secondary_content = sprintf(Store::_(
				'Orders can not be billed to %s. Please select a different '.
				'billing address or enter a new billing address.'),
				$address->provstate->title);

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function getRequiredBillingAddressFields()

	protected function getRequiredBillingAddressFields(
		StoreOrderAddress $address)
	{
		$fields = array(
			'fullname',
			'line1',
			'city',
			'provstate',
			'phone',
		);

		$country = $address->country;
		if ($country->has_postal_code) {
			$fields[] = 'postal_code';
		}

		return $fields;
	}

	// }}}
	// {{{ protected function getBillingAddressRequiredMessage()

	protected function getBillingAddressRequiredMessage()
	{
		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $this->getCheckoutEditLink(
			'confirmation/billingaddress'
		);
		$a_tag->setContent(Store::_('add a billing address'));

		$message = new SwatMessage(Store::_('Billing Address'), 'error');
		$message->secondary_content = sprintf(
			Store::_(
				'A billing address is required. Please %s before '.
				'you place your order.'
			),
			$a_tag
		);
		$message->content_type = 'text/xml';

		return $message;
	}

	// }}}

	// validate shipping
	// {{{ protected function validateShippingAddress()

	protected function validateShippingAddress()
	{
		$valid = true;

		$order = $this->app->session->order;
		if ($order->shipping_address instanceof StoreOrderAddress) {
			$address = $order->shipping_address;

			$valid = (
				$this->validateShippingAddressRequiredFields($address) &&
				$this->validateShippingAddressCountry($address) &&
				$this->validateShippingAddressProvState($address)
			);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateShippingAddressRequiredFields()

	protected function validateShippingAddressRequiredFields(
		StoreOrderAddress $address)
	{
		$valid = true;
		$required_fields = $this->getRequiredShippingAddressFields($address);

		foreach ($required_fields as $field) {
			if (!isset($address->$field)) {

				$message = new SwatMessage(
					Store::_('Shipping Address'),
					'error'
				);

				$message->secondary_content = sprintf(Store::_(
					'Shipping address is missing required fields. Please '.
					'%sselect a different shipping address or enter a new '.
					'shipping address%s.'),
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/shippingaddress'
					).'">',
					'</a>'
				);

				$message->content_type = 'text/xml';

				$this->ui->getWidget('message_display')->add(
					$message,
					SwatMessageDisplay::DISMISS_OFF
				);

				$valid = false;
				break;
			}
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

		foreach ($this->app->getRegion()->shipping_countries as $country) {
			$country_ids[] = $country->id;
		}

		$country_id = $address->getInternalValue('country');

		if (!in_array($country_id, $country_ids)) {
			$valid = false;
			$message = new SwatMessage(Store::_('Shipping Address'), 'error');
			$message->secondary_content = sprintf(Store::_(
				'Orders can not be shipped to %s. Please select a different '.
				'shipping address or enter a new shipping address.'),
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

		/*
		 * If provstate is null, it means it's either not required, or
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
			$message = new SwatMessage(Store::_('Shipping Address'), 'error');
			$message->secondary_content = sprintf(Store::_(
				'Orders can not be shipped to %s. Please select a different '.
				'shipping address or enter a new shipping address.'),
				$address->provstate->title);

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}

		if ($valid) {
			$valid = $this->validateShippingProvstateExclusion($address);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateShippingProvStateExclusion()

	protected function validateShippingProvStateExclusion(
		StoreOrderAddress $address)
	{
		if (!isset($this->app->cart->checkout)) {
			return true;
		}

		$valid = true;
		$shipping_provstate = $address->getInternalValue('provstate');

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$bindings = $entry->item->prov_state_exclusion_bindings;
			foreach ($bindings as $binding) {
				$item_exclusion_provstate = $binding->getInternalValue(
					'provstate'
				);

				if ($item_exclusion_provstate == $shipping_provstate) {
					$valid = false;
					$message = new SwatMessage(
						Store::_('Shipping Address'),
						'error'
					);

					$message->content_type = 'text/xml';
					$message->secondary_content = sprintf(
						Store::_(
							'Item %s “%s” can not be shipped to %s. '.
							'Please %sselect a different shipping address%s '.
							'or %sremove this item%s from your order.'
						),
						SwatString::minimizeEntities($binding->item->sku),
						SwatString::minimizeEntities(
							$binding->item->product->title
						),
						SwatString::minimizeEntities(
							$address->provstate->title
						),
						'<a href="'.$this->getCheckoutEditLink(
							'confirmation/shippingaddress'
						).'">',
						'</a>',
						'<a href="'.$this->getCheckoutEditLink(
							'confirmation/cart'
						).'">',
						'</a>'
					);

					$this->ui->getWidget('message_display')->add(
						$message,
						SwatMessageDisplay::DISMISS_OFF
					);
				}
			}
		}

		return $valid;
	}

	// }}}
	// {{{ protected function getRequiredShippingAddressFields()

	protected function getRequiredShippingAddressFields(
		StoreOrderAddress $address)
	{
		$fields = array(
			'fullname',
			'line1',
			'city',
			'provstate',
			'phone',
		);

		$country = $address->country;
		if ($country->has_postal_code) {
			$fields[] = 'postal_code';
		}

		return $fields;
	}

	// }}}
	// {{{ protected function getShippingAddressRequiredMessage()

	protected function getShippingAddressRequiredMessage()
	{
		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $this->getCheckoutEditLink(
			'confirmation/shippingaddress'
		);
		$a_tag->setContent(Store::_('add a shipping address'));

		$message = new SwatMessage(Store::_('Shipping Address'), 'error');
		$message->secondary_content = sprintf(
			Store::_(
				'A shipping address is required. Please %s before '.
				'you place your order.'
			),
			$a_tag
		);
		$message->content_type = 'text/xml';

		return $message;
	}

	// }}}

	// validate shipping type
	// {{{ protected function validateShippingType()

	protected function validateShippingType()
	{
		return true;
	}

	// }}}

	// validate payment
	// {{{ protected function validatePaymentMethodWithMessage()

	protected function validatePaymentMethodWithMessage()
	{
		return $this->validatePaymentMethod(true);
	}

	// }}}
	// {{{ protected function validatePaymentMethodWithNoMessage()

	protected function validatePaymentMethodWithNoMessage()
	{
		return $this->validatePaymentMethod(false);
	}

	// }}}
	// {{{ protected function validatePaymentMethod()

	protected function validatePaymentMethod($show_message = false)
	{
		$valid = true;
		$order = $this->app->session->order;

		if ($this->app->config->store->multiple_payment_support) {
			$payment_total = 0;
			foreach ($order->payment_methods as $payment_method) {
				$payment_total += $payment_method->amount;
			}

			if ($order->total > $payment_total) {
				$valid = false;
			}
		} else {
			if (count($order->payment_methods) == 0) {
				$valid = false;
			}
		}

		if (!$valid && $show_message) {
			$message = $this->getPaymentMethodRequiredMessage();
			$this->ui->getWidget('message_display')->add(
				$message,
				SwatMessageDisplay::DISMISS_OFF
			);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function getPaymentMethodRequiredMessage()

	protected function getPaymentMethodRequiredMessage()
	{
		$message = new SwatMessage(Store::_('Payment'), 'error');
		$message->secondary_content = Store::_(
			'The payments on this order do not cover the order total. '.
			'Please edit an existing payment or add another '.
			'payment method.'
		);
		return $message;
	}

	// }}}

	// order processing and saving
	// {{{ protected function processOrder()

	protected function processOrder()
	{
		$saved = $this->save();

		if (!$saved)
			return;

		$this->sendConfirmationEmail();
		$this->removeCartEntries();
		$this->cleanupSession();
		$this->updateProgress();

		$this->app->relocate($this->getThankYouSource());
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
		// Save the account if a password has been set.
		if ($this->app->session->account->password != '') {
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
				$e->processAndContinue();
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

		// new addresses are only saved to accounts if the ini setting is true.
		if ($this->app->config->store->save_account_address) {
			if ($order->billing_address instanceof StoreOrderAddress) {
				$address = $this->addAddressToAccount($order->billing_address);
				$account->setDefaultBillingAddress($address);
			}

			// shipping address is only added if it differs from billing address
			if ($order->shipping_address instanceof StoreOrderAddress) {
				if ($order->shipping_address !== $order->billing_address) {
					$address = $this->addAddressToAccount(
						$order->shipping_address
					);
				}
				$account->setDefaultShippingAddress($address);
			}
		}

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
			$account->instance   = $this->app->getInstance();
			$account->createdate = new SwatDate();
			$account->createdate->toUTC();
		}

		// save account
		$account->save();

		// if this is a new account, log it in
		if ($new_account) {
			// clear account from session so we appear to not be logged in now
			// that the account is saved
			$this->app->session->account = null;

			// Login, but don't regenerate the session id. Regenerating the
			// session id will cause problems when the same order is submitted
			// multiple times by the customer.
			$this->app->session->loginById(
				$account->id,
				SiteSessionModule::NO_REGENERATE_ID
			);
		}
	}

	// }}}
	// {{{ protected function saveOrder()

	protected function saveOrder()
	{
		$order = $this->app->session->order;

		// remove unused payment methods when multiple payments are allowed
		if ($this->app->config->store->multiple_payment_support) {
			foreach ($order->payment_methods as $payment_method)
				if ($payment_method->amount <= 0)
					$order->payment_methods->remove($payment_method);

			$this->sortPaymentMethodsByPriority($order);
		}

		$order->instance = $this->app->getInstance();

		// attach order to account
		if (isset($this->app->session->account) &&
			$this->app->session->account instanceof StoreAccount &&
			$this->app->session->account->id != '') {
			$order->account = $this->app->session->account;
		}

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
	// {{{ protected function sendConfirmationEmail()

	protected function sendConfirmationEmail()
	{
		$order = $this->app->session->order;
		$order->sendConfirmationEmail($this->app);
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
		$this->app->ads->clearAd();
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
	 * <kbd>card-expired</kbd>            - for expired cards.
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
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content =
				'<p>'.sprintf(
					Store::_(
						'%sBilling address does not correspond with card '.
						'number.%s Your order has %snot%s been placed. '.
						'Please edit your %sbilling address%s and try again.'
					),
					'<strong>',
					'</strong>',
					'<em>',
					'</em>',
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/billingaddress'
					).'">',
					'</a>'
				).
				' '.$this->getErrorMessageNoFunds().
				'</p><p>'.$this->getErrorMessageContactUs().'</p>';

			break;
		case 'postal-code-mismatch':
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content =
				'<p>'.sprintf(
					Store::_(
						'%sBilling postal code / ZIP code does not correspond '.
						'with card number.%s Your order has %snot%s been '.
						'placed. Please edit your %sbilling address%s and try '.
						'again.'
					),
					'<strong>',
					'</strong>',
					'<em>',
					'</em>',
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/billingaddress'
					).'">',
					'</a>'
				).
				' '.$this->getErrorMessageNoFunds().
				'</p><p>'.$this->getErrorMessageContactUs().'</p>';

			break;
		case 'card-verification-value':
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content =
				'<p>'.sprintf(
					Store::_(
						'%sCard security code does not match card '.
						'number.%s Your order has %snot%s been placed. '.
						'Please %scorrect your card security code%s to '.
						'continue.'
					),
					'<strong>',
					'</strong>',
					'<em>',
					'</em>',
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/paymentmethod'
					).'">',
					'</a>'
				).
				' '.$this->getErrorMessageNoFunds().
				'</p><p>'.$this->getErrorMessageContactUs().'</p>';

			break;
		case 'card-type':
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content =
				'<p>'.sprintf(
					Store::_(
						'%sCard type does not correspond with card '.
						'number.%s Your order has %snot%s been placed. '.
						'Please edit your %spayment information%s and try '.
						'again.'
					),
					'<strong>',
					'</strong>',
					'<em>',
					'</em>',
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/paymentmethod'
					).'">',
					'</a>'
				).
				' '.$this->getErrorMessageNoFunds().
				'</p><p>'.$this->getErrorMessageContactUs().'</p>';

			break;
		case 'card-expired':
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content =
				'<p>'.sprintf(
					Store::_(
						'%sCard is expired.%s Your order has %snot%s been '.
						'placed. Please %suse a different card%s to continue.'
					),
					'<strong>',
					'</strong>',
					'<em>',
					'</em>',
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/paymentmethod'
					).'">',
					'</a>'
				).
				' '.$this->getErrorMessageNoFunds().
				'</p><p>'.$this->getErrorMessageContactUs().'</p>';

			break;
		case 'total':
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content =
				'<p>'.sprintf(
					Store::_(
						'%sYour order total is too large to process.%s '.
						'Your order has %snot%s been placed. Please remove '.
						'some items from %syour cart%s or %scontact us%s to '.
						'continue.'
					),
					'<strong>',
					'</strong>',
					'<em>',
					'</em>',
					'<a href="'.$this->getCheckoutEditLink(
						'confirmation/cart'
					).'">',
					'</a>',
					'<a href="'.$this->getEditLink('about/contact').'">',
					'</a>').
				' '.$this->getErrorMessageNoFunds().
				'</p>';

			break;
		case 'payment-error':
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content =
				sprintf(
				Store::_('%sYour payment details are correct, but we were '.
					'unable to process your payment.%s Your order has %snot%s '.
					'been placed. Please %scontact us%s to complete your '.
					'order.'),
					'<strong>', '</strong>', '<em>', '</em>',
					'<a href="'.$this->getEditLink('about/contact').'">',
					'</a>').
				' '.$this->getErrorMessageNoFunds();

			break;
		case 'order-error':
			// TODO: only display account stuff if account was created
			$message = $this->getPrototypeErrorMessage($message_id);
			$message->secondary_content = sprintf(
				Store::_(
					'Your account has been created, but your order has '.
					'%snot%s been placed and you have %snot%s been billed. '.
					'The error has been recorded and and we will attempt '.
					'to fix it as quickly as possible.'),
					'<em>', '</em>', '<em>', '</em>');

			break;
		case 'account-error':
			$message = $this->getPrototypeErrorMessage($message_id);
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
	// {{{ protected function getPrototypeErrorMessage()

	protected function getPrototypeErrorMessage($message_id)
	{
		switch ($message_id) {
		case 'order-error':
		case 'account-error':
			$message = new SwatMessage(
				Store::_(
					'A system error occurred while processing your order'
				),
				'system-error'
			);

			break;

		default:
			$message = new SwatMessage(
				Store::_(
					'There was a problem processing your payment.'
				),
				'error'
			);

			break;
		}

		$message->content_type = 'text/xml';

		return $message;
	}

	// }}}
	// {{{ protected function getErrorMessageNoFunds()

	protected function getErrorMessageNoFunds()
	{
		return Store::_('No funds have been removed from your card.');
	}

	// }}}
	// {{{ protected function getErrorMessageContactUs()

	protected function getErrorMessageContactUs()
	{
		return sprintf(
			Store::_(
				'If you are still unable to complete your order after '.
				'confirming your payment information, please %scontact us%s.'
			),
			'<a href="'.$this->getEditLink('about/contact').'">',
			'</a>'
		);
	}

	// }}}
	// {{{ protected function getEditLink()

	protected function getEditLink($link)
	{
		return $link;
	}

	// }}}
	// {{{ protected function getCheckoutEditLink()

	protected function getCheckoutEditLink($link)
	{
		return $this->getEditLink($this->getCheckoutSource().'/'.$link);
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
		$this->buildTaxMessage($order);

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
			$this->ui->getWidget('submit_button')->sensitive = false;
		} else {
			$message = new SwatMessage(Store::_('Please Review Your Order'));
			$message->content_type = 'text/xml';
			$message->secondary_content = sprintf(
				Store::_(
					'Press the %s button to complete your order.'
				),
				sprintf(
					'<em>%s</em>',
					$this->ui->getWidget('submit_button')->title
				)
			);
			$this->ui->getWidget('message_display')->add(
				$message,
				SwatMessageDisplay::DISMISS_OFF
			);
		}
	}

	// }}}
	// {{{ protected function buildOrder()

	protected function buildOrder()
	{
		$this->createOrder();
	}

	// }}}
	// {{{ protected function buildBasicInfo()

	protected function buildBasicInfo($order)
	{
		$view = $this->ui->getWidget('basic_info_details');
		$ds = $this->getBasicInfoDetailsStore($order);

		$view->data = $ds;

		if (!isset($ds->fullname) || $ds->fullname == '')
			$view->getField('fullname_field')->visible = false;
	}

	// }}}
	// {{{ protected function getBasicInfoDetailsStore()

	protected function getBasicInfoDetailsStore($order)
	{
		$ds = new SwatDetailsStore($order);

		if ($this->app->session->isLoggedIn())
			$ds->fullname = $this->app->session->account->fullname;

		return $ds;
	}

	// }}}
	// {{{ protected function buildBillingAddress()

	protected function buildBillingAddress($order)
	{
		if ($order->billing_address instanceof StoreOrderAddress) {
			ob_start();
			$order->billing_address->display();
			$this->ui->getWidget('billing_address')->content = ob_get_clean();
			$this->ui->getWidget('billing_address')->content_type = 'text/xml';
		}
	}

	// }}}
	// {{{ protected function buildShippingAddress()

	protected function buildShippingAddress($order)
	{
		if ($order->shipping_address instanceof StoreOrderAddress) {
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
	}

	// }}}
	// {{{ protected function buildTaxMessage()

	protected function buildTaxMessage($order)
	{
		if ($order->shipping_address instanceof StoreOrderAddress &&
			$order->shipping_address->provstate instanceof StoreProvState &&
			$order->shipping_address->provstate->tax_message !== null) {

			$container = new SwatDisplayableContainer('tax_message');
			$text = new SwatContentBlock();
			$text->content = $order->shipping_address->provstate->tax_message;
			$container->addChild($text);
			$this->ui->getWidget('item_container')->addChild($container);
		}
	}

	// }}}

	// build phase - payment method
	// {{{ protected function buildPaymentMethod()

	protected function buildPaymentMethod($order)
	{
		ob_start();

		if ($this->app->config->store->multiple_payment_support) {
			$this->calculateMultiplePaymentMethods($order);
		}

		$this->validatePaymentMethod(true);

		if ($this->app->config->store->multiple_payment_support) {
			$this->displayMultiplePaymentMethods($order);
			$this->displayNewPaymentLinks($order);
		} else {
			if (count($order->payment_methods) > 0) {
				$payment_method = $order->payment_methods->getFirst();
				$payment_method->showCardExpiry(false);
				$payment_method->display();
				$this->displayNewPaymentLinks($order);
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'swat-none';
				$span_tag->setContent(Store::_('<none>'));
				$span_tag->display();
				$this->displayNewPaymentLinks($order);
			}
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
		$this->sortPaymentMethodsByPriority($order);
		$payment_total = 0;
		$payment_methods = $order->payment_methods->getArray();
		$adjustable_payment_methods = array();

		foreach ($payment_methods as $payment_method) {
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
			$adjustment = $order->total - $payment_total;
			foreach ($adjustable_payment_methods as $payment_method) {
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
			$partial_payment_total = 0;
			$done = false;
			foreach ($payment_methods as $payment_method) {
				$payment_total =
					$partial_payment_total + $payment_method->amount;

				if ($done) {
					$payment_method->amount = 0;
				} elseif ($payment_total > $order->total) {
					$payment_method->amount =
						$order->total - $partial_payment_total;

					$done = true;
				}

				$partial_payment_total+= $payment_method->amount;
			}
		}
	}

	// }}}
	// {{{ protected function sortPaymentMethodsByPriority()

	protected function sortPaymentMethodsByPriority($order)
	{
		$payment_methods = $order->payment_methods->getArray();
		$order->payment_methods->removeAll();

		usort($payment_methods, array($this, 'sortPaymentMethodsCallback'));

		$count = 1;
		foreach ($payment_methods as $payment_method) {
			$payment_method->displayorder = $count++;
			$order->payment_methods->add($payment_method);
		}
	}

	// }}}
	// {{{ protected function sortPaymentMethodsCallback()

	protected function sortPaymentMethodsCallback($method1, $method2)
	{
		$result = 0;

		if ($method1->isAdjustable() && !$method2->isAdjustable())
			$result = 1;
		elseif ($method2->isAdjustable() && !$method1->isAdjustable())
			$result = -1;

		if ($result == 0) {
			$result = $method2->payment_type->priority -
				$method1->payment_type->priority;
		}

		if ($result == 0)
			$result = strcmp($method1->getTag(), $method2->getTag());

		return $result;
	}

	// }}}
	// {{{ protected function displayMultiplePaymentMethods()

	protected function displayMultiplePaymentMethods($order)
	{
		echo '<table class="multiple-payment-table"><tbody>';

		$payment_total = 0;
		foreach ($order->payment_methods as $payment_method) {
			$payment_method->showCardExpiry(false);
			$payment_total+= $payment_method->amount;

			echo '<tr><th class="payment">';
			$payment_method->display();
			echo '</th><td class="payment-amount">';
			$payment_method->displayAmount();
			echo '</td><td class="payment-edit">';
			$this->displayPaymentMethodToolLink($payment_method);
			echo '</td></tr>';
		}

		echo '</tbody><tfoot>';

		$locale = SwatI18NLocale::get();
		$balance = $order->total - $payment_total;

		echo '<tr><th>Payment Total:</th><td class="payment-amount">';
		echo $locale->formatCurrency($payment_total);
		echo '</td><td></td></tr>';

		if ($balance > 0) {
			echo '<tr class="payment-remaining swat-error">'.
				'<th>Remaining Balance:</th><td class="payment-amount">';

			echo $locale->formatCurrency($balance);
			echo '</td><td></td></tr>';
		}

		echo '</tfoot></table>';
	}

	// }}}
	// {{{ protected function displayNewPaymentLinks()

	protected function displayNewPaymentLinks(StoreOrder $order)
	{
		$links = $this->getNewPaymentLinks($order);

		foreach ($links as $link) {
			$anchor = new SwatHtmlTag('a');
			$anchor->href = $link['href'];
			$anchor->setContent($link['title']);

			echo '<p class="new-payment-link">';
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
				'href' => $this->getCheckoutEditLink(
					'confirmation/paymentmethod/new'
				),
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
			if (!$payment_method->payment_type->isAccount() &&
				!$payment_method->payment_type->isVoucher()) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	// }}}
	// {{{ protected function displayPaymentMethodToolLink()

	protected function displayPaymentMethodToolLink(
		StorePaymentMethod $payment_method)
	{
		$tag = $payment_method->getTag();

		$tool = new SwatToolLink();
		$tool->class = 'payment_method_edit';
		$tool->title = 'Edit';
		$tool->link = $this->getCheckoutEditLink(
			sprintf(
				'confirmation/paymentmethod/%s',
				$tag
			)
		);
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
		$order->voucher_total = $cart->getVoucherTotal();
		$order->surcharge_total = $cart->getSurchargeTotal(
			$order->payment_methods);

		$order->shipping_total = $cart->getShippingTotal(
			$order->billing_address,
			$order->shipping_address,
			$order->shipping_type
		);

		$order->tax_total = $cart->getTaxTotal(
			$order->billing_address,
			$order->shipping_address,
			$order->shipping_type,
			$order->payment_methods
		);

		$order->total = $this->getOrderTotal();

		// Reload ad from the database to esure it exists before trying to save
		// the order. This prevents order failure when a deleted ad ends up in
		// the session.
		$session_ad = $this->app->ads->getAd();
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
		$region = $this->app->getRegion();

		$wrapper = SwatDBClassMap::get('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$order_item = $entry->createOrderItem();
			$order_item->setDatabase($this->app->db);
			$order_item->setAvailableItemCache($region, $entry->item);
			$order_item->setItemCache($entry->item);
			$order->items->add($order_item);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry('packages/store/styles/store-cart.css');
		$this->layout->addHtmlHeadEntry(
			'packages/store/styles/store-checkout-confirmation-page.css'
		);
	}

	// }}}
}

?>
