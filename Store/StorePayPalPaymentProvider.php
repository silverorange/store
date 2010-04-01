<?php

require_once 'Store/StorePaymentProvider.php';
require_once 'Swat/SwatNumber.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Payment/PayPal/SOAP.php';

/**
 * Payment provider for PayPal payments
 *
 * Only the {@link StorePaymentProvider::pay()} and
 * {@link StorePaymentProvider::hold()} methods are implemented, corresponding
 * to the PayPal DirectPay "Sale" and "Authorization" actions.
 *
 * Additionally, methods to handle PayPal Express Checkout are provided.
 *
 * @package   Store
 * @copyright 2009-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
 */
class StorePayPalPaymentProvider extends StorePaymentProvider
{
	// {{{ class constants

	const EXPRESS_CHECKOUT_URL_LIVE =
		'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=%s&useraction=%s';

	const EXPRESS_CHECKOUT_URL_SANDBOX =
		'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=%s&useraction=%s';

	// }}}
	// {{{ protected properties

	/**
	 * The currency to use for transactions
	 *
	 * @var string
	 * @see StorePayPalPaymentProvider::__construct()
	 */
	protected $currency;

	/**
	 * PayPal SOAP client
	 *
	 * @var Payment_PayPal_SOAP
	 * @see StoreProtxPaymentProvider::__construct()
	 */
	protected $client;

	/**
	 * @var string
	 * @see StorePayPalPaymentProvider::__construct()
	 */
	protected $mode;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment provider using the PayPal SOAP API
	 *
	 * Available parameters are:
	 *
	 * <kbd>mode</kbd>      - optional. Transaction mode to use. Must be one of
	 *                        either 'live' or 'sandbox'. If not specified,
	 *                        'sandbox' is used.
	 * <kbd>username</kbd>  - required. Username for PayPal authentication.
	 * <kbd>password</kbd>  - required. Password for PayPal authentication.
	 * <kbd>subject</kbd>   - optional. Third-party on behalf of whom requests
	 *                        should be made. Use for market-place type apps.
	 * <kbd>signature</kbd> - required. Signature used for signature-based
	 *                        authentication.
	 * <kbd>currency</kbd>  - required. The currency in which to perform
	 *                        transactions.
	 *
	 * @throws StoreException if a required parameter is missing or if the
	 *                        'mode' paramater is not valid.
	 */
	public function __construct(array $parameters = array())
	{
		$required_parameters = array(
			'username',
			'password',
			'signature',
			'currency',
		);

		foreach ($required_parameters as $parameter) {
			if (!isset($parameters[$parameter])) {
				throw new StoreException('"'.$parameter.'" is required in the '.
					'PayPal payment provider parameters.');
			}
		}

		$this->currency = $parameters['currency'];

		$options = array(
			'username'  => $parameters['username'],
			'password'  => $parameters['password'],
			'signature' => $parameters['signature'],
		);

		if (!isset($parameters['mode'])) {
			$parameters['mode'] = 'sandbox';
		}

		if (isset($parameters['subject'])) {
			$options['subject'] = $parameters['subject'];
		}

		$valid_modes = array('live', 'sandbox');
		if (!in_array($parameters['mode'], $valid_modes)) {
			throw new StoreException('Mode "'.$mode.'" is not valid for '.
				'the PayPal payment provider.');
		}

		$options['mode'] = $parameters['mode'];
		$this->mode      = $parameters['mode'];

		$this->client = new Payment_PayPal_SOAP($options);
	}

	// }}}

	// direct payment methods
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
	 */
	public function pay(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = $this->getDoDirectPaymentRequest($order, 'Sale',
			$card_number, $card_verification_value);

		try {
			$response = $this->client->call('DoDirectPayment', $request);
		} catch (Payment_PayPal_SOAP_ErrorException $e) {
			// ignore warnings
			if ($e->getSeverity() === Payment_PayPal_SOAP::ERROR_WARNING) {
				$response = $e->getResponse();
			} else {
				throw $e;
			}
		}

		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->transaction_type = StorePaymentRequest::TYPE_PAY;
		$transaction->transaction_id = $response->TransactionID;

		return $transaction;
	}

