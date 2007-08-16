<?php

require_once 'Swat/exceptions/SwatException.php';

require_once 'Store/StorePaymentProvider.php';
require_once 'Store/StoreProtxPaymentRequest.php';
require_once 'Store/dataobjects/StorePaymentTransaction.php';
require_once 'Store/dataobjects/StoreOrder.php';
require_once 'Store/exceptions/StoreException.php';

require_once 'Store/exceptions/StorePaymentMalformedException.php';
require_once 'Store/exceptions/StorePaymentInvalidException.php';
require_once 'Store/exceptions/StorePaymentErrorException.php';
require_once 'Store/exceptions/StorePaymentNotAuthorizedException.php';
require_once 'Store/exceptions/StorePaymentRejectedException.php';

require_once 'Store/exceptions/StorePaymentAddressException.php';
require_once 'Store/exceptions/StorePaymentCardTypeException.php';
require_once 'Store/exceptions/StorePaymentCvvException.php';

/**
 * Payment provider driver for Protx VSP Direct payments
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider
 */
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
	// {{{ public function pay()

	/**
	 * Pay for an order immediately
	 *
	 * @param StoreOrder $order the order to pay for.
	 * @param string $card_number the card number to use for payment.
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
	 */
	public function pay(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		SwatException::addSensitiveParameter(
			'card_number', __FUNCTION__, __CLASS__);

		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_PAY, $this->mode);

		$payment_fields = $this->getOrderPaymentFields($order);
		$request->setFields($payment_fields);

		$card_fields = $this->getCardFields($order, $card_number,
			$card_verification_value);

		$request->setFields($card_fields);

		$response = $request->process();
		$this->checkResponse($response);

		$transaction = $this->getPaymentTransaction($response, $order->id,
			StorePaymentRequest::TYPE_PAY);

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
	 * @return StorePaymentTransaction the transaction object for the payment.
	 *                                  this object contains information such
	 *                                  as the transaction identifier and
	 *                                  Address Verification Service (AVS)
	 *                                  results.
	 */
	public function hold(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		SwatException::addSensitiveParameter(
			'card_number', __FUNCTION__, __CLASS__);

		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_HOLD, $this->mode);

		$payment_fields = $this->getOrderPaymentFields($order);
		$request->setFields($payment_fields);

		$card_fields = $this->getCardFields($order, $card_number,
			$card_verification_value);

		$request->setFields($card_fields);

		$response = $request->process();
		$this->checkResponse($response);

		$transaction = $this->getPaymentTransaction($response, $order->id,
			StorePaymentRequest::TYPE_HOLD);

		return $transaction;
	}

	// }}}
	// {{{ public function release()

	/**
	 * Release funds held for an order payment
	 *
	 * If this method does not throw an exception, the release was successful.
	 *
	 * @param StorePaymentTransaction $transaction the tranaction used to place
	 *                                              a hold on the funds. This
	 *                                              should be a transaction
	 *                                              returned by
	 *                                              {@link StorePaymentProvider::hold()}.
	 *
	 * @return StorePaymentTransaction a transaction object representing the
	 *                                  released transaction.
	 */
	public function release(StorePaymentTransaction $transaction)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_RELEASE, $this->mode);

		$fields = array(
			'Vendor'       => $this->vendor,
			'VendorTxCode' => $transaction->getInternalValue('ordernum'),
			'VPSTxId'      => $transaction->transaction_id,
			'SecurityKey'  => $transaction->security_key,
			'TxAuthNo'     => $transaction->authorization_code,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);

		$release_transaction = new StorePaymentTransaction();
		$release_transaction->createdate = new SwatDate();
		$release_transaction->createdate->toUTC();
		$release_transaction->ordernum =
			$transaction->getInternalValue('ordernum');

		$release_transaction->request_type = StorePaymentRequest::TYPE_RELEASE;
		$release_transaction->transaction_id = $transaction->transaction_id;

		return $release_transaction;
	}

	// }}}
	// {{{ public function abort()

	/**
	 * Abort a hold on funds held for an order payment
	 *
	 * Call this method if you have a transaction from a previous call to
	 * {@link StorePaymentProvider::hold()} that you would like to cancel.
	 *
	 * If this method does not throw an exception, the about was successful.
	 *
	 * @param StorePaymentTransaction $transaction the tranaction used to place
	 *                                              a hold on the funds. This
	 *                                              should be a transaction
	 *                                              returned by
	 *                                              {@link StorePaymentProvider::hold()}.
	 *
	 * @return StorePaymentTransaction a transaction object representing the
	 *                                  aborted transaction.
	 */
	public function abort(StorePaymentTransaction $transaction)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_ABORT, $this->mode);

		$fields = array(
			'Vendor'       => $this->vendor,
			'VendorTxCode' => $transaction->getInternalValue('ordernum'),
			'VPSTxId'      => $transaction->transaction_id,
			'SecurityKey'  => $transaction->security_key,
			'TxAuthNo'     => $transaction->authorization_code,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);

		$abort_transaction = new StorePaymentTransaction();
		$abort_transaction->createdate = new SwatDate();
		$abort_transaction->createdate->toUTC();
		$abort_transaction->ordernum =
			$transaction->getInternalValue('ordernum');

		$abort_transaction->request_type = StorePaymentRequest::TYPE_ABORT;
		$abort_transaction->transaction_id = $transaction->transaction_id;

		return $abort_transaction;
	}

	// }}}
	// {{{ public function refund()

	/**
	 * Refunds all or part of a transaction
	 *
	 * Refunds can only be made on transactions that have been settled by
	 * the merchant bank. If the transaction has not yet been settled, you can
	 * perform call {@link StorePaymentProvider::void()} to cancel the
	 * original transaction without incurring merchant fees.
	 *
	 * @param StorePaymentTransaction the original transaction to refund.
	 * @param string $description optional. A description of why the refund is
	 *                             being made. If not specified, a blank string
	 *                             is used.
	 * @param double $amount optional. The amount to refund. This amount cannot
	 *                        exceed the original transaction value. If not
	 *                        specified, the amount defaults to the total value
	 *                        of the order for the original transaction.
	 *
	 * @return StorePaymentTransaction a new transaction object representing
	 *                                  the refund transaction.
	 */
	public function refund(StorePaymentTransaction $transaction,
		$description = '', $amount = null)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_REFUND, $this->mode);

		// compose refund transaction id from order and original transaction
		$transaction_id = $transaction->order->id.'-'.$transaction->id;
		$amount = ($amount === null) ? $order->total : $amount; 
		if ($amount > 100000) {
			throw new StoreException('Protx refunds can only be made for '.
				'values of 100,000 or less.');
		}
		$amount = SwatString::numberFormat($amount, 2, null, false);
		$description = substr($description, 0, 100);

		$fields = array(
			'Vendor'              => $this->vendor,
			'VendorTxCode'        => $transaction_id,
			'Amount'              => $amount,
			'Currency'            => $this->currency,
			'Description'         => $description,
			'RelatedVPSTxId'      => $transaction->transaction_id,
			'RelatedVendorTxCode' => $transaction->ordernum->id,
			'RelatedSecurityKey'  => $transaction->security_key,
			'RelatedTxAuthNo'     => $transaction->authorization_code,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);

		$refund_transaction = new StorePaymentTransaction();
		$refund_transaction->createdate = new SwatDate();
		$refund_transaction->createdate->toUTC();
		$refund_transaction->ordernum = $transaction->order->id;
		$refund_transaction->request_type = StorePaymentRequest::TYPE_REFUND;
		$refund_transaction->transaction_id = $response->getField('VPSTxId');
		$refund_transaction->authorization_code =
			$response->getField('TxAuthNo');

		return $refund_transaction;
	}

	// }}}
	// {{{ public function void()

	/**
	 * Voids a transaction
	 *
	 * Voiding cancels a transaction and prevents both both merchant fees and
	 * charging the customer.
	 *
	 * A void must be performed before the merchant bank settles outstanding
	 * transactions. Once settled, a transaction cannot be voided.
	 *
	 * For Protx, this means the void must be performed before the morning
	 * following the creation of the transaction.
	 *
	 * Once a transaction is voided it cannot be refunded, released, repeated,
	 * aborted, or voided again.
	 *
	 * If this method does not throw an exception, the void was successful.
	 *
	 * @param StorePaymentTransaction $transaction the tranaction to void.
	 *
	 * @return StorePaymentTransaction a transaction object representing the
	 *                                  voided transaction.
	 */
	public function void(StorePaymentTransaction $transaction)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_VOID, $this->mode);

		$fields = array(
			'Vendor'       => $this->vendor,
			'VendorTxCode' => $transaction->getInternalValue('ordernum'),
			'VPSTxId'      => $transaction->transaction_id,
			'SecurityKey'  => $transaction->security_key,
			'TxAuthNo'     => $transaction->authorization_code,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);

		$void_transaction = new StorePaymentTransaction();
		$void_transaction->createdate = new SwatDate();
		$void_transaction->createdate->toUTC();
		$void_transaction->ordernum =
			$transaction->getInternalValue('ordernum');

		$void_transaction->request_type = StorePaymentRequest::TYPE_VOID;
		$void_transaction->transaction_id = $transaction->transaction_id;

		return $void_transaction;
	}

	// }}}
	// {{{ public function threeDomainSecureAuth()

	/**
	 * Authenticates an existing 3-D Secure transaction
	 *
	 * After successful completion of the 3-D Secure transaction, both the
	 * original transaction object and the returned transaction object should
	 * be saved. The original transaction object will be updated by this
	 * method.
	 *
	 * @param StorePaymentTransaction $transaction the original transaction
	 *                                              initiated by the 3-D Secure
	 *                                              authentication process.
	 *                                              This transaction must
	 *                                              contain the order id and
	 *                                              merchant data of the
	 *                                              original transaction.
	 * @param string $pares payer authentication response. The base64 encoded,
	 *                       encrypted message retrieved from the issuing bank
	 *                       for the transaction.
	 *
	 * @return StorePaymentTransaction the authenticated transation.
	 */
	public function threeDomainSecureAuth(StorePaymentTransaction $transaction,
		$pares)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_3DS_AUTH, $this->mode);

		$fields = array(
			'MD'    => $transaction->merchant_data,
			'PARes' => $pares,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);

		// create final transaction (this one can be RELEASE'd if the original
		// transaction was a HOLD).
		$authed_transaction = $this->getPaymentTransaction($response,
			$transaction->getInternalValue('ordernum'),
			$transaction->request_type);

		// set original transaction type to 3-D Secure auth
		$transaction->request_type = StorePaymentRequest::TYPE_3DS_AUTH;

		return $authed_transaction;
	}

	// }}}
	// {{{ private function getCardFields()

	/**
	 * Gets card-specific fields required for making a card-based request
	 *
	 * @param StoreOrder $order the order to use for the card-based request.
	 * @param string $card_number the card number to use for the card-based
	 *                             request.
	 * @param string $card_verification_value optional if AVS mode is set to
	 *                                         off. The three-digit security
	 *                                         code found on the reverse of
	 *                                         cards or the four-digit security
	 *                                         code found on the front of amex
	 *                                         cards.
	 *
	 * @return array an array of key-value pairs containing VSP direct
	 *                card-specific fields used for card-based requests.
	 *
	 * @throws StoreException if the order has an unsupported card type.
	 */
	private function getCardFields(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		SwatException::addSensitiveParameter(
			'card_number', __FUNCTION__, __CLASS__);

		$payment_method = $order->payment_method;
		$payment_type = $payment_method->payment_type;

		$card_holder = substr($payment_method->card_fullname, 0, 50);
		$card_number = substr($card_number, 0, 20);
		$expiry_date = $payment_method->card_expiry->format('%m%y');

		$payment_type_map = $this->getPaymentTypeMap();
		if (array_key_exists($payment_type->shortname, $payment_type_map))
			$card_type = $payment_type_map[$payment_type->shortname];
		else
			throw new StoreException('Unsupported card type in order.');

		$fields = array(
			'CardHolder' => $card_holder,
			'CardNumber' => $card_number,
			'CardType'   => $card_type,
			'ExpiryDate' => $expiry_date,
		);

		// Start date is required for Solo, Switch and Amex
		if (in_array($card_type, array('SWITCH', 'SOLO', 'AMEX'))) {
			$start_date = $payment_method->card_inception->format('%m%y');
			$fields['StartDate'] = $start_date;
		}

		// Issue number is required for Solo and Switch
		if (in_array($card_type, array('SWITCH', 'SOLO'))) {
			$issue_number = substr($payment_method->card_issue_number, 0, 2);
			$fields['IssueNumber'] = $issue_number;
		}

		if ($card_verification_value !== null ||
			$this->avs_mode == StorePaymentProvider::AVS_ON) {
			// CV2 is 4 chars for amex and 3 chars for everything else
			$length = ($card_type == 'AMEX') ? 4 : 3;
			$cv2 = substr($card_verification_value, 0, $length);
			$fields['CV2'] = $cv2;
		}

		// AVS/CV2 mode
		if ($this->avs_mode == StorePaymentProvider::AVS_ON)
			$fields['ApplyAVSCV2'] = 3; // Force checks but don't apply rules
		else
			$fields['ApplyAVSCV2'] = 2; // Force NO checks

		// 3-DS mode
		if ($this->three_domain_secure_mode ==
			StorePaymentProvider::THREE_DOMAIN_SECURE_ON)
			$fields['Apply3DSecure'] = 3; // Force use of 3-D Secure if enabled
		else
			$fields['Apply3DSecure'] = 2; // Do not use 3-D Secure checks

		return $fields;
	}

	// }}}
	// {{{ private function getOrderRequiredFields()

	/**
	 * Gets fields required for making all transaction types
	 *
	 * @param StoreOrder $order the order to get fields from.
	 *
	 * @return array an array of key-value pairs containing VSP direct fields
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
		$required_fields = $this->getOrderRequiredFields($order);

		if ($order->total > 100000) {
			throw new StoreException('Protx payments can only be made for '.
				'orders with total values of 100,000 or less.');
		}

		$amount = SwatString::numberFormat($order->total, 2, null, false);
		$description = substr($order->getDescription(), 0, 100);

		$billing_address = $this->getAddressString($order->billing_address);
		$billing_post_code =
			substr($order->billing_address->postal_code, 0, 10);

		$delivery_address = $this->getAddressString($order->shipping_address);
		$delivery_post_code =
			substr($order->shipping_address->postal_code, 0, 10);

		$customer_name = substr($order->billing_address->fullname, 0, 100);

		$payment_fields = array(
			'Amount'           => $amount,
			'Currency'         => $this->currency,
			'Description'      => $description,
			'BillingAddress'   => $billing_address,
			'BillingPostCode'  => $billing_post_code,
			'DeliveryAddress'  => $delivery_address,
			'DeliveryPostCode' => $delivery_post_code,
			'CustomerName'     => $customer_name,
		);

		if ($order->phone !== null) {
			$contact_number = substr($order->phone, 0, 20);
			$payment_fields['ContactNumber'] = $contact_number;
		}

		if ($order->email !== null) {
			$customer_email = substr($order->email, 0, 255);
			$payment_fields['CustomerEMail'] = $customer_email;
		}

		$fields = array_merge($required_fields, $payment_fields);

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
			switch ($status_detail) {
			case 'Security code length is invalid.':
				throw new StorePaymentCvvException($status_detail);
				break;
			case 'The card type does not match the card number':
				throw new StorePaymentCardTypeException($status_detail);
				break;
			default:
				throw new StorePaymentMalformedException($status_detail);
				break;
			}

			break;

		case 'INVALID':
			switch ($status_detail) {
			case 'The BillingPostCode you provided also appears to be '.
				'included as part of the BillingAddress.  You should remove '.
				'the Post Code from the Address field before submitting the '.
				'address, or AVS checks will fail.':
				throw new StorePaymentAddressException($status_detail);
				break;
			case 'The card number given is invalid.':
				throw new StorePaymentCardTypeException($status_detail);
				break;
			case 'The CV2 field should be a 3 digit number for all cards '.
				'except American Express, where it contains 4 digits.':
				throw new StorePaymentCvvException($status_detail);
				break;
			default:
				throw new StorePaymentInvalidException($status_detail);
				break;
			}

			break;

		case 'ERROR':
			throw new StorePaymentErrorException($status_detail);
			break;

		case 'NOTAUTHED':

			throw new StorePaymentNotAuthorizedException($status_detail);
			break;

		case 'REJECTED':
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
	// {{{ private function getAddressString()

	private function getAddressString(StoreOrderAddress $address)
	{
		$address_string = $address->line1;
		$address_string.= ($address->line2 === null) ? '' : ' '.$address->line2;
		$address_string.= ', '.$address->city.' ';

		$provstate = $address->getInternalValue('provstate');
		$address_string.= ($provstate === null) ?
			$address->provstate_other.', ' :
			$address->provstate->abbreviation.', ';

		$address_string.= $address->country->title;
		$address_string = substr($address_string, 0, 200);

		return $address_string;
	}

	// }}}
	// {{{ private function getPaymentTransaction()

	/**
	 * Builds a payment transaction object from a Protx payment response
	 *
	 * This is a helper method for constructing transaction objects for
	 * PAY, HOLD and 3DS_AUTH requests.
	 *
	 * @param StoreProtxPaymentResponse $response the response object from
	 *                                             which to build the
	 *                                             transaction object.
	 * @param integer $order_id the id of the order used to make the
	 *                           transaction.
	 * @param integer $request_type the type of request used to make the
	 *                               transaction. Should be one of the
	 *                               StorePaymentRequest::TYPE_* constants.
	 *
	 * @return StorePaymentTransaction the payment transaction object.
	 */
	private function getPaymentTransaction(StoreProtxPaymentResponse $response,
		$order_id, $request_type)
	{
		$transaction = new StorePaymentTransaction();
		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->ordernum = $order_id;
		$transaction->request_type = $request_type;

		if ($response->getField('Status') == '3DAUTH') {
			$transaction->merchant_data = $response->getField('MD');
			$transaction->setPayerAuthenticationRequest(
				$response->getField('PAReq'));

			$transaction->setAccessControlServerUrl(
				$response->getField('ACSURL'));
		} else {
			$transaction->transaction_id = $response->getField('VPSTxId');
			$transaction->security_key = $response->getField('SecurityKey');
			$transaction->authorization_code = $response->getField('TxAuthNo');

			// cardholder authentication verification value
			if ($response->hasField('CAVV'))
				$transaction->cavv = $response->getField('CAVV');

			// address
			switch ($response->getField('AddressResult')) {
			case 'NOTPROVIDED':
				$transaction->address_status =
					StorePaymentTransaction::STATUS_MISSING;

				break;
			case 'NOTCHECKED':
				$transaction->address_status =
					StorePaymentTransaction::STATUS_NOTCHECKED;

				break;
			case 'MATCHED':
				$transaction->address_status =
					StorePaymentTransaction::STATUS_PASSED;

				break;
			case 'NOTMATCHED':
				$transaction->address_status =
					StorePaymentTransaction::STATUS_FAILED;

				break;
			}

			// postal/zip code
			switch ($response->getField('PostCodeResult')) {
			case 'NOTPROVIDED':
				$transaction->postal_code_status =
					StorePaymentTransaction::STATUS_MISSING;

				break;
			case 'NOTCHECKED':
				$transaction->postal_code_status =
					StorePaymentTransaction::STATUS_NOTCHECKED;

				break;
			case 'MATCHED':
				$transaction->postal_code_status =
					StorePaymentTransaction::STATUS_PASSED;

				break;
			case 'NOTMATCHED':
				$transaction->postal_code_status =
					StorePaymentTransaction::STATUS_FAILED;

				break;
			}

			// card verification value
			switch ($response->getField('CV2Result')) {
			case 'NOTPROVIDED':
				$transaction->card_verification_value_status =
					StorePaymentTransaction::STATUS_MISSING;

				break;
			case 'NOTCHECKED':
				$transaction->card_verification_value_status =
					StorePaymentTransaction::STATUS_NOTCHECKED;

				break;
			case 'MATCHED':
				$transaction->card_verification_value_status =
					StorePaymentTransaction::STATUS_PASSED;

				break;
			case 'NOTMATCHED':
				$transaction->card_verification_value_status =
					StorePaymentTransaction::STATUS_FAILED;

				break;
			}

			// 3-D Secure authentication status
			if ($response->hasField('3DSecureStatus')) {
				switch ($response->getField('3DSecureStatus')) {
				case 'NOAUTH':
				case 'CANTAUTH':
				case 'ATTEMPTONLY':
					$transaction->three_domain_secure_status =
						StorePaymentTransaction::STATUS_MISSING;

					break;
				case 'NOTCHECKED':
					$transaction->three_domain_secure_status =
						StorePaymentTransaction::STATUS_NOTCHECKED;

					break;
				case 'OK':
					$transaction->three_domain_secure_status =
						StorePaymentTransaction::STATUS_PASSED;

					break;
				case 'NOTAUTHED':
					$transaction->three_domain_secure_status =
						StorePaymentTransaction::STATUS_FAILED;

					break;
				}
			}
		}

		return $transaction;
	}

	// }}}
}

?>
