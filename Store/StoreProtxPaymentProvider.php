<?php

require_once 'Store/StorePaymentProvider.php';
require_once 'Store/StoreProtxPaymentRequest.php';
require_once 'Store/dataobjects/StorePaymentTransaction.php';
require_once 'Store/dataobjects/StoreOrder.php';
require_once 'Store/exceptions/StoreException.php';

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
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_PAY, $this->mode);

		$fields = $this->getOrderPaymentFields($order);
		$request->setFields($fields);
		$request->setField('CardNumber', $card_number);
		if ($card_verification_value !== null)
			$request->setField('CV2', $card_verification_value);

		$response = $request->process();
		$this->checkResponse($response);

		$transaction = $this->getPaymentTransaction($response, $order->id);
		return $transaction;
	}

	// }}}
	// {{{ public function hold()

	/**
	 * Place a hold on funds for an order
	 *
	 * @param StoreOrder $order the order to hold funds for.
	 * @param string $card_number the card number to place the hold on.
	 * @param string $card_verification_value optional. Card verification value
	 *                                         used for fraud prevention.
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
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_HOLD, $this->mode);

		$fields = $this->getOrderPaymentFields($order);
		$request->setFields($fields);
		$request->setField('CardNumber', $card_number);
		if ($card_verification_value !== null)
			$request->setField('CV2', $card_verification_value);

		$response = $request->process();
		$this->checkResponse($response);

		$transaction = $this->getPaymentTransaction($response, $order->id);
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
	 */
	public function release(StorePaymentTransaction $transaction) 
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_RELEASE, $this->mode);

		$fields = array(
			'Vendor'       => $this->vendor,
			'VendorTxCode' => $transaction->getInternalValue('ordernum');
			'VPSTxId'      => $transaction->transaction_id,
			'SecurityKey'  => $transaction->security_key,
			'TxAuthNo'     => $transaction->authorization_code,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);
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
	 */
	public function abort(StorePaymentTransaction $transaction)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_ABORT, $this->mode);

		$fields = array(
			'Vendor'       => $this->vendor,
			'VendorTxCode' => $transaction->getInternalValue('ordernum');
			'VPSTxId'      => $transaction->transaction_id,
			'SecurityKey'  => $transaction->security_key,
			'TxAuthNo'     => $transaction->authorization_code,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);
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
			'RelatedVendorTxCode' => $transaction->order->id,
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
	 */
	public function void(StorePaymentTransaction $transaction)
	{
		$request = new StoreProtxPaymentRequest(
			StorePaymentRequest::TYPE_VOID, $this->mode);

		$fields = array(
			'Vendor'       => $this->vendor,
			'VendorTxCode' => $transaction->getInternalValue('ordernum');
			'VPSTxId'      => $transaction->transaction_id,
			'SecurityKey'  => $transaction->security_key,
			'TxAuthNo'     => $transaction->authorization_code,
		);

		$request->setFields($fields);
		$response = $request->process();
		$this->checkResponse($response);
	}

	// }}}
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

		$billing_address = $this->getAddressString($order->billing_address);
		$billing_post_code =
			substr($order->billing_address->postal_code, 0, 10);

		$delivery_address = $this->getAddressString($order->shipping_address);
		$delivery_post_code =
			substr($order->shipping_address->postal_code, 0, 10);

		$customer_name = substr($order->billing_address->fullname, 0, 100);
		$contact_number = substr($order->phone, 0, 20);
		$customer_email = substr($order->email, 0, 255);

		$payment_type_map = $this->getPaymentTypeMap();
		if (array_key_exists($payment_method->payment_type->shortname,
			$payment_type_map)) {
			$card_type =
				$payment_type_map[$payment_method->payment_type->shortname];
		} else {
			throw new StoreException('Unsupported card type in order.');
		}

		$payment_fields = array(
			'Amount'           => $amount,
			'CardHolder'       => $card_holder,
			'CardNumber'       => $card_number,
			'ExpiryDate'       => $card_expiry,
			'CardType'         => $card_type,
			'ExpiryDate'       => $card_expiry,
			'Currency'         => $this->currency,
			'Description'      => $description,
			'BillingAddress'   => $billing_address,
			'BillingPostCode'  => $billing_post_code,
			'DeliveryAddress'  => $delivery_address,
			'DeliveryPostCode' => $delivery_post_code,
			'CustomerName'     => $customer_name,
			'ContactNumber'    => $contact_number,
			'CustomerEMail'    => $customer_email,
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

		// AVS/CV2 mode
		if ($this->avs_mode == StorePaymentProvider::AVS_ON) {
			$fields['ApplyAVSCV2'] = 1; // Force checks
			// TODO: include AVS/CV2 fields
		} else {
			$fields['ApplyAVSCV2'] = 2; // Force NO checks
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
	 * @param StoreProtxPaymentResponse $response the response object to
	 *                                             build the transaction object
	 *                                             from.
	 * @param integer $order_id the id of the order used to make the
	 *                           transaction.
	 *
	 * @return StorePaymentTransaction the payment transaction object.
	 */
	private function getPaymentTransaction(StoreProtxPaymentResponse $response,
		$order_id)
	{
		$transaction = new StorePaymentTransaction();
		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->ordernum = $order_id;
		$transaction->transaction_id = $response->getField('VPSTxId');
		$transaction->security_key = $response->getField('SecurityKey');
		$transaction->authorization_code = $response->getField('TxAuthNo');

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

		return $transaction;
	}

	// }}}
}

?>