	// }}}
	// {{{ public function hold()

	/**
	 * Place a hold on funds for an order
	 *
	 * @param StoreOrder $order the order to hold funds for.
	 * @param string $card_number the card number to place the hold on.
	 * @param string $card_verification_value optional if AVS mode is set to
	 *                                         off. The three-digit security
	 *                                         code found on the reverse of
	 *                                         cards or the four-digit security
	 *                                         code found on the front of amex
	 *                                         cards.
	 *
	 * @return StorePaymentMethodTransaction the transaction object for the
	 *                                        payment. This object contains the
	 *                                        transaction date and identifier.
	 *
	 * @sensitive $card_number
	 */
	public function hold(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = $this->getDoDirectPaymentRequest($order, 'Authorization',
			$card_number, $card_verification_value);

		try {
			$response = $this->client->call('DoDirectPayment', $request);
		} catch (Payment_PayPal_SOAP_ErrorException $e) {
			// ignore warnings
			if ($e->getSeverity() === Payment_PayPal_SOAP::ERROR_WARNING) {
				$response = $e->getResponse();
			} else {
				throw $e;
			}
		}

		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->transaction_type = StorePaymentRequest::TYPE_HOLD;
		$transaction->transaction_id = $response->TransactionID;

		return $transaction;
	}

	// }}}

	// express checkout payment methods
	// {{{ public function setExpressCheckout()

	/**
	 * Initiates or updates an express checkout transaction
	 *
	 * @param array $details array of name-value pairs. Required parameters
	 *                        are 'ReturnURL' and 'CancelURL'. If no
	 *                        <kbd>$payment_details</kbd> are specified, the
	 *                        'PaymentDetails' parameter is required.
	 * @param array $payment_details array of name-value pairs. Required
	 *                                parameters are 'OrderTotal'.
	 *
	 * @return string the token of the transaction.
	 *
	 * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
	 * @see StorePayPalPaymentProvider::doExpressCheckout()
	 */
	public function setExpressCheckout(array $details,
		array $payment_details = array())
	{
		$required_parameters = array('ReturnURL', 'CancelURL');
		foreach ($required_parameters as $name) {
			if (!array_key_exists($name, $details)) {
				throw new StoreException('Required setExpressCheckout() '.
					'$details parameter "'.$name.'" is missing.');
			}
		}

		if ($payment_details == array() && isset($details['PaymentDetails'])) {
			$payment_details  = $details['PaymentDetails'];
		}

		$required_parameters = array('OrderTotal');
		foreach ($required_parameters as $name) {
			if (!array_key_exists($name, $payment_details)) {
				throw new StoreException('Required setExpressCheckout() '.
					'$payment_details parameter"'.$name.'" is missing.');
			}
		}

		$details['PaymentDetails'] = $payment_details;
		$request = $this->getSetExpressCheckoutRequest($details);

		try {
			$response = $this->client->call('SetExpressCheckout', $request);
		} catch (Payment_PayPal_SOAP_ErrorException $e) {
			// ignore warnings
			if ($e->getSeverity() === Payment_PayPal_SOAP::ERROR_WARNING) {
				$response = $e->getResponse();
			} else {
				throw $e;
			}
		}

		// According to the PayPal WSDL and SOAP schemas, it should be
		// impossible to get a response without a token, but due to incredible
		// incompetance on PayPal's end, the token is not returned if the token
		// is specified in the original array of parameters. In this case, just
		// return the token as it was passed in, as the response value should
		// be identical anyhow according to the documentation.
		if (isset($response->Token)) {
			$token = $response->Token;
		} else {
			if (isset($parameters['Token'])) {
				$token = $parameters['Token'];
			} else {
				throw new StoreException('No token returned in '.
					'SetExpressCheckout call.');
			}
		}

		return $token;
	}

	// }}}
	// {{{ public function getExpressCheckoutUri()

