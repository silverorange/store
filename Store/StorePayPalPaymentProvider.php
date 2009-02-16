<?php

require_once 'Store/StorePaymentProvider.php';
require_once 'Swat/SwatNumber.php';
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
 * @see       StorePaymentTransaction
 */
class StorePayPalPaymentProvider extends StorePaymentProvider
{
	// {{{ private properties

	/**
	 * PayPal SOAP client
	 *
	 * @var Payment_PayPal_SOAP_Client
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

		if (isset($parameters['mode'])) {
			$valid_modes = array('live', 'sandbox');
			if (!in_array($parameters['mode'], $valid_modes)) {
				throw new StoreException('Mode "'.$mode.'" is not valid for '.
					'the PayPal payment provider.');
			}

			$options['mode'] = $parameters['mode'];
		}

		$this->client = new Payment_PayPal_SOAP_Client($options);
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
	 * @return StorePaymentTransaction the transaction object for the payment.
	 *                                  this object contains information such
	 *                                  as the transaction identifier and
	 *                                  Address Verification Service (AVS)
	 *                                  results.
	 */
	public function pay(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = $this->getDoDirectPaymentRequest($order, 'Sale',
			$card_number, $card_verification_value);

		Swat::printObject($request);
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
	 * @return StorePaymentTransaction the transaction object for the payment.
	 *                                  this object contains information such
	 *                                  as the transaction identifier and
	 *                                  Address Verification Service (AVS)
	 *                                  results.
	 *
	 * @sensitive $card_number
	 */
	public function hold(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = $this->getDoDirectPaymentRequest($order, 'Authorization',
			$card_number, $card_verification_value);

		$transaction = $this->getPaymentTransaction($response, $order->id,
			StorePaymentRequest::TYPE_HOLD);

		return $transaction;
	}

	// }}}

	// data-structure helper methods
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

		$description = strip_tags($item->getDescription());
		$description = $this->formatString($description, 127);

		if ($description != '') {
			$details['Name'] = $description;
		}

		if ($item->sku != '') {
			$details['Number']   = $item->sku;
		}

		$details['Amount']   = $this->getCurrencyValue($item->price);
		$detauls['Quantity'] = $item->quantity;

		return $details;
	}

	// }}}
	// {{{ private function getShipToAddress()

	private function getShipToAddress(StoreOrder $order)
	{
		return $this->getAddress($order->shipping_address);
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
	// {{{ private function getAddress()

	private function getAddress(StoreOrderAddress $address)
	{
		$details = array();

		$details['Name']    = $this->formatString($address->fullname, 32);
		$details['Street1'] = $this->formatString($address->line1, 100);

		if ($address->line2 != '') {
			$details['Street2'] = $this->formatString($address->line2, 100);
		}

		$details['City'] = $this->formatString($address->city, 40);

		if ($address->getInternalValue('provstate') !== null) {
			$details['StateOrProvince'] = $address->provstate->shortname;
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
	// {{{ private function getPersonName()

	private function getPersonName(StoreOrderPaymentMethod $payment_method)
	{
		$fullname = $payment_method->card_fullname;

		// split name into first and last parts in roughly the middle
		$fullname_exp = explode(' ', $fullname);
		$midpoint     = floor(count($fullname_exp) / 2) + 1;
		$first_name   = '';
		$last_name    = '';
		for ($i = 0 ; $i < count($fullname_exp); $i++) {
			if ($i < $midpoint) {
				$first_name.= $fullname_exp[$i];
			} else {
				$last_name.= $fullname_exp[$i];
			}
		}

		$details = array();

		$details['FirstName'] = $this->formatString($first_name, 25);
		$details['LastName'] = $this->formatString($last_name, 25);

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
