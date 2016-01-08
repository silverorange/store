<?php

require_once 'Swat/SwatString.php';
require_once 'Store/StorePaymentProvider.php';
require_once 'Store/StorePaymentRequest.php';
require_once 'Store/exceptions/StorePaymentBraintreeException.php';

/**
 * @package   Store
 * @copyright 2011-2015 silverorange
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
 */
class StoreBraintreePaymentProvider extends StorePaymentProvider
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $environment;

	/**
	 * @var string
	 */
	protected $merchant_id;

	/**
	 * @var string
	 */
	protected $public_key;

	/**
	 * @var string
	 */
	protected $private_key;

	/**
	 * @var string
	 */
	protected $site_title = '';

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment provider using the Braintree API
	 *
	 * Available parameters are:
	 *
	 * <kbd>environment</kbd> - optional. Transaction mode to use. Must be one
	 *                          of either 'production' or 'sandbox'. If not
	 *                          specified, 'sandbox' is used.
	 * <kbd>merchant_id</kbd> - required. Login identifier for authentication.
	 * <kbd>public_key</kbd>  - required. Transaction key for authentication.
	 * <kbd>private_key</kbd> - required. Transaction key for authentication.
	 * <kbd>site_title</kbd>  - optional. The title of the site which is
	 *                          placing the order. If specified, the order id
	 *                          will be prefixed with the title. If not
	 *                          specified, order ids will not be prefixed.
	 *
	 * @throws StoreException if a required parameter is missing or if the
	 *                        'environment' paramater is not valid.
	 */
	public function __construct(array $parameters = array())
	{
		$required_parameters = array(
			'merchant_id',
			'public_key',
			'private_key',
		);

		foreach ($required_parameters as $parameter) {
			if (!isset($parameters[$parameter])) {
				throw new StoreException(
					'"'.$parameter.'" is required in the Braintree payment '.
					'provider parameters.'
				);
			}
		}

		if (!isset($parameters['environment'])) {
			$parameters['environment'] = 'sandbox';
		}

		$valid_environments = array('production', 'sandbox');
		if (!in_array($parameters['environment'], $valid_environments)) {
			throw new StoreException(
				'Environment "'.$environment.'" is not valid for the '.
				'Braintree payment provider.'
			);
		}

		$this->merchant_id = $parameters['merchant_id'];
		$this->public_key  = $parameters['public_key'];
		$this->private_key = $parameters['private_key'];
		$this->environment = $parameters['environment'];

		if (isset($parameters['site_title'])) {
			$this->site_title = $parameters['site_title'];
		}
	}

	// }}}
	// {{{ public function pay()

	/**
	 * Pay for an order immediately
	 *
	 * @param StoreOrder $order the order to pay for.
	 * @param string $card_number the card number to use for payment.
	 * @param string $card_verification_value optional. Card verification value
	 *                                         used for fraud prevention.
	 *
	 * @return StorePaymentMethodTransaction the transaction object for the
	 *                                        payment. This object contains the
	 *                                        transaction date and identifier.
	 *
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 */
	public function pay(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = array(
			'amount' => $this->formatCurrency($order->total),
			'orderId' => $this->getOrderId($order),
			'creditCard' => $this->getCreditCard(
				$order,
				$card_number,
				$card_verification_value
			),
			'options' => array(
				'submitForSettlement' => true,
			),
		);

		if ($order->billing_address instanceof StoreOrderAddress) {
			$request['billing'] = $this->getBillingAddress(
				$order->billing_address
			);
		}

		if ($order->account instanceof StoreAccount) {
			$request['customer'] = $this->getCustomer($order->account);
		}

		$custom_fields = $this->getCustomFields($order);
		if (count($custom_fields) > 0) {
			$request['customFields'] = $custom_fields;
		}

		// do transaction
		$this->setConfig();
		$response = Braintree_Transaction::sale($request);

		// check for errors and throw exception
		if (!$response->success) {
			throw new StorePaymentBraintreeException(
				'Error processing Braintree payment.',
				0,
				$response
			);
		}

		return $this->createPaymentMethodTransaction($response->transaction);
	}

	// }}}
	// {{{ public function getExceptionMessageId()

	public function getExceptionMessageId(Exception $e)
	{
		if ($e instanceof Braintree_Exception_Authentication ||
			$e instanceof Braintree_Exception_Authorization ||
			$e instanceof Braintree_Exception_Configuration ||
			$e instanceof Braintree_Exception_ServerError ||
			$e instanceof Braintree_Exception_UpgradeRequired) {
			return 'payment-error';
		}

		if ($e instanceof StorePaymentBraintreeException) {
			$response = $e->getResponse();

			// data validation error(s)
			if ($response->errors->deepSize() > 0) {
				return 'payment-error';
			}

			$transaction = $response->transaction;
			$status = $transaction->status;

			// transaction error
			if ($status === Braintree_Transaction::PROCESSOR_DECLINED) {
				switch ($transaction->processorResponseCode) {
				case 2004: // expired card
					return 'card-expired';
				case 2010: // issuer declined CVV
					return 'card-verification-value';
				case 2060: // address verification failed
					return 'address-mismatch';
				case 2005: // invalid number
				case 2008: // account length error
				case 2007: // no account
				case 2009: // invalid issuer
				case 2051: // card number does not match payment type
					return 'card-not-valid';
				case 2014: // card type not enabled
					return 'card-type';
				case 2000: // do not honor
				case 2001: // insufficient funds
				case 2002: // limit exceeded
				case 2003: // activity limit exceeded
				case 2015: // not allowed (reason unknown)
				case 2041: // declined (call for approval)
				case 2044: // declined (call issuer)
				case 2046: // declined (customer needs to call bank)
				case 2057: // issuer or cardholder restricted
				default:
					return 'card-error';
				}
			}

			if ($status === Braintree_Transaction::SETTLEMENT_DECLINED) {
				return 'payment-error';
			}

			if ($status === Braintree_Transaction::GATEWAY_REJECTED) {
				switch ($transaction->gatewayRejectionReason) {
				case Braintree_Transaction::AVS:
					return 'address-mismatch';
				case Braintree_Transaction::AVS_AND_CVV:
				case Braintree_Transaction::CVV:
					return 'card-verification-value';
				default:
					return 'payment-error';
				}
			}
		}

		return null;
	}

	// }}}
	// {{{ protected function createPaymentMethodTransaction()

	protected function createPaymentMethodTransaction(
		Braintree_Transaction $external_transaction,
		$type = StorePaymentRequest::TYPE_PAY)
	{
		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->transaction_type = $type;
		$transaction->transaction_id = $external_transaction->id;
		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();

		return $transaction;
	}

	// }}}
	// {{{ protected function setConfig()

	protected function setConfig()
	{
		Braintree_Configuration::environment($this->environment);
		Braintree_Configuration::merchantId($this->merchant_id);
		Braintree_Configuration::publicKey($this->public_key);
		Braintree_Configuration::privateKey($this->private_key);
	}

	// }}}
	// {{{ protected function getCreditCard()

	/**
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 */
	protected function getCreditCard(
		StoreOrder $order, $card_number, $card_verification_value = null)
	{
		// Default expiry date to use if no date is found in a payment method
		// is 1 month ago (expired).
		$date = new SwatDate('-1 month');
		$name = '';

		// Get expiration date and cardholder from payment method.
		foreach ($order->payment_methods as $payment_method) {
			if ($payment_method->getUnencryptedCardNumber() == $card_number) {
				$date = clone $payment_method->card_expiry;
				$name = $payment_method->card_fullname;
				break;
			}
		}

		// No name on payment method, try to get name from billing address
		if ($name == '' &&
			$order->billing_address instanceof StoreOrderAddress) {
			$name = $order->billing_address->getFullname();
		}

		return array(
			'cardholderName' => $this->truncateField($name, 175),
			'cvv'            => $card_verification_value,
			'expirationDate' => $date->formatLikeIntl('MM/yy'),
			'number'         => $card_number,
		);
	}

	// }}}
	// {{{ protected function getBillingAddress()

	protected function getBillingAddress(StoreOrderAddress $address)
	{
		if ($address->provstate_other != null) {
			$region = $address->provstate_other;
		} elseif ($address->provstate instanceof StoreProvState) {
			$region = $address->provstate->abbreviation;
		} else {
			// Some international addresses do not need a region.
			$region = null;
		}

		$lines = explode("\n", $address->line1);
		if (count($lines) === 1) {
			$line1 = $address->line1;
			$line2 = $address->line2;
		} else {
			$line1 = $lines[0];
			$line2 = $lines[1];
		}

		$names = $this->getAddressNames($address);

		$request = array(
			'countryCodeAlpha2' => $address->country->id,
			'firstName' => $this->truncateField($names['first'], 255),
			'locality' => $this->truncateField($address->city, 255),
			'postalCode' => $address->postal_code,
			'streetAddress' => $this->truncateField($line1, 255),
		);

		if ($region !== null) {
			$request['region'] = $this->truncateField($region, 255);
		}

		if ($names['last'] != '') {
			$request['lastName'] = $this->truncateField(
				$address->last_name,
				255
			);
		}

		if ($line2 != '') {
			$request['extendedAddress'] = $this->truncateField($line2, 255);
		}

		if ($address->company) {
			$request['company'] = $this->truncateField($address->company, 255);
		}

		return $request;
	}

	// }}}
	// {{{ protected function getCustomer()

	protected function getCustomer(StoreAccount $account)
	{
		$names = $this->getAccountNames($account);

		$request = array(
			'firstName' => $this->truncateField($names['first'], 255),
		);

		if ($names['last'] != '') {
			$request['lastName'] = $this->truncateField($names['last'], 255);
		}

		if ($account->company != '') {
			$request['company'] = $this->truncateField($account->company, 255);
		}

		if ($account->phone != '') {
			$request['phone'] = $this->truncateField($account->phone, 255);
		}

		if ($account->email != '') {
			$request['email'] = $this->truncateField($account->email, 255);
		}

		return $request;
	}

	// }}}
	// {{{ protected function getCustomFields()

	protected function getCustomFields(StoreOrder $order)
	{
		return array(
			'site_title' => $this->truncateField($this->site_title, 255),
			'order_description' => $this->truncateField(
				$this->getOrderDescription($order),
				255
			),
		);
	}

	// }}}
	// {{{ protected function getOrderDescription()

	protected function getOrderDescription(StoreOrder $order)
	{
		return Store::_('Online Order');
	}

	// }}}
	// {{{ protected function getOrderId()

	protected function getOrderId(StoreOrder $order)
	{
		$order_id = (string)$order->id;

		if ($this->site_title != '') {
			$order_id = $this->site_title.' '.$order_id;
		}

		return $order_id;
	}

	// }}}
	// {{{ protected function getAddressNames()

	protected function getAddressNames(StoreOrderAddress $address)
	{
		return $this->splitFullName($address->fullname);
	}

	// }}}
	// {{{ protected function getAccountNames()

	protected function getAccountNames(StoreAccount $account)
	{
		return $this->splitFullName($account->fullname);
	}

	// }}}
	// {{{ protected function formatCurrency()

	/**
	 * @param float $value
	 *
	 * @return string
	 */
	protected function formatCurrency($value)
	{
		$value = round($value, 2);
		return number_format($value, 2, '.', '');
	}

	// }}}
	// {{{ protected function truncateField()

	protected function truncateField($content, $maxlength)
	{
		$content = SwatString::condense($content, $maxlength - 4, ' ...');
		$content = str_replace('  •  ', ' - ', $content);
		$content = html_entity_decode($content, ENT_QUOTES, 'ISO-8859-1');
		return $content;
	}

	// }}}
	// {{{ private function splitFullname()

	private function splitFullname($full_name)
	{
		$parts = explode(' ', $full_name, 2);

		if (count($parts) === 2) {
			$first = trim($parts[0]);
			$last = trim($parts[1]);
		} else {
			$first = trim($parts[0]);
			$last = '';
		}

		return array(
			'first' => $first,
			'last'  => $last,
		);
	}

	// }}}
}

?>