	/**
	 * Gets the URI for PayPal's Express Checkout
	 *
	 * Site code should relocate to this URI.
	 *
	 * @param string $token the token of the current transaction.
	 * @param boolean $continue optional. Whether or not the customer should
	 *                           will continue to a review page, or will commit
	 *                           to payment without an additonal review step.
	 *
	 * @return string the URI to which the browser should be relocated to
	 *                 continue the Express Checkout transaction.
	 *
	 * @see StorePayPalPaymentProvider::setExpressCheckout()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
	 * @see StorePayPalPaymentProvider::doExpressCheckout()
	 */
	public function getExpressCheckoutUri($token, $continue = true)
	{
		$useraction = ($continue) ? 'continue' : 'commit';

		if ($this->mode === 'live') {
			$uri = sprintf(
				self::EXPRESS_CHECKOUT_URL_LIVE,
				urlencode($token),
				$useraction);
		} else {
			$uri = sprintf(
				self::EXPRESS_CHECKOUT_URL_SANDBOX,
				urlencode($token),
				$useraction);
		}

		return $uri;
	}

	// }}}
	// {{{ public function getExpressCheckoutDetails()

	/**
	 * Updates an order with payment details from an Express Checkout
	 * transaction
	 *
	 * This sets the order payment method and order billing address.
	 *
	 * @param string $token the token of the Express Checkout transaction for
	 *                       which to get the details.
	 * @param StoreOrder $order the order object to update.
	 * @param MDB2_Driver_Common $db the database. This is used for parsing
	 *                                addresses and payment types.
	 *
	 * @see StorePayPalPaymentProvider::setExpressCheckout()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
	 * @see StorePayPalPaymentProvider::doExpressCheckout()
	 */
	public function getExpressCheckoutDetails($token, StoreOrder $order,
		MDB2_Driver_Common $db)
	{
		$request = $this->getGetExpressCheckoutDetailsRequest($token);

		try {
			$response = $this->client->call('GetExpressCheckoutDetails',
				$request);
		} catch (Payment_PayPal_SOAP_ErrorException $e) {
			// ignore warnings
			if ($e->getSeverity() === Payment_PayPal_SOAP::ERROR_WARNING) {
				$response = $e->getResponse();
			} else {
				throw $e;
			}
		}

		$details = $response->GetExpressCheckoutDetailsResponseDetails;

		$payment_method = $order->payment_methods->getByPayPalToken($token);
		if ($payment_method === null) {
			$payment_method = $this->getStoreOrderPaymentMethod($db);
			$order->payment_methods->add($payment_method);
		}
		$this->updateStoreOrderPaymentMethod(
			$payment_method,
			$token,
			$details->PayerInfo,
			$db
		);

		// set billing address if it was returned
		if (isset($details->BillingAddress) &&
			isset($details->BillingAddress->Country)) {
			$billing_address = $this->getStoreOrderAddress(
				$details->BillingAddress, $db);

			// Only set address if it is not already set or if it is not the
			// same as the existing billing address.
			if ($order->billing_address === null ||
				!$order->billing_address->compare($billing_address)) {
				$order->billing_address = $billing_address;
			}
		}

		// set shipping address if it was returned
		if (isset($details->PayerInfo->Address->Country)) {
			$shipping_address = $this->getStoreOrderAddress(
				$details->PayerInfo->Address, $db);

			// Only set address if it is not already set or if it is not the
			// same as the existing shipping address.
			if ($order->shipping_address === null ||
				!$order->shipping_address->compare($shipping_address)) {
				$order->shipping_address = $shipping_address;
			}

			// Only set billing address if it is not already set.
			if ($order->billing_address === null) {
				$order->billing_address = $order->shipping_address;
			}
		}

		if ($order->email === null) {
			$order->email = $details->PayerInfo->Payer;
		}

		if (isset($details->ContactPhone) && $order->phone === null) {
			$order->phone = $details->ContactPhone;
		}
	}

	// }}}
	// {{{ public function doExpressCheckout()

