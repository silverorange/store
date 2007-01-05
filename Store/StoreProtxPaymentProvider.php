<?php

require_once 'Store/StorePaymentProvider.php';
require_once 'Store/StoreProtxPaymentRequest.php';
require_once 'Store/dataobjects/StoreOrder.php';
require_once 'Store/exceptions/StoreException.php';

class StoreProtxPaymentProvider extends StorePaymentProvider
{
	// {{{ private properties

	/**
	 * The vendor login name for VSP direct
	 *
	 * @var string
	 * @see StoreProtxPaymentProvider::__construct()
	 */
	private $vendor;

	/**
	 * The currency to use for transactions
	 *
	 * @var string
	 * @see StoreProtxPaymentProvider::__construct()
	 */
	private $currency;

	/**
	 * The transaction mode to use for transactions
	 *
	 * Must be one of 'live', 'test', 'simulator'. Defaults to 'simulator'.
	 *
	 * @var string
	 * @see StoreProtxPaymentProvider::__construct()
	 */
	private $mode;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment provider using the Protx VSP Direct protocol
	 *
	 * Valid parameters are:
	 *
	 * Mode:     Optional. Transaction mode. One of either 'live', 'test' or
	 *           'simulator'.
	 * Vendor:   Required. The vendor login name as provided by Protx.
	 * Currency: Required. The currency in which to perform transactions.
	 *
	 * @throws StoreException if a required parameter is missing or if the
	 *                        'Mode' paramater is not valid.
	 */
	public function __construct(array $parameters = array())
	{
		if (!isset($parameters['Vendor']))
			throw new StoreException('"Vendor" is required in the Protx '.
				'payment provider parameters.');

		if (!isset($parameters['Currency']))
			throw new StoreException('"Currency" is required in the Protx '.
				'payment provider parameters.');

		if (isset($parameters['Mode']))
			$this->mode = (string)$parameters['Mode'];

		$this->vendor = (string)$parameters['Vendor'];
		$this->currency = (string)$parameters['Currency'];
	}

	// }}}

	public function pay(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_PAY, $this->mode);

		$fields = $this->getOrderPaymentFields($order);
		$request->setFields($fields);
		$request->setField('CardNumber', $card_number);
		if ($card_verification_value !== null)
			$request->setField('CV2', $card_verification_value);

		$response = $request->process();
		$this->checkResponse($response);

