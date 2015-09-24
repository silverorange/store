<?php

require_once 'Swat/SwatString.php';
require_once 'Store/StorePaymentProvider.php';
require_once 'Store/StorePaymentRequest.php';
require_once 'Braintree.php';

/**
 * @package   Store
 * @copyright 2011-2015 silverorange
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
 */
class StoreBraintreePaymentProvider extends StorePaymentProvider
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $environment;

	/**
	 * @var string
	 */
	protected $merchant_id;

	/**
	 * @var string
	 */
	protected $public_key;

	/**
	 * @var string
	 */
	protected $private_key;

	/**
	 * @var string
	 */
	protected $order_id_prefix;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new payment provider using the Braintree API
	 *
	 * Available parameters are:
	 *
	 * <kbd>environment</kbd>     - optional. Transaction mode to use.
	 *                              Must be one of either 'production'
	 *                              or 'sandbox'. If not specified,
	 *                              'sandbox' is used.
	 * <kbd>merchant_id</kbd>     - required. Login identifier for
	 *                              authentication.
	 * <kbd>public_key</kbd>      - required. Transaction key for
	 *                              authentication.
	 * <kbd>private_key</kbd>     - required. Transaction key for
	 *                              authentication.
	 * <kbd>order_id_prefix</kbd> - optional. Prefix for the order id
	 *                              used for the description sent to
	 *                              Authorize.net. If not set, the
	 *                              order id will be prefixed with
	 *                              "Order".
	 *
	 * @throws StoreException if a required parameter is missing or if the
	 *                        'environment' paramater is not valid.
	 */
	public function __construct(array $parameters = array())
	{
		$required_parameters = array(
			'merchant_id',
			'public_key',
			'private_key',
		);

		foreach ($required_parameters as $parameter) {
			if (!isset($parameters[$parameter])) {
				throw new StoreException(
					'"'.$parameter.'" is required in the Braintree payment '.
					'provider parameters.'
				);
			}
		}

		if (!isset($parameters['environment'])) {
			$parameters['environment'] = 'sandbox';
		}

		$valid_environments = array('production', 'sandbox');
		if (!in_array($parameters['environment'], $valid_environments)) {
			throw new StoreException(
				'Environment "'.$environment.'" is not valid for the '.
				'Braintree payment provider.'
			);
		}

		$this->merchant_id = $parameters['merchant_id'];
		$this->public_key  = $parameters['public_key'];
		$this->private_key = $parameters['private_key'];
		$this->environment = $parameters['environment'];

		$this->order_id_prefix =
			(isset($parameters['order_id_prefix']))
			? $parameters['order_id_prefix']
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
		$request = array(
			'amount' => $this->formatCurrency($order->total),
			'orderId' => $this->getOrderId($order),
			'creditCard' => $this->getCreditCard(
				$order,
				$card_number,
				$card_verification_value
			),
			'merchantAccountId' => $this->merchant_id,
			'options' => array(
				'submitForSettlement' => true,
			),
		);

		if ($order->billing_address instanceof StoreOrderAddress) {
			$request['billing'] = $this->getBillingAddress(
				$order->billing_address
			);
		}

		if ($order->account instanceof StoreAccount) {
			$request['customer'] = $this->getCustomer($order->account);
		}

		// do transaction
		try {
			$this->setConfig();
			$response = Braintree_Transaction::sale($request);

			if (count($response->errors) > 0) {
				foreach ($response->errors as $error) {
				}
			}
		} catch (Braintree_Exception $e) {
			throw $e;
		}

/*
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
*/
		$class_name = SwatDBClassMap::get('StorePaymentMethodTransaction');
		$transaction = new $class_name();

		$transaction->transaction_type = StorePaymentRequest::TYPE_PAY;
		$transaction->transaction_id = $response->transaction_id;
		$transaction->createdate = new SwatDate();
		$transaction->createdate->toUTC();

		return $transaction;
	}

	// }}}
	// {{{ protected function setConfig()

	protected function setConfig()
	{
		Braintree_Configuration::environment($this->environment);
		Braintree_Configuration::merchantId($this->merchant_id);
		Braintree_Configuration::publicKey($this->public_key);
		Braintree_Configuration::privateKey($this->private_key);
	}

	// }}}
	// {{{ protected function getCreditCard()

	/**
	 * @sensitive $card_number
	 * @sensitive $card_verification_value
	 */
	protected function getCreditCard(
		StoreOrder $order, $card_number, $card_verification_value = null)
	{
		// get expiration date and cardholder from payment method
		$date = new SwatDate('-1 month');
		$name = '';
		foreach ($order->payment_methods as $payment_method) {
			if ($payment_method->getUnencryptedCardNumber() == $card_number) {
				$date = clone $payment_method->card_expiry;
				$name = $payment_method->card_fullname;
				break;
			}
		}

		return array(
			'cardholderName' => $this->truncateField($name, 175),
			'cvv'            => $card_verification_value,
			'expirationDate' => $date->formatLikeIntl('MM/yy'),
			'number'         => $card_number,
		);
	}

	// }}}
	// {{{ protected function getBillingAddress()

	protected function getBillingAddress(StoreOrderAddress $address)
	{
		if ($address->provstate_other != null) {
			$region = $address->provstate_other;
		} elseif ($address->provstate instanceof StoreProvState) {
			$region = $address->provstate->abbreviation;
		}

		$lines = explode("\n", $address->line1);
		if (count($lines) === 1) {
			$line1 = $address->line1;
			$line2 = $address->line2;
		} else {
			$line1 = $lines[0];
			$line2 = $lines[1];
		}

		$names = $this->getAddressNames($address);

		$request = array(
			'countryCodeAlpha2' => $address->country->id,
			'firstName' => $this->truncateField($names['first'], 255),
			'locality' => $this->truncateField($address->city, 255),
			'postalCode' => $address->postal_code,
			'region' => $this->truncateField($region, 255),
			'streetAddress' => $this->truncateField($line1, 255),
		);

		if ($names['last'] != '') {
			$request['lastName'] = $this->truncateField(
				$address->last_name,
				255
			);
		}

		if ($line2 != '') {
			$request['extendedAddress'] = $this->truncateField($line2, 255);
		}

		if ($address->company) {
			$request['company'] = $this->truncateField($address->company, 255);
		}

		return $request;
	}

	// }}}
	// {{{ protected function getCustomer()

	protected function getCustomer(StoreAccount $account)
	{
		$names = $this->getAccountNames($account);

		$request = array(
			'firstName' => $this->truncateField($names['first'], 255),
		);

		if ($names['last'] != '') {
			$request['lastName'] = $this->truncateField($names['last'], 255);
		}

		if ($account->company != '') {
			$request['company'] = $this->truncateField($account->company, 255);
		}

		if ($account->phone != '') {
			$request['phone'] = $this->truncateField($account->phone, 255);
		}

		if ($account->email != '') {
			$request['email'] = $this->truncateField($account->email, 255);
		}

		return $request;
	}

	// }}}
	// {{{ protected function getOrderId()

	protected function getOrderId(StoreOrder $order)
	{
		return $this->order_id_prefix.' '.$order->id;
	}

	// }}}
	// {{{ protected function getAddressNames()

	protected function getAddressNames(StoreOrderAddress $address)
	{
		return $this->splitFullName($address->fullname);
	}

	// }}}
	// {{{ protected function getAccountNames()

	protected function getAccountNames(StoreAccount $account)
	{
		return $this->splitFullName($account->fullname);
	}

	// }}}
	// {{{ protected function formatCurrency()

	/**
	 * @param float $value
	 *
	 * @return string
	 */
	protected function formatCurrency($value)
	{
		$value = round($value, 2);
		return number_format($value, 2, '.', '');
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
	// {{{ private function splitFullname()

	private function splitFullname($full_name)
	{
		$parts = explode(' ', $full_name, 2);

		if (count($parts) === 2) {
			$first = trim($parts[0]);
			$last = trim($parts[1]);
		} else {
			$first = trim($parts[0]);
			$last = '';
		}

		return array(
			'first' => $first,
			'last'  => $last,
		);
	}

	// }}}
}

?>