	/**
	 * Completes an Express Checkout payment
	 *
	 * The <kbd>$action</kbd> must be the same value as the original request
	 * that generated the <kbd>$token</kbd>.
	 *
	 * @param string $token the token of the active transaction.
	 * @param string $action one of 'Sale' or 'Authorization' or 'Order'.
	 * @param string $payer_id PayPal payer identification number as returned
	 *                          by the <kbd>getExpressCheckout()</kbd> method.
	 * @param StoreOrder $order the order to pay for.
	 * @param string $notify_url optional. The URL where the Instant Payment
	 *                            Notification (IPN) from PayPal should be
	 *                            sent. If not specified, the IPN will be sent
	 *                            to the URL set in your PayPal account.
	 * @param string $custom optional. A custom value that is passed through
	 *                        with your order. This value will be present in
	 *                        the IPN if it is specified.
	 *
	 * @return array a two element array containing a
	 *                {@link StorePaymentMethodTransaction} object representing
	 *                the transaction as well as an object containing the
	 *                detailed response from PayPal. The array keys are:
	 *                <kbd>transaction</kbd> and <kbd>details</kbd>
	 *                respectively.
	 *
	 * @see StorePayPalPaymentProvider::setExpressCheckout()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
	 */
	public function doExpressCheckout($token, $action,
		$payer_id, StoreOrder $order, $notify_url = '', $custom = '')
	{
		switch ($action) {
		case 'Authorizarion':
			$transaction_type = StorePaymentRequest::TYPE_HOLD;
			break;

		case 'Sale':
		case 'Order':
		default:
			$transaction_type = StorePaymentRequest::TYPE_PAY;
			break;
		}

		$request = $this->getDoExpressCheckoutPaymentRequest($token,
			$action, $payer_id, $order);

		try {
			$response = $this->client->call('DoExpressCheckoutPayment',
				$request);
		} catch (Payment_PayPal_SOAP_ErrorException $e) {
			// ignore warnings
			if ($e->getSeverity() === Payment_PayPal_SOAP::ERROR_WARNING) {
				$response = $e->getResponse();
			} else {
				throw $e;
			}
		}

		$details = $response->DoExpressCheckoutPaymentResponseDetails;

		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->transaction_type = $transaction_type;
		$transaction->transaction_id = $details->PaymentInfo->TransactionID;

		return array(
			'transaction' => $transaction,
			'details'     => $details,
		);
	}

	// }}}

	// convenience methods
	// {{{ public static function getExceptionMessageId()

	/**
	 * Get a formatted message from a Payment_PayPal_SOAP_ErrorException
	 *
	 * @param Payment_PayPal_SOAP_ErrorException $e The payment exception
	 *
	 * @return string The error message id to lookup a error message to display
	 * @see StoreCheckoutConfirmationPage::getErrorMessage()
	 */
	public static function getExceptionMessageId(
		Payment_PayPal_SOAP_ErrorException $e)
	{
		switch ($e->getCode()) {

		/*
		 * Invalid card number (4111 1111 1111 1111 for example)
		 */
		case 10759:
			return 'card-not-valid';

		/*
		 * CVV2 error. May be one of:
		 *
		 *  - CVV2 does not match
		 *  - CVV2 was not processed by PayPal
		 *  - CVV2 was not provided by merchant (us)
		 *  - CVV2 is not supported by card issuer
		 *  - No response from card issuer
		 */
		case 10725:
		case 15004:
			$cvv2_code = $e->getResponse()->CVV2Code;
			switch ($cvv2_code) {

			// CVV2 does not match
			case 'N':
				return 'card-verification-value';

			// Everything else gets a generic error message.
			case 'P':
			case 'S':
			case 'U':
			case 'X':
			default:
				return 'card-error';
			}

			break;

		/*
		 * AVS error. There are a number of possible errors as documented
		 * within.
		 */
		case 10505:
		case 10555:
			$avs_code = $e->getResponse()->AVSCode;
			switch ($avs_code) {

			// Postal code does not match.
			case 'A':
			case 'B':
				return 'postal-code-mismatch';

			// Either the whole address or the street address do not match.
			case 'C':
			case 'N':
			case 'P':
			case 'W':
			case 'Z':
				return 'address-mismatch';

			// Service unavailable or other errors. Generic error message.
			case 'E':
			case 'G':
			case 'I':
			case 'R':
			case 'S':
			case 'U':
			default:
				return 'card-error';
			}

			break;

		/*
		 * Express checkout shipping address did not pass PalPal's
		 * address verification check.
		 *
		 * PayPal checks the City/State/ZIP and fails if they don't match.
		 */
		case 10736:
			return 'paypal-address-error';

		/*
		 * Gateway declined. This happens when the issuing bank declines the
		 * transaction.
		 */
		case 10752:
			return 'card-error';

		/*
		 * Some kind of generic AVS rate limiting error. Who knows what the
		 * fuck this really means because it's not documented besides
		 * "Gateway Error". It happens occasionally though.
		 */
		case 10564:
			return 'card-error';

		/*
		 * ExpressCheckout session has expired. Checkout needs to be restarted.
		 */
		case 10411:
			return 'paypal-expired-token';
		}

		return null;
	}

