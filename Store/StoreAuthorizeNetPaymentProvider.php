<?php

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * @package   Store
 * @copyright 2011-2020 silverorange
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
 */
class StoreAuthorizeNetPaymentProvider extends StorePaymentProvider
{
	// {{{ protected properties

	/**
	 * @var string
	 * @see StoreAuthorizeNetPaymentProvider::__construct()
	 */
	protected $transaction_key;

	/**
	 * @var string
	 * @see StoreAuthorizeNetPaymentProvider::__construct()
	 */
	protected $login_id;

	/**
	 * 'live' or 'sandbox'
	 *
	 * @var string
	 * @see StoreAuthorizeNetPaymentProvider::__construct()
	 */
	protected $mode;

	/**
	 * @var string
	 * @see StoreAuthorizeNetPaymentProvider::__construct()
	 */
	protected $invoice_number_prefix;

	/**
	 * @var string
	 * @see StoreAuthorizeNetPaymentProvider::__construct()
	 */
	protected $order_description_prefix;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment provider using the Authorize.net AIM API
	 *
	 * Available parameters are:
	 *
	 * <kbd>mode</kbd>                     - optional. Transaction mode to use.
	 *                                       Must be one of either 'live' or
	 *                                       'sandbox'. If not specified,
	 *                                       'sandbox' is used.
	 * <kbd>login_id</kbd>                 - required. Login identifier for
	 *                                       Authorize.net authentication.
	 * <kbd>transaction_key</kbd>          - required. Transaction key for
	 *                                       Authorize.net authentication.
	 * <kbd>invoice_number_prefix</kbd>    - optional. Prefix for the invoice
	 *                                       number sent to Authorize.net. Will
	 *                                       be trimmed to 20 charecters minus
	 *                                       the length of the order id to fix
	 *                                       Authorize.net field length
	 *                                       requirements.
	 * <kbd>order_description_prefix</kbd> - optional. Prefix for the order id
	 *                                       used for the description sent to
	 *                                       Authorize.net. If not set, the
	 *                                       order id will be prefixed with
	 *                                       "Order".
	 *
	 * @throws StoreException if a required parameter is missing or if the
	 *                        'mode' paramater is not valid.
	 */
	public function __construct(array $parameters = array())
	{
		$required_parameters = array(
			'login_id',
			'transaction_key',
		);

		foreach ($required_parameters as $parameter) {
			if (!isset($parameters[$parameter])) {
				throw new StoreException('"'.$parameter.'" is required in the '.
					'Authorize.net payment provider parameters.');
			}
		}

		if (!isset($parameters['mode'])) {
			$parameters['mode'] = 'sandbox';
		}

		$valid_modes = array('live', 'sandbox');
		if (!in_array($parameters['mode'], $valid_modes)) {
			throw new StoreException('Mode "'.$mode.'" is not valid for '.
				'the Authorize.net payment provider.');
		}

		$this->login_id        = $parameters['login_id'];
		$this->transaction_key = $parameters['transaction_key'];
		$this->mode            = $parameters['mode'];

		if (isset($parameters['invoice_number_prefix'])) {
			$this->invoice_number_prefix = $parameters['invoice_number_prefix'];
		}

		$this->order_description_prefix =
			(isset($parameters['order_description_prefix']))
			? $parameters['order_description_prefix']
			: Store::_('Order');
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
	public function pay(
		StoreOrder $order,
		$card_number,
		$card_verification_value = null
	) {
		$controller = $this->getTransactionController(
			$order,
			$card_number,
			$card_verification_value
		);

		$response = $controller->executeWithApiResponse(
			$this->mode === 'live'
				? \net\authorize\api\constants\ANetEnvironment::PRODUCTION
				: \net\authorize\api\constants\ANetEnvironment::SANDBOX
		);

		if ($this->hasError($response)) {
			throw $this->getException($response);
		}

		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->transaction_type = StorePaymentRequest::TYPE_PAY;
		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->transaction_id =
			$response->getTransactionResponse()->getTransId();

		return $transaction;
	}

	// }}}
	// {{{ public function getExceptionMessageId()

	public function getExceptionMessageId(Exception $e)
	{
		if ($e instanceof StorePaymentAuthorizeNetException) {
			switch ($e->getCode()) {
			case 2:
			case 3:
			case 4:
			case 6:
			case 17:  // card type not accepted
			case 28:  // card type not accepted
			case 37:  // card number invalid
			case 45:  // blacklisted cvv or address data
			case 128: // blocked by issuing bank
			case 200: // FDC Omaha
			case 315:
			case 316:
			case 317:
				return 'card-not-valid';

			// AVS address mismatch
			case 27:
			case 127: // for 'void' action
				return 'address-mismatch';

			// CVV2 mismatch
			case 44:
				return 'card-verification-value';

			// Everything else gets a generic error.
			default:
				return 'card-error';
			}
		}

		return null;
	}

	// }}}
	// {{{ protected function getTransactionController()

	/**
	 * Builds a transaction controller for a payment.
	 *
	 * @param StoreOrder $order the order to pay for.
	 * @param string $card_number the card number to use for payment.
	 * @param string $card_verification_value optional. Card verification value
	 *                                         used for fraud prevention.
	 *
	 * @return net\authorize\api\controller\CreateTransactionController the transaction
	 *         controller.
	 *
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 */
	protected function getTransactionController(
		StoreOrder $order,
		$card_number,
		$card_verification_value = null
	) {
		$request_type = new AnetAPI\TransactionRequestType();

		$request_type->setTransactionType('authCaptureTransaction');
		$request_type->setAmount($this->getSafeTotal($order->total));
		$request_type->setTax($this->getTax($order));
		$request_type->setShipping($this->getShipping($order));
		$request_type->setOrder($this->getOrder($order));
		$request_type->setPayment(
			$this->getPayment($order, $card_number, $card_verification_value)
		);

		foreach ($order->items as $item) {
			$request_type->addToLineItems($this->getLineItem($item));
		}

		if ($order->billing_address instanceof StoreOrderAddress) {
			$request_type->setBillTo($this->getBillTo($order->billing_address));
		}

		$request_type->setCustomer($this->getCustomer($order));
		$request_type->setCustomerIP($this->getIPAddress());

		$request = new AnetAPI\CreateTransactionRequest();

		$request->setMerchantAuthentication($this->getMerchantAuthentication());
		$request->setTransactionRequest($request_type);

		return new AnetController\CreateTransactionController($request);
	}

	// }}}
	// {{{ protected function getInvoiceNumber()

	protected function getInvoiceNumber(StoreOrder $order)
	{
		// Authorize.net only allows 20 chars for invoice number. Get max
		// length from 19 to account for the space added.
		$invoice_number_prefix = $this->truncateField(
			$this->invoice_number_prefix,
			19 - mb_strlen($order->id)
		);

		return ($invoice_number_prefix == '')
			? $order->id
			: $invoice_number_prefix.' '.$order->id;
	}

	// }}}
	// {{{ protected function getOrderDescription()

	protected function getOrderDescription(StoreOrder $order)
	{
		return $this->order_description_prefix.' '.$order->id;
	}

	// }}}
	// {{{ protected function getPayment()

	/**
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 * @sensitive $payment_method
	 */
	protected function getPayment(
		StoreOrder $order,
		$card_number,
		$card_verification_value = null
	) {
		$credit_card = new AnetAPI\CreditCardType();

		$credit_card->setCardNumber($card_number);
		$credit_card->setCardCode($card_verification_value);

		// Default expiry date to use if no date is found in a payment method
		// is 1 month ago (expired).
		$date = new SwatDate('-1 month');

		foreach ($order->payment_methods as $payment_method) {
			if ($payment_method->getUnencryptedCardNumber() == $card_number) {
				$date = clone $payment_method->card_expiry;
				break;
			}
		}

		$credit_card->setExpirationDate($date->formatLikeIntl('y-MM'));

		$payment = new AnetAPI\PaymentType();
		$payment->setCreditCard($credit_card);
		return $payment;
	}

	// }}}
	// {{{ protected function getBillTo()

	protected function getBillTo(StoreOrderAddress $address)
	{
		$anet_address = new AnetAPI\CustomerAddressType();

		$anet_address->setFirstName($address->first_name);
		$anet_address->setLastName($address->last_name);

		if ($address->company != '') {
			$anet_address->setCompany($address->company);
		}

		$anet_address->setAddress($address->line1);
		$anet_address->setCity($address->city);

		if ($address->provstate_other != null) {
			$anet_address->setState($address->provstate_other);
		} elseif ($address->provstate instanceof StoreProvState) {
			$anet_address->setState($address->provstate->abbreviation);
		}

		$anet_address->setZip($address->postal_code);
		$anet_address->setCountry($address->country->title);

		if ($address->phone != '') {
			$anet_address->setPhoneNumber($address->phone);
		}

		return $anet_address;
	}

	// }}}
	// {{{ protected function getOrder()

	protected function getOrder(StoreOrder $order)
	{
		$anet_order = new AnetAPI\OrderType();

		$anet_order->setInvoiceNumber($this->getInvoiceNumber($order));
		$anet_order->setDescription(
			$this->truncateField(
				$this->getOrderDescription($order),
				255
			)
		);

		return $anet_order;
	}

	// }}}
	// {{{ protected function getCustomer()

	protected function getCustomer(StoreOrder $order)
	{
		$customer = new AnetAPI\CustomerDataType();

		$customer->setEmail($order->email);

		if ($order->account instanceof SiteAccount &&
			$order->account->id !== null
		) {
			$customer->setId($order->account->id);
		}

		return $customer;
	}

	// }}}
	// {{{ protected function getLineItem()

	protected function getLineItem(StoreOrderItem $item)
	{
		$line_item = new AnetAPI\LineItemType();

		$line_item->setItemId($item->id);
		$line_item->setName($this->truncateField($item->product_title, 31));
		$line_item->setDescription(
			$this->truncateField($item->description, 255)
		);
		$line_item->setQuantity($item->quantity);
		$line_item->setUnitPrice($item->price);
		$line_item->setTaxable(false);

		return $line_item;
	}

	// }}}
	// {{{ protected function getIPAddress()

	protected function getIPAddress()
	{
		$remote_ip = null;

		if (isset($_SERVER['HTTP_X_FORWARDED_IP'])) {
			$remote_ip = $_SERVER['HTTP_X_FORWARDED_IP'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$remote_ip = $_SERVER['REMOTE_ADDR'];
		}

		return $remote_ip;
	}

	// }}}
	// {{{ protected function getTax()

	protected function getTax(StoreOrder $order)
	{
		$amount = new AnetAPI\ExtendedAmountType();
		$amount->setAmount($this->getSafeTotal($order->tax_total));
		return $amount;
	}

	// }}}
	// {{{ protected function getShipping()

	protected function getShipping(StoreOrder $order)
	{
		$amount = new AnetAPI\ExtendedAmountType();
		$amount->setAmount($this->getSafeTotal($order->shipping_total));
		return $amount;
	}

	// }}}
	// {{{ protected function getMerchantAuthentication()

	protected function getMerchantAuthentication()
	{
		$auth = new AnetAPI\MerchantAuthenticationType();

		$auth->setName($this->login_id);
		$auth->setTransactionKey($this->transaction_key);

		return $auth;
	}

	// }}}
	// {{{ protected function hasError()

	protected function hasError($response)
	{
		if ($response instanceof AnetAPI\AnetApiResponseType) {
			if ($response->getMessages()->getResultCode() !== 'Ok') {
				return true;
			}

			$tresponse = $response->getTransactionResponse();
			if ($tresponse instanceof AnetAPI\TransactionResponseType) {
				$errors = $tresponse->getErrors();
				if (is_array($errors) && count($errors) > 0) {
					return true;
				}
			}
		}

		return false;
	}

	// }}}
	// {{{ protected function getException()

	protected function getException($response)
	{
		$code = 0;
		$text = 'Unknown Error';

		if ($response instanceof AnetAPI\AnetApiResponseType) {
			$messages = $response->getMessages()->getMessage();
			$tresponse = $response->getTransactionResponse();
			if ($tresponse instanceof AnetAPI\TransactionResponseType) {
				$errors = $tresponse->getErrors();
				if (is_array($errors) && count($errors) > 0) {
					$error = $errors[0];

					$code = $error->getErrorCode();
					$text = $error->getErrorText();
				} elseif (is_array($messages) && count($messages) > 0){
					$message = $messages[0];

					$text = sprintf(
						'%s: %s',
						$message->getCode(),
						$message->getText()
					);
				}
			} elseif (is_array($messages) && count($messages) > 0){
				$message = $messages[0];

				$text = sprintf(
					'%s: %s',
					$message->getCode(),
					$message->getText()
				);
			}
		}

		return new StorePaymentAuthorizeNetException($text, $code);
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
	// {{{ protected function getSafeTotal()

	protected function getSafeTotal($total)
	{
		// We can get into trouble using floats. Using solution outlined here:
		// https://github.com/AuthorizeNet/sdk-php/issues/366
		return number_format($total, 2, '.', '') . "";
	}

	// }}}
}

?>