	// TODO: do something here
		echo $response;
	}

	public function release(StoreOrder $order) 
	{
	}

	public function hold(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_HOLD, $this->mode);

		$fields = $this->getOrderPaymentFields($order);
		$request->setFields($fields);
		$request->setField('CardNumber', $card_number);
		if ($card_verification_value !== null)
			$request->setField('CV2', $card_verification_value);

		$response = $request->process();
		$this->checkResponse($response);

	// TODO: do something here
	}

	public function authorize(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_AUTHORIZE, $this->mode);

		$fields = $this->getOrderPaymentFields($order);
		$request->setFields($fields);
		$request->setField('CardNumber', $card_number);
		if ($card_verification_value !== null)
			$request->setField('CV2', $card_verification_value);

		$response = $request->process();
		$this->checkResponse($response);

	// TODO: do something here
	}

	public function refund(StoreOrder $order, $amount = null)
	{
	}

	public function authorizedPay(StoreOrder $order)
	{
	}

	public function void(StoreOrder $order)
	{
	}
	// {{{ private function getOrderRequiredFields()

	/**
	 * Gets fields required for making all transaction types
	 *
	 * @param StoreOrder $order the order to get fields from.
	 *
	 * @return array an array or key-value pairs containing VSP direct fields
	 *                used for all transaction types for an order.
	 */
	private function getOrderRequiredFields(StoreOrder $order)
	{
		if ($order->id === null) {
			throw new StoreException('Order requires an id to make Protx '.
				'payments. Call the save() method on the order object before '.
				'making payment.');
		}

		if (strlen($order->id) > 40) {
			throw new StoreException('Order id is too high to make Protx '.
				'payment.');
		}

		$fields = array(
			'Vendor'       => $this->vendor,
			'VendorTxCode' => (string)$order->id,
		);

		return $fields;
	}

	// }}}
	// {{{ private function getOrderPaymentFields()

	/**
	 * Gets fields required for making payments from an order object
	 *
	 * @param StoreOrder $order the order to get payment fields from.
	 *
	 * @return array an array or key-value pairs containing VSP direct fields
	 *                used for payment processing for an order.
	 *
	 * @see StoreProtxPaymentProvider::getOrderRequiredFields()
	 */
	private function getOrderPaymentFields(StoreOrder $order)
	{
		$fields = $this->getOrderRequiredFields($order);

		if ($order->total > 100000) {
			throw new StoreException('Protx payments can only be made for '.
				'orders with total values of 100,000 or less.');
		}

		$payment_method = $order->payment_method;
		$amount = SwatString::numberFormat($order->total, 2, null, false);
		$card_holder = substr($payment_method->credit_card_fullname, 0, 50);
		$card_number = substr($payment_method->credit_card_number, 0, 20);
		$card_expiry = $payment_method->credit_card_expiry->format('%m%y');
		$description = substr($order->getDescription(), 0, 100);

		$payment_type_map = $this->getPaymentTypeMap();
		if (array_key_exists($payment_method->payment_type->shortname,
			$payment_type_map)) {
			$card_type =
				$payment_type_map[$payment_method->payment_type->shortname];
		} else {
			throw new StoreException('Unsupported card type in order.');
		}

		$payment_fields = array(
			'Amount'       => $amount,
			'CardHolder'   => $card_holder,
			'CardNumber'   => $card_number,
			'ExpiryDate'   => $card_expiry,
			'CardType'     => $card_type,
			'ExpiryDate'   => $card_expiry,
			'Currency'     => $this->currency,
			'Description'  => $description,
		);

		$fields = array_merge($fields, $payment_fields);

		// Start date is required for Solo, Switch and Amex
		if (in_array($card_type, array('SWITCH', 'SOLO', 'AMEX'))) {
			$card_inception = $payment_method->card_inception->format('%m%y');
			$fields['StartDate'] = $card_inception;
		}

		// Issue number is required for Solo and Switch
		if (in_array($card_type, array('SWITCH', 'SOLO'))) {
			$fields['IssueNumber'] = $payment_method->card_issue_number;
		}

		return $fields;
	}

	// }}}
	// {{{ private function checkResponse()

	/**
	 * Checks a StoreProtxPaymentResponse object for errors and if errors are
	 * found, throws an appropriate exception
	 *
	 * @param StoreProtxPaymentResponse $response the response object to check.
	 *
	 * @throws StorePaymentMalformedException if the request was missing fields
	 *                                         or was badly formatted.
	 * @throws StorePaymentInvalidException if the request contained incorrect
	 *                                       fields.
	 * @throws StorePaymentErrorException if the payment server encountered an
	 *                                     error processing the request.
	 * @throws StorePaymentNotAuthorizedException if the request was not
	 *                                             authorized by the acquiring
	 *                                             bank.
	 * @throws StorePaymentRejectedException if the request was rejected based
	 *                                        on rules configured in the Protx
	 *                                        merchent account.
	 */
	private function checkResponse(StoreProtxPaymentResponse $response)
	{
		$status = $response->getField('Status');
		$status_detail = $response->getField('StatusDetail');

		switch ($status) {
		case 'MALFORMED':
			require_once 'Store/exceptions/StorePaymentMalformedException.php';
			throw new StorePaymentMalformedException($status_detail);
			break;

		case 'INVALID':
			require_once 'Store/exceptions/StorePaymentInvalidException.php';
			throw new StorePaymentInvalidException($status_detail);
			break;

		case 'ERROR':
			require_once 'Store/exceptions/StorePaymentErrorException.php';
			throw new StorePaymentErrorException($status_detail);
			break;

		case 'NOTAUTHED':
			require_once
				'Store/exceptions/StorePaymentNotAuthorizedException.php';

			throw new StorePaymentNotAuthorizedException($status_detail);
			break;

		case 'REJECTED':
			require_once 'Store/exceptions/StorePaymentRejectedException.php';
			throw new StorePaymentRejectedException($status_detail);
			break;
		}
	}

	// }}}
	// {{{ private function getPaymentTypeMap()

	/**
	 * Gets a key-value array mapping StorePaymentType shortnames to Protx
	 * CardType values
	 *
	 * The payment type map is taken from page 35 of the VSP Direct Integration
	 * Guidelines.
	 *
	 * @return array a key-value array mapping StorePaymentType shortnames to
	 *                Protx CardType values.
	 *
	 * @see StorePaymentType
	 */
	private function getPaymentTypeMap()
	{
		static $type_map = array(
			'visa'       => 'VISA',
			'mastercard' => 'MC',
			'delta'      => 'DELTA',
			'solo'       => 'SOLO',
			'switch'     => 'SWITCH',
			'electron'   => 'UKE',
			'amex'       => 'AMEX',
			'dinersclub' => 'DC',
			'jcb'        => 'JCB',
		);

		return $type_map;
	}

	// }}}
}

?>