	// }}}
	// {{{ public function formatNumber()

	/**
	 * @param double $value
	 *
	 * @return string formatted order total.
	 */
	public function formatNumber($value)
	{
		$value = SwatNumber::roundToEven($value, 2);
		return number_format($value, 2, '.', '');
	}

	// }}}
	// {{{ public function formatString()

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function formatString($string, $max_length = 0)
	{
		// convert to iso-8859-1
		$string = iconv('utf-8', 'ASCII//TRANSLIT', $string);

		// truncate to max_length
		if ($max_length > 0) {
			$string = mb_substr($string, 0, $max_length);
		}

		return $string;
	}

	// }}}
	// {{{ public function getCurrencyValue()

	/**
	 * @param double $value
	 *
	 * @return string formatted order total.
	 */
	public function getCurrencyValue($value, $currency)
	{
		return array(
			'_'          => $this->formatNumber($value),
			'currencyID' => $currency,
		);
	}

	// }}}

	// data-structure helper methods (express checkout)
	// {{{ protected function getSetExpressCheckoutRequest()

	protected function getSetExpressCheckoutRequest(array $parameters)
	{
		if (isset($parameters['PaymentDetails']['OrderTotal']) &&
			!is_array($parameters['PaymentDetails']['OrderTotal'])) {
			$parameters['PaymentDetails']['OrderTotal'] =
				$this->getCurrencyValue(
					$parameters['PaymentDetails']['OrderTotal'],
					$this->currency);
		}

		if (isset($parameters['PaymentDetails']['ShipToAddress']) &&
			$parameters['PaymentDetails']['ShipToAddress'] instanceof StoreOrderAddress) {
			$parameters['PaymentDetails']['ShipToAddress'] =
				$this->getAddress(
					$parameters['PaymentDetails']['ShipToAddress']);
		}

		return array(
			'SetExpressCheckoutRequest' => array(
				'Version' => '62.0',
				'SetExpressCheckoutRequestDetails' => $parameters,
			),
		);
	}

	// }}}
	// {{{ protected function getGetExpressCheckoutDetailsRequest()

	protected function getGetExpressCheckoutDetailsRequest($token)
	{
		return array(
			'GetExpressCheckoutDetailsRequest' => array(
				'Version' => '62.0',
				'Token'   => $token,
			),
		);
	}

	// }}}
	// {{{ protected function getDoExpressCheckoutPaymentRequest()

	protected function getDoExpressCheckoutPaymentRequest($token,
		$action, $payer_id, StoreOrder $order, $notify_url = '', $custom = '')
	{
		return array(
			'DoExpressCheckoutPaymentRequest' => array(
				'Version' => '62.0',
				'DoExpressCheckoutPaymentRequestDetails' =>
					$this->getDoExpressCheckoutPaymentRequestDetails($token,
						$action, $payer_id, $order, $notify_url, $custom),
			),
		);
	}

	// }}}
	// {{{ protected function getDoExpressCheckoutPaymentRequestDetails()

	protected function getDoExpressCheckoutPaymentRequestDetails($token,
		$action, $payer_id, StoreOrder $order, $notify_url = '', $custom = '')
	{
		$details = array();

		$details['Token']          = $token;
		$details['PaymentAction']  = $action;
		$details['PayerID']        = $payer_id;
		$details['PaymentDetails'] = $this->getPaymentDetails($order,
			$notify_url, $custom);

		return $details;
	}

