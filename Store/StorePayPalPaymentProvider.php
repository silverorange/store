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
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
 */
class StorePayPalPaymentProvider extends StorePaymentProvider
{
	// {{{ class constants

	const EXPRESS_CHECKOUT_URL_LIVE =
		'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';

	const EXPRESS_CHECKOUT_URL_SANDBOX =
		'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';

	// }}}
	// {{{ private properties

	/**
	 * PayPal SOAP client
	 *
	 * @var Payment_PayPal_SOAP
	 * @see StoreProtxPaymentProvider::__construct()
	 */
	private $client;

	/**
	 * The currency to use for transactions
	 *
	 * @var string
	 * @see StorePayPalPaymentProvider::__construct()
	 */
	private $currency;

	/**
	 * @var string
	 * @see StorePayPalPaymentProvider::__construct()
	 */
	private $mode;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment provider using the PayPal SOAP API
	 *
	 * Available parameters are:
	 *
	 * <kbd>mode</kbd>      - optional. Transaction mode to use. Muse be one of
	 *                        either 'live' or 'sandbox'. If not specified,
	 *                        'sandbox' is used.
	 * <kbd>username</kbd>  - required. Username for PayPal authentication.
	 * <kbd>password</kbd>  - required. Password for PayPal authentication.
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

		$response = $this->client->call('DoDirectPayment', $request);

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

		$response = $this->client->call('DoDirectPayment', $request);

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
	 * @param array $parameters array of name-value pairs. Required parameters
	 *                           are 'OrderTotal', 'ReturnURL' and 'CancelURL'.
	 *
	 * @return string the token of the transaction.
	 *
	 * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
	 * @see StorePayPalPaymentProvider::doExpressCheckout()
	 */
	public function setExpressCheckout(array $parameters)
	{
		$required_parameters = array('OrderTotal', 'ReturnURL', 'CancelURL');
		foreach ($required_parameters as $name) {
			if (!array_key_exists($name, $parameters)) {
				throw new StoreException('Required setExpressCheckout() '.
					'parameter "'.$name.'" is missing.');
			}
		}

		$request  = $this->getSetExpressCheckoutRequest($parameters);
		$response = $this->client->call('SetExpressCheckout', $request);

		return $response->Token;
	}

	// }}}
	// {{{ public function getExpressCheckoutUri()

