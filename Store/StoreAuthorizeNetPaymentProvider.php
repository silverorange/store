<?php

require_once 'Swat/SwatString.php';
require_once 'Store/StorePaymentProvider.php';
require_once 'Store/StorePaymentRequest.php';
require_once 'Store/exceptions/StorePaymentAuthorizeNetException.php';

/**
 * @package   Store
 * @copyright 2011-2015 silverorange
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
	public function pay(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = $this->getAIMPaymentRequest(
			$order,
			$card_number,
			$card_verification_value
		);

		// do transaction
		$response = $request->authorizeAndCapture();

		if ($response->declined || $response->error) {
			$text = sprintf(
				'Code: %s, Reason Code: %s, Message: %s',
				$response->response_code,
				$response->response_reason_code,
				$response->response_reason_text
			);

			throw new StorePaymentAuthorizeNetException(
				$text,
				$response->response_code,
				$response->response_reason_code,
				$response
			);
		}

		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->transaction_type = StorePaymentRequest::TYPE_PAY;
		$transaction->transaction_id = $response->transaction_id;
		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();

		return $transaction;
	}

	// }}}
	// {{{ public function getAIMPaymentRequest()

	/**
	 * Builds an AuthorizeNetAIM request for a payment.
	 *
	 * @param StoreOrder $order the order to pay for.
	 * @param string $card_number the card number to use for payment.
	 * @param string $card_verification_value optional. Card verification value
	 *                                         used for fraud prevention.
	 *
	 * @return AuthorizeNetAIM the payment request object.
	 *
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 */
	protected function getAIMPaymentRequest(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		$request = new AuthorizeNetAIM(
			$this->login_id,
			$this->transaction_key
		);

		$request->setSandbox(($this->mode !== 'live'));

		// Transaction fields
		$request->tax     = $this->formatNumber($order->tax_total);
		$request->freight = $this->formatNumber($order->shipping_total);
		$request->amount  = $this->formatNumber($order->total);

		$this->setRequestCardFields(
			$request,
			$order,
			$card_number,
			$card_verification_value
		);

		// Order fields
		$request->invoice_num = $this->getInvoiceNumber($order);
		$request->description = $this->truncateField(
			$this->getOrderDescription($order),
			255
		);

		if ($order->billing_address instanceof StoreOrderAddress) {
			$this->setRequestAddressFields($request, $order->billing_address);
		}

		$request->email = $order->email;
		if ($order->account !== null && $order->account->id !== null) {
			$request->cust_id = $order->account->id;
		}

		$request->customer_ip = $this->getIPAddress();

		$this->addRequestLineItems($request, $order);

		return $request;
	}

	// }}}
	// {{{ public function getExceptionMessageId()

	public function getExceptionMessageId(Exception $e)
	{
		if ($e instanceof StorePaymentAuthorizeNetException) {
			// declined responses
			if ($e->getCode() === AuthorizeNetResponse::DECLINED) {
				switch ($e->getReasonCode()) {
				case 2:
				case 3:
				case 4:
				case 28:  // card type not accepted
				case 37:  // card number invalid
				case 45:  // blacklisted cvv or address data
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

			// error responses
			} else {
				switch ($e->getReasonCode()) {
				case 6:
				case 17:  // card type not accepted
				case 128: // blocked by issuing bank
					return 'card-not-valid';

				// Everything else gets a generic error.
				default:
					return 'card-error';
				}
			}
		}

		return null;
	}

	// }}}
	// {{{ protected function getInvoiceNumber()

	protected function getInvoiceNumber(StoreOrder $order)
	{
		// Authorize.net only allows 20 chars for invoice number. Get max length
		// from 19 to account for the space added.
		$invoice_number_prefix = $this->truncateField(
			$this->invoice_number_prefix,
			19 - strlen($order->id)
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
	// {{{ protected function setRequestCardFields()

	/**
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 * @sensitive $payment_method
	 */
	protected function setRequestCardFields(AuthorizeNetAIM $request,
		StoreOrder $order, $card_number, $card_verification_value = null)
	{
		$request->card_num = $card_number;
		$request->card_code = $card_verification_value;

		// Default expiry date to use if no date is found in a payment method
		// is 1 month ago (expired).
		$date = new SwatDate('-1 month');

		foreach ($order->payment_methods as $payment_method) {
			if ($payment_method->getUnencryptedCardNumber() == $card_number) {
				$date = clone $payment_method->card_expiry;
				break;
			}
		}

		$request->exp_date = $date->formatLikeIntl('MM/yy');
	}

	// }}}
	// {{{ protected function setRequestAddressFields()

	protected function setRequestAddressFields(AuthorizeNetAIM $request,
		StoreOrderAddress $address)
	{
		$request->first_name = $address->first_name;
		$request->last_name  = $address->last_name;

		if ($address->company != '') {
			$request->company = $address->company;
		}

		$request->address = $address->line1;
		$request->city = $address->city;
		if ($address->provstate_other != null) {
			$request->state = $address->provstate_other;
		} elseif ($address->provstate instanceof StoreProvState) {
			$request->state = $address->provstate->abbreviation;
		}

		$request->zip = $address->postal_code;
		$request->country = $address->country->title;

		if ($address->phone != '') {
			$request->phone = $address->phone;
		}
	}

	// }}}
	// {{{ protected function addRequestLineItems()

	protected function addRequestLineItems(AuthorizeNetAIM $request,
		StoreOrder $order)
	{
		foreach ($order->items as $item) {
			$this->addRequestLineItem($request, $item);
		}
	}

	// }}}
	// {{{ protected function addRequestLineItem()

	protected function addRequestLineItem(AuthorizeNetAIM $request,
		StoreOrderItem $item)
	{
		$request->addLineItem(
			$item->id,
			$this->truncateField($item->product_title, 31),
			$this->truncateField($item->description, 255),
			$item->quantity,
			$this->formatNumber($item->price),
			false
		);
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
	// {{{ protected function truncateField()

	protected function truncateField($content, $maxlength)
	{
		$content = SwatString::condense($content, $maxlength - 4, ' ...');
		$content = str_replace('  •  ', ' - ', $content);
		$content = html_entity_decode($content, ENT_QUOTES, 'ISO-8859-1');
		return $content;
	}

	// }}}
	// {{{ protected function formatNumber()

	/**
	 * @param float $value
	 *
	 * @return string formatted .
	 */
	protected function formatNumber($value)
	{
		$value = round($value, 2);

		return number_format($value, 2, '.', '');
	}

	// }}}
}

?>