	// }}}
	// {{{ protected function getStoreOrderPaymentMethod()

	protected function getStoreOrderPaymentMethod(MDB2_Driver_Common $db)
	{
		$class_name = SwatDBClassMap::get('StoreOrderPaymentMethod');
		$payment_method = new $class_name();

		$class_name = SwatDBClassMap::get('StorePaymentType');
		$payment_type = new $class_name();
		$payment_type->setDatabase($db);

		if ($payment_type->loadFromShortname('paypal')) {
			$payment_method->payment_type = $payment_type;
		}

		return $payment_method;
	}

	// }}}
	// {{{ protected function getStoreOrderAddress()

	protected function getStoreOrderAddress($address, MDB2_Driver_Common $db)
	{
		$class_name = SwatDBClassMap::get('StoreOrderAddress');
		$order_address = new $class_name();

		$order_address->fullname    = $address->Name;
		$order_address->line1       = $address->Street1;
		$order_address->city        = $address->CityName;
		$order_address->postal_code = $address->PostalCode;
		$order_address->country     = $address->Country;

		if ($address->Street2 != '') {
			$order_address->line2 = $address->Street2;
		}

		// PayPal sometimes returns an abbreviation and sometimes returns the
		// full title. Go figure.
		if ($address->StateOrProvince != '') {
			$class_name = SwatDBClassMap::get('StoreProvState');
			$provstate  = new $class_name();
			$provstate->setDatabase($db);
			if (strlen($address->StateOrProvince) === 2) {
				if ($provstate->loadFromAbbreviation($address->StateOrProvince,
					$address->Country)) {
					$order_address->provstate = $provstate;
				} else {
					$order_address->provstate_other = $address->StateOrProvince;
				}
			} else {
				if ($provstate->loadFromTitle($address->StateOrProvince,
					$address->Country)) {
					$order_address->provstate = $provstate;
				} else {
					$order_address->provstate_other = $address->StateOrProvince;
				}
			}
		}

		return $order_address;
	}

	// }}}
	// {{{ protected function getStoreFullname()

	protected function getStoreFullname($person_name)
	{
		$name = array();

		if ($person_name->Salutation != '') {
			$name[] = $person_name->Salutation;
		}
		if ($person_name->FirstName != '') {
			$name[] = $person_name->FirstName;
		}
		if ($person_name->MiddleName != '') {
			$name[] = $person_name->MiddleName;
		}
		if ($person_name->LastName != '') {
			$name[] = $person_name->LastName;
		}
		if ($person_name->Suffix != '') {
			$name[] = $person_name->Suffix;
		}

		$name = implode(' ', $name);

		return $name;
	}

	// }}}
	// {{{ protected function updateStoreOrderPaymentMethod()

	protected function updateStoreOrderPaymentMethod(
		StoreOrderPaymentMethod $payment_method, $token, $payer_info)
	{
		$payment_method->setPayPalToken($token);

		$fullname = $this->getStoreFullname($payer_info->PayerName);

		$payment_method->card_fullname = $fullname;
		$payment_method->payer_email   = $payer_info->Payer;
		$payment_method->payer_id      = $payer_info->PayerID;

		return $payment_method;
	}

	// }}}

	// data-structure helper methods (direct)
	// {{{ protected function getDoDirectPaymentRequest()

	protected function getDoDirectPaymentRequest(StoreOrder $order, $action,
		$card_number, $card_verification_value)
	{
		return array(
			'DoDirectPaymentRequest' => array(
				'Version' => '1.0',
				'DoDirectPaymentRequestDetails' =>
					$this->getDoDirectPaymentRequestDetails($order, $action,
						$card_number, $card_verification_value),
			),
		);
	}

	// }}}
	// {{{ protected function getDoDirectPaymentRequestDetails()