	/**
	 * Gets the URI for PayPal's Express Checkout
	 *
	 * Site code should relocate to this URI.
	 *
	 * @param string $token the token of the current transaction.
	 *
	 * @return string the URI to which the browser should be relocated to
	 *                 continue the Express Checkout transaction.
	 *
	 * @see StorePayPalPaymentProvider::setExpressCheckout()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
	 * @see StorePayPalPaymentProvider::doExpressCheckout()
	 */
	public function getExpressCheckoutUri($token)
	{
		if ($this->mode === 'live') {
			$uri = self::EXPRESS_CHECKOUT_URL_LIVE.urlencode($token);
		} else {
			$uri = self::EXPRESS_CHECKOUT_URL_SANDBOX.urlencode($token);
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
		$request  = $this->getGetExpressCheckoutDetailsRequest($token);
		$response = $this->client->call('GetExpressCheckoutDetails', $request);
		$details  = $response->GetExpressCheckoutDetailsResponseDetails;

		$payment_method = $this->getStoreOrderPaymentMethod(
			$details->PayerInfo, $db);

		// Note: When multiple payment methods are added, this code will
		// need updating.
		$class_name = SwatDBClassMap::get('StoreOrderPaymentMethodWrapper');
		$order->payment_methods = new $class_name();
		$order->payment_methods->add($payment_method);

		if (isset($details->PayerInfo->Address->Country)) {
			$shipping_address = $this->getStoreOrderAddress(
				$details->PayerInfo->Address, $db);

			// Only set address if it is not already set or if it is not the
			// same as the existing billing address.
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
	 *
	 * @return StorePaymentMethodTransaction the transaction object for the
	 *                                        payment. This object contains the
	 *                                        transaction date and identifier.
	 *
	 * @see StorePayPalPaymentProvider::setExpressCheckout()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutDetails()
	 * @see StorePayPalPaymentProvider::getExpressCheckoutUri()
	 */
	public function doExpressCheckout($token, $action,
		$payer_id, StoreOrder $order)
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

		$response = $this->client->call('DoExpressCheckoutPayment', $request);
		$details  = $response->DoExpressCheckoutPaymentResponseDetails;

		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->transaction_type = $transaction_type;
		$transaction->transaction_id = $details->PaymentInfo->TransactionID;

		return $transaction;
	}

	// }}}

	// data-structure helper methods (express checkout)
	// {{{ private function getSetExpressCheckoutRequest()

	private function getSetExpressCheckoutRequest(array $parameters)
	{
		if (array_key_exists('OrderTotal', $parameters) &&
			!is_array($parameters['OrderTotal'])) {
			$parameters['OrderTotal'] = $this->getCurrencyValue(
				$parameters['OrderTotal'], $this->currency);
		}

		if (array_key_exists('Address', $parameters) &&
			$parameters['Address'] instanceof StoreOrderAddress) {
			$parameters['Address'] = $this->getAddress($parameters['Address']);
		}

		return array(
			'SetExpressCheckoutRequest' => array(
				'Version' => '1.0',
				'SetExpressCheckoutRequestDetails' => $parameters,
			),
		);
	}

	// }}}
	// {{{ private function getGetExpressCheckoutDetailsRequest()

	private function getGetExpressCheckoutDetailsRequest($token)
	{
		return array(
			'GetExpressCheckoutDetailsRequest' => array(
				'Version' => '1.0',
				'Token'   => $token,
			),
		);
	}

	// }}}
	// {{{ private function getDoExpressCheckoutPaymentRequest()

	private function getDoExpressCheckoutPaymentRequest($token,
		$action, $payer_id, StoreOrder $order)
	{
		return array(
			'DoExpressCheckoutPaymentRequest' => array(
				'Version' => '1.0',
				'DoExpressCheckoutPaymentRequestDetails' =>
					$this->getDoExpressCheckoutPaymentRequestDetails($token,
						$action, $payer_id, $order),
			),
		);
	}

	// }}}
	// {{{ private function getDoExpressCheckoutPaymentRequestDetails()

	private function getDoExpressCheckoutPaymentRequestDetails($token,
		$action, $payer_id, StoreOrder $order)
	{
		$details = array();

		$details['Token']          = $token;
		$details['PaymentAction']  = $action;
		$details['PayerID']        = $payer_id;
		$details['PaymentDetails'] = $this->getPaymentDetails($order);

		return $details;
	}

	// }}}
	// {{{ private function getStoreOrderPaymentMethod()

	private function getStoreOrderPaymentMethod($payer_info,
		MDB2_Driver_Common $db)
	{
		$class_name = SwatDBClassMap::get('StoreOrderPaymentMethod');
		$payment_method = new $class_name();

		$fullname = $this->getStoreFullname($payer_info->PayerName);

		$payment_method->card_fullname = $fullname;
		$payment_method->payer_email   = $payer_info->Payer;
		$payment_method->payer_id      = $payer_info->PayerID;

		$class_name = SwatDBClassMap::get('StorePaymentType');
		$payment_type = new $class_name();
		$payment_type->setDatabase($db);

		if ($payment_type->loadFromShortname('paypal')) {
			$payment_method->payment_type = $payment_type;
		}

		return $payment_method;
	}

	// }}}
	// {{{ private function getStoreOrderAddress()

	private function getStoreOrderAddress($address, MDB2_Driver_Common $db)
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
	// {{{ private function getStoreFullname()

	private function getStoreFullname($person_name)
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

	// data-structure helper methods (direct)
	// {{{ private function getDoDirectPaymentRequest()

	private function getDoDirectPaymentRequest(StoreOrder $order, $action,
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
	// {{{ private function getDoDirectPaymentRequestDetails()

	private function getDoDirectPaymentRequestDetails(StoreOrder $order,
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
	// {{{ private function getCreditCardDetails()

	private function getCreditCardDetails(StoreOrder $order,
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
	// {{{ private function getCreditCardType()

	private function getCreditCardType(StoreOrderPaymentMethod $payment_method)
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
	// {{{ private function getPayerInfo()

	private function getPayerInfo(StoreOrder $order,
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
	// {{{ private function getPayerInfoAddress()

	private function getPayerInfoAddress(StoreOrder $order)
	{
		return $this->getAddress($order->billing_address);
	}

	// }}}
	// {{{ private function getPersonName()

	private function getPersonName(StoreOrderPaymentMethod $payment_method)
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
	// {{{ private function getPaymentDetails()

	private function getPaymentDetails(StoreOrder $order)
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

		$details['ShipToAddress']      = $this->getShipToAddress($order);
		$details['PaymentDetailsItem'] = $this->getPaymentDetailsItems($order);

		return $details;
	}

	// }}}
	// {{{ private function getPaymentDetailsItems()

	private function getPaymentDetailsItems(StoreOrder $order)
	{
		$details = array();

		foreach ($order->items as $item) {
			$details[] = $this->getPaymentDetailsItem($item);
		}

		return $details;
	}

	// }}}
	// {{{ private function getPaymentDetailsItem()

	private function getPaymentDetailsItem(StoreOrderItem $item)
	{
		$details = array();

		$name = $item->product_title;
		$description = strip_tags($item->getDescription());
		if ($description != '') {
			$name.= ' - '.$description;
		}

		$details['Name'] = $this->formatString($name, 127);

		if ($item->sku != '') {
			$details['Number']   = $item->sku;
		}

		$details['Amount'] = $this->getCurrencyValue($item->price,
			$this->currency);

		$details['Quantity'] = $item->quantity;

		return $details;
	}

	// }}}
	// {{{ private function getShipToAddress()

	private function getShipToAddress(StoreOrder $order)
	{
		return $this->getAddress($order->shipping_address);
	}

	// }}}
	// {{{ private function getAddress()

	private function getAddress(StoreOrderAddress $address)
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
	// {{{ private function getCurrencyValue()

	/**
	 * @param double $value
	 *
	 * @return string formatted order total.
	 */
	private function getCurrencyValue($value, $currency)
	{
		return array(
			'_'          => $this->formatNumber($value),
			'currencyID' => $currency,
		);
	}

	// }}}

	// general helper methods
	// {{{ private function formatNumber()

	/**
	 * @param double $value
	 *
	 * @return string formatted order total.
	 */
	private function formatNumber($value)
	{
		$value = SwatNumber::roundToEven($value, 2);
		return number_format($value, 2, '.', '');
	}

	// }}}
	// {{{ private function formatString()

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	private function formatString($string, $max_length = 0)
	{
		// convert to iso-8859-1
		$string = iconv('utf-8', 'iso-8859-1//TRANSLIT', $string);

		// truncate to max_length
		if ($max_length > 0) {
			$string = mb_substr($string, 0, $max_length);
		}

		return $string;
	}

	// }}}
	// {{{ private function getMerchantSessionId()

	private function getMerchantSessionId()
	{
		// Note: PayPal's documentation states this should only contain
		// numeric characters, however the conversion to base-10 from base-64
		// is not easy in PHP. Numerous examples online use alphanumeric
		// characters in this field.
		return session_id();
	}

	// }}}
	// {{{ private function getIpAddress()

	private function getIpAddress()
	{
		return $_SERVER['REMOTE_ADDR'];
	}

	// }}}
}
