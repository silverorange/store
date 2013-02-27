<?php

require_once 'Swat/SwatString.php';
require_once 'Store/StorePaymentProvider.php';
require_once 'Store/exceptions/StorePaymentAuthorizeNetException.php';
require_once 'AuthorizeNet.php';

/**
 * @package   Store
 * @copyright 2011-2013 silverorange
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

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment provider using the Authorize.net AIM API
	 *
	 * Available parameters are:
	 *
	 * <kbd>mode</kbd>            - optional. Transaction mode to use. Must be
	 *                              one of either 'live' or 'sandbox'. If not
	 *                              specified, 'sandbox' is used.
	 * <kbd>login_id</kbd>        - required. Login identifier for
	 *                              Authorize.net authentication.
	 * <kbd>transaction_key</kbd> - required. Transaction key for
	 *                              Authorize.net authentication.
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
		$payment_method  = $order->payment_methods->getFirst();
		$billing_address = $order->billing_address;

		$request = new AuthorizeNetAIM(
			$this->login_id,
			$this->transaction_key);

		$request->setSandbox(($this->mode !== 'live'));

		// Transaction fields
		$request->amount = $order->total;
		$request->card_num =
			$payment_method->getUnencryptedCardNumber();

		$request->card_code =
			$payment_method->getUnencryptedCardVerificationValue();

		$request->exp_date =
			$payment_method->card_expiry->formatLikeIntl('MM/yy');

		// Order fields
		$request->invoice_num = $order->id;
		$request->description = 'Order '.$order->id;

		// Customer fields
		$request->first_name = $billing_address->first_name;
		$request->last_name  = $billing_address->last_name;

		if ($billing_address->company !== null) {
			$request->company = $billing_address->company;
		}

		$request->address = $billing_address->line1;
		$request->city = $billing_address->city;
		if ($billing_address->provstate_other !== null) {
			$request->state = $billing_address->provstate_other;
		} else {
			$request->state = $billing_address->provstate->abbreviation;
		}
		$request->zip = $billing_address->postal_code;
		$request->country = $billing_address->country->title;

		if ($billing_address->phone !== null) {
			$request->phone = $billing_address->phone;
		}
		$request->email = $order->email;
		if ($order->account !== null && $order->account->id !== null) {
			$request->cust_id = $order->account->id;
		}
		$request->customer_ip = $this->getIpAddress();

		// Line items
		foreach ($order->items as $item) {
			$request->addLineItem(
				$item->id,
				$this->truncateField($item->product_title, 31),
				$this->truncateField($item->description, 255),
				$item->quantity,
				$item->price,
				false);
		}

		// do transaction
		$response = $request->authorizeAndCapture();

		if ($response->declined || $response->error) {
			$text = sprintf('Code: %s, Reason Code: %s, Message: %s',
				$response->response_code,
				$response->response_reason_code,
				$response->response_reason_text);

			throw new StorePaymentAuthorizeNetException(
				$text,
				$response->response_code,
				$response->response_reason_code,
				$response);
		}

		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();
		$transaction->transaction_type = StorePaymentRequest::TYPE_PAY;
		$transaction->transaction_id = $response->transaction_id;

		return $transaction;
	}

	// }}}
	// {{{ public static function getExceptionMessageId()

	/**
	 * Get a message id from a StorePaymentAuthorizeNetException
	 *
	 * @param StorePaymentAuthorizeNetException $e the payment exception
	 *
	 * @return string the error message id.
	 *
	 * @see StoreCheckoutConfirmationPage::getErrorMessage()
	 */
	public static function getExceptionMessageId(
		StorePaymentAuthorizeNetException $e)
	{
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

	// }}}
	// {{{ protected function getIpAddress()

	protected function getIpAddress()
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
}

?>