	protected function getDoDirectPaymentRequestDetails(StoreOrder $order,
		$action, $card_number, $card_verification_value)
	{
		$payment_method = $order->payment_methods->getFirst();

		$details = array();

		$details['PaymentAction']     = $action;
		$details['PaymentDetails']    = $this->getPaymentDetails($order);
		$details['CreditCard']        = $this->getCreditCardDetails($order,
			$payment_method, $card_number, $card_verification_value);

		$details['IPAddress']         = $this->getIpAddress();
		$details['MerchantSessionID'] = $this->getMerchantSessionId();

		return $details;
	}

	// }}}
	// {{{ protected function getCreditCardDetails()

	protected function getCreditCardDetails(StoreOrder $order,
		StoreOrderPaymentMethod $payment_method, $card_number,
			$card_verification_value)
	{
		$details = array();

		$details['CardOwner'] = $this->getPayerInfo($order, $payment_method);

		$details['CreditCardType'] = $this->getCreditCardType($payment_method);

		$details['CreditCardNumber'] = $card_number;

		$details['ExpMonth'] = $payment_method->card_expiry->format('%m');
		$details['ExpYear']  = $payment_method->card_expiry->format('%Y');
		$details['CVV2']     = $card_verification_value;

		if ($payment_method->card_inception !== null) {
			$details['StartMonth'] =
				$payment_method->card_inception->format('%m');

			$details['StartYear'] =
				$payment_method->card_inception->format('%Y');
		}

		if ($payment_method->card_issue_number != '') {
			$details['IssueNumber'] = $payment_method->card_issue_number;
		}

		return $details;
	}

	// }}}
	// {{{ protected function getCreditCardType()

	protected function getCreditCardType(StoreOrderPaymentMethod $payment_method)
	{
		switch ($payment_method->card_type->shortname) {
		case 'amex':
			$type = 'Amex';
			break;

		case 'discover':
			$type = 'Discover';
			break;

		case 'mastercard':
			$type = 'MasterCard';
			break;

		case 'visa':
			$type = 'Visa';
			break;

		case 'switch':
			$type = 'Switch';
			break;

		case 'solo':
			$type = 'Solo';
			break;

		default:
			throw new StorePaymentException('Unsupported card type in order.');
		}

		return $type;
	}

	// }}}
	// {{{ protected function getPayerInfo()

	protected function getPayerInfo(StoreOrder $order,
		StorePaymentMethod $payment_method)
	{
		$details = array();

		if ($order->email != '') {
			$details['Payer'] = $order->email;
		}

		$details['PayerName'] = $this->getPersonName($payment_method);

		$details['PayerCountry'] =
			$order->billing_address->getInternalValue('country');

		$details['Address'] = $this->getPayerInfoAddress($order);

		return $details;
	}

	// }}}
	// {{{ protected function getPayerInfoAddress()

	protected function getPayerInfoAddress(StoreOrder $order)
	{
		return $this->getAddress($order->billing_address);
	}

	// }}}
	// {{{ protected function getPersonName()

	protected function getPersonName(StoreOrderPaymentMethod $payment_method)
	{
		$fullname = $payment_method->card_fullname;

		$midpoint = intval(floor(strlen($fullname) / 2));

		// get space closest to the middle of the string
		$left_pos  = strrpos($fullname, ' ', -strlen($fullname) + $midpoint);
		$right_pos = strpos($fullname, ' ', $midpoint);

		if ($left_pos === false && $right_pos === false) {
			// There is no first and last name division, just split string for
			// PayPal's sake.
			$pos = $midpoint;
		} elseif ($left_pos === false) {
			$pos = $right_pos;
		} elseif ($right_pos === false) {
			$pos = $left_pos;
		} elseif (($midpoint - $left_pos) <= ($right_pos - $midpoint)) {
			$pos = $left_pos;
		} else {
			$pos = $right_pos;
		}

		// split name into first and last parts in roughly the middle
		if ($pos === false) {
			$first_name = substr($fullname, 0, $midpoint);
			$last_name  = substr($fullname, $midpoint);
		} else {
			$first_name = substr($fullname, 0, $pos);
			$last_name  = substr($fullname, $pos + 1);
		}

		$details = array();

		$details['FirstName'] = $this->formatString($first_name, 25);
		$details['LastName'] = $this->formatString($last_name, 25);

		return $details;
	}

