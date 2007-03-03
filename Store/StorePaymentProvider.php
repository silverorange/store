<?php

require_once 'Store/dataobjects/StorePaymentTransaction.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Class to manage automated card transactions for e-commerce stores
 *
 * This class implements the factory pattern to make it easy to change payment
 * providers without needing to alter your site code.
 *
 * Example usage:
 * <code>
 * $paramaters = array(
 *     'Mode'   => 'test',
 *     'Vendor' => 'my-vendor-id',
 * );
 * $provider = StorePaymentProvider::factory('Protx', $paramaters);
 * $transaction = $provider->pay($order);
 * if ($transaction->address_status == StorePaymentTransaction::STATUS_FAILED) {
 *     echo 'Invalid billing address detected!';
 *     $provider->abort($transaction);
 * }
 * </code>
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider::factory();
 * @see       StorePaymentTransaction
 */
abstract class StorePaymentProvider
{
	// {{{ class constants

	/**
	 * Use Address Verification Service (AVS)
	 */
	const AVS_ON  = true;

	/**
	 * Don't use Address Verification Service (AVS)
	 */
	const AVS_OFF = false;

	// }}}
	// {{{ protected properties

	/**
	 * The Address Verification Service (AVS) mode
	 *
	 * One of either StorePaymentProvider::AVS_ON or
	 * StorePaymentProvider::AVS_OFF.
	 *
	 * @var boolean
	 */
	protected $avs_mode = self::AVS_OFF;

	// }}}
	// {{{ public static function factory()

	/**
	 * Creates a new payment provider instance
	 *
	 * This is the main mechanism for starting an online payment transaction.
	 *
	 * @param string $driver the payment provider driver to use.
	 * @param array $parameters an array of additional key-value parameters to
	 *                           pass to the payment provider driver.
	 *
	 * @return StorePaymentProvider a payment provider instance using the
	 *                               specified driver.
	 *
	 * @see StorePaymentProvider::__construct()
	 *
	 * @throws StoreException if the driver specified by <i>$driver</i> could
	 *                         not be loaded.
	 */
	public static function factory($driver, array $parameters = array())
	{
		static $loaded_drivers = array();

		if (array_key_exists($driver, $loaded_drivers)) {
			$class_name = $loaded_drivers[$driver];
		} else {
			$sanitized_driver = basename($driver);
			include_once 'Store/Store'.$sanitized_driver.'PaymentProvider.php';
			$class_name = 'Store'.$sanitized_driver.'PaymentProvider';

			if (!class_exists($class_name)) {
				throw new Exception(sprintf('No payment provider available '.
					'for driver %s', $driver));
			}

			$loaded_drivers[$sanitized_driver] = $class_name;
		}

		$reflector = new ReflectionClass($class_name);
		return $reflector->newInstance($parameters);
	}

	// }}}
	// {{{ abstract public function __construct()

	/**
	 * Creates a new payment provider
	 *
	 * @param array $paramaters an array of key-value pairs containing driver-
	 *                           specific constructor properties. See
	 *                           individual driver documentation for valid
	 *                           parameters.
	 */
	abstract public function __construct(array $paramaters);

	// }}}
	// {{{ public function setAvsMode()

	/**
	 * Set the Address Verification Service (AVS) mode
	 *
	 * Using AVS allows site code to validate transactions based on address and
	 * card verification value. Using AVS never prevents transactions, it just
	 * allows site code to decided whether or not to make a transaction. As
	 * such, it does not make much sense to use AVS with the
	 * {@link StorePaymentProvider::pay()} method. AVS is not used by default.
	 *
	 * @param boolean $mode optional. The AVS mode to use. One of either
	 *                       {@link StorePaymentProvider::AVS_ON} or
	 *                       {@link StorePaymentProvider::AVS_OFF}. If not
	 *                       specified, defaults to AVS_ON.
	 */
	public function setAvsMode($mode = self::AVS_ON)
	{
		$this->avs_mode = (boolean)$mode;
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
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
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
	 *
	 * @see StorePaymentProvider::release()
	 */
	public function hold(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	// }}}
	// {{{ public function release()

	/**
	 * Release funds held for an order payment
	 *
	 * @param StorePaymentTransaction $transaction the tranaction used to place
	 *                                              a hold on the funds. This
	 *                                              should be a transaction
	 *                                              returned by
	 *                                              {@link StorePaymentProvider::hold()}.
	 *
	 * @see StorePaymentProvider::hold()
	 */
	public function release(StorePaymentTransaction $transaction)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	// }}}
	// {{{ public function verify()

	/**
	 * Verifies a card payment for an order
	 *
	 * The payment is not actually made but all fraud prevention checks
	 * (AVS, 3D-Secure) are performed on the card. The resulting transaction
	 * object can then be used to pay for the order at a later date.
	 *
	 * @param StoreOrder $order the order to verify the card payment for.
	 * @param string $card_number the card number to use for payment.
	 * @param string $card_verification_value optional. Card verification value
	 *                                         used for fraud prevention.
	 *
	 * @return StorePaymentTransaction the transaction object containing the
	 *                                  verification results. This transaction
	 *                                  may optionally be used to pay for the
	 *                                  order at a later date.
	 *
	 * @see StorePaymentProvider::verifiedPay()
	 */
	public function verify(StoreOrder $order, $card_number,
		$card_verification_value = null)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	// }}}
	// {{{ public function verifiedPay()

	/**
	 * Pays for an order using an already verified transaction
	 *
	 * @param StorePaymentTranaction $transaction the verified transaction to
	 *                                             pay with. This should be a
	 *                                             transaction returned from a
	 *                                             {@link StorePaymentProvider::vefiry()}
	 *                                             call.
	 *
	 * @see StorePaymentProvider::verify()
	 */
	public function verifiedPay(StorePaymentTransaction $transaction)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	// }}}

	public function refund(StorePaymentTransaction $transaction, $amount = null)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	public function void(StorePaymentTransaction $transaction)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	public function abort(StorePaymentTransaction $transaction)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}
}

?>
