<?php

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * @package   Store
 * @copyright 2011-2019 silverorange
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
 */
class StoreAuthorizeNetPaymentProvider extends StorePaymentProvider
{
	// {{{ protected properties

	/**
	 * @var string
	 * @see AuthorizeNetPaymentProvider::__construct()
	 */
	protected $transaction_key;

	/**
	 * @var string
	 * @see AuthorizeNetPaymentProvider::__construct()
	 */
	protected $login_id;

	/**
	 * 'live' or 'sandbox'
	 *
	 * @var string
	 * @see AuthorizeNetPaymentProvider::__construct()
	 */
	protected $mode;

	/**
	 * @var string
	 * @see AuthorizeNetPaymentProvider::__construct()
	 */
	protected $invoice_number_prefix;

	/**
	 * @var string
	 * @see AuthorizeNetPaymentProvider::__construct()
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
	 * @return net\authorize\api\controller\CreateTransactionController the transaction controller
	 *
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 */
	protected function getTransactionController(
		StoreOrder $order,
		$card_number,
		$card_verification_value = null
	) {
		$requestType = new AnetAPI\TransactionRequestType();
		$requestType->setTransactionType('authCaptureTransaction');
		$requestType->setAmount($order->total);
		$requestType->setTax($this->getTax($order));
		$requestType->setShipping($this->getShipping($order));
		$requestType->setOrder($this->getOrder($order));
		$requestType->setPayment(
			$this->getPayment($order, $card_number, $card_verification_value)
		);

		foreach ($order->items as $item) {
			$requestType->addToLineItems($this->getLineItem($item));
		}

		if ($order->billing_address instanceof StoreOrderAddress) {
			$requestType->setBillTo($this->getBillTo($order->billing_address));
		}

		$requestType->setCustomer($this->getCustomer($order));
		$requestType->setCustomerIP($this->getIPAddress());

		$request = new AnetAPI\CreateTransactionRequest();
		$request->setMerchantAuthentication($this->getMerchantAuthentication());
		$request->setTransactionRequest($requestType);

		return new AnetController\CreateTransactionController($request);
	}

	// }}}
	// {{{ protected function getInvoiceNumber()

	protected function getInvoiceNumber(StoreOrder $order)
	{
		// Authorize.net only allows 20 chars for invoice number. Get max length
		// from 19 to account for the space added.
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
		$creditCard = new AnetAPI\CreditCardType();
		$creditCard->setCardNumber($card_number);
		$creditCard->setCardCode($card_verification_value);

		// Default expiry date to use if no date is found in a payment method
		// is 1 month ago (expired).
		$date = new SwatDate('-1 month');

		foreach ($order->payment_methods as $payment_method) {
			if ($payment_method->getUnencryptedCardNumber() == $card_number) {
				$date = clone $payment_method->card_expiry;
				break;
			}
		}

		$creditCard->setExpirationDate($date->formatLikeIntl('y-MM'));

		$payment = new AnetAPI\PaymentType();
		$payment->setCreditCard($creditCard);

		return $payment;
	}

	// }}}
	// {{{ protected function getBillTo()

	protected function getBillTo(StoreOrderAddress $address)
	{
		$addr = new AnetAPI\CustomerAddressType();
		$addr->setFirstName($address->first_name);
		$addr->setLastName($address->last_name);

		if ($address->company != '') {
			$addr->setCompany($address->company);
		}

		$addr->setAddress($address->line1);
		$addr->setCity($address->city);

		if ($address->provstate_other != null) {
			$addr->setState($address->provstate_other);
		} elseif ($address->provstate instanceof StoreProvState) {
			$addr->setState($address->provstate->abbreviation);
		}

		$addr->setZip($address->postal_code);
		$addr->setCountry($address->country->title);

		if ($address->phone != '') {
			$addr->setPhoneNumber($address->phone);
		}

		return $addr;
	}

	// }}}
	// {{{ protected function getOrder()

	protected function getOrder(StoreOrder $order)
	{
		$ord = new AnetAPI\OrderType();
		$ord->setInvoiceNumber($this->getInvoiceNumber($order));
		$ord->setDescription(
			$this->truncateField(
				$this->getOrderDescription($order),
				255
			)
		);

		return $ord;
	}

	// }}}
	// {{{ protected function getCustomer()

	protected function getCustomer(StoreOrder $order)
	{
		$customer = new AnetAPI\CustomerDataType();
		$customer->setEmail($order->email);

		if ($order->account !== null && $order->account->id !== null) {
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
		$line_item->setDescription($this->truncateField($item->description, 255));
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
		$amount->setAmount($order->tax_total);

		return $amount;
	}

	// }}}
	// {{{ protected function getShipping()

	protected function getShipping(StoreOrder $order)
	{
		$amount = new AnetAPI\ExtendedAmountType();
		$amount->setAmount($order->shipping_total);

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
			if ($response->getMessages()->getResultCode() != "Ok") {
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
				} else if (is_array($messages) && count($messages) > 0){
					$message = $messages[0];

					$text = sprintf(
						'%s: %s',
						$message->getCode(),
						$message->getText()
					);
				}
			} else if (is_array($messages) && count($messages) > 0){
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
}

?>