	// }}}

	// data-structure helper methods (shared)
	// {{{ protected function getPaymentDetails()

	protected function getPaymentDetails(StoreOrder $order,
		$notify_url = '', $custom = '')
	{
		$details = array();

		$details['OrderTotal'] =
			$this->getCurrencyValue($order->total, $this->currency);

		$description = $order->getDescription();
		$description = $this->formatString($description, 127);
		if ($description != '') {
			$details['OrderDescription'] = $description;
		}

		$details['ItemTotal'] =
			$this->getCurrencyValue($order->item_total, $this->currency);

		$details['ShippingTotal'] =
			$this->getCurrencyValue($order->shipping_total, $this->currency);

		$details['HandlingTotal'] =
			$this->getCurrencyValue($order->surcharge_total, $this->currency);

		$details['TaxTotal'] =
			$this->getCurrencyValue($order->tax_total, $this->currency);

		if ($order->id !== null) {
			$details['InvoiceID'] = $order->id;
		}

		if ($order->shipping_address->getInternalValue('country') !== null) {
			$details['ShipToAddress'] = $this->getShipToAddress($order);
		}

		if ($notify_url != '') {
			$details['Notify_URL'] = $this->formatString($notify_url, 2048);
		}

		if ($custom != '') {
			$details['Custom'] = $this->formatString($custom, 256);
		}

		$details['PaymentDetailsItem'] = $this->getPaymentDetailsItems($order);

		return $details;
	}

	// }}}
	// {{{ protected function getPaymentDetailsItems()

	protected function getPaymentDetailsItems(StoreOrder $order)
	{
		$details = array();

		foreach ($order->items as $item) {
			$details[] = $this->getPaymentDetailsItem($item);
		}

		return $details;
	}

	// }}}
	// {{{ protected function getPaymentDetailsItem()

	protected function getPaymentDetailsItem(StoreOrderItem $item)
	{
		$details = array();

		$name = $item->product_title;
		$description = strip_tags($item->getDescription());
		if ($description != '') {
			$name.= ' - '.$description;
		}

		$details['Name'] = $this->formatString($name, 127);

		if ($item->sku != '') {
			$details['Number'] = $item->sku;
		}

		$details['Amount'] = $this->getCurrencyValue($item->price,
			$this->currency);

		$details['Quantity'] = $item->quantity;

		return $details;
	}

	// }}}
	// {{{ protected function getShipToAddress()

	protected function getShipToAddress(StoreOrder $order)
	{
		return $this->getAddress($order->shipping_address);
	}

	// }}}
	// {{{ protected function getAddress()

	protected function getAddress(StoreOrderAddress $address)
	{
		$details = array();

		$details['Name']    = $this->formatString($address->fullname, 32);
		$details['Street1'] = $this->formatString($address->line1, 100);

		if ($address->line2 != '') {
			$details['Street2'] = $this->formatString($address->line2, 100);
		}

		$details['CityName'] = $this->formatString($address->city, 40);

		if ($address->getInternalValue('provstate') !== null) {
			$details['StateOrProvince'] = $address->provstate->abbreviation;
		} else {
			$details['StateOrProvince'] = $this->formatString(
				$address->provstate_other, 40);
		}

		$details['PostalCode'] = $this->formatString($address->postal_code, 20);
		$details['Country']    = $address->getInternalValue('country');

		if ($address->phone != '') {
			$details['Phone'] = $this->formatString($address->phone, 20);
		}

		return $details;
	}

	// }}}

	// general helper methods
	// {{{ protected function getMerchantSessionId()

	protected function getMerchantSessionId()
	{
		// Note: PayPal's documentation states this should only contain
		// numeric characters, however the conversion to base-10 from base-64
		// is not easy in PHP. Numerous examples online use alphanumeric
		// characters in this field.
		return session_id();
	}

	// }}}
	// {{{ protected function getIpAddress()

	protected function getIpAddress()
	{
		return $_SERVER['REMOTE_ADDR'];
	}

	// }}}
}
