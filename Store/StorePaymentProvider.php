<?php

require_once 'Store/dataobjects/StorePaymentMethodTransaction.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Class to manage automated card transactions for e-commerce stores
 *
 * This class implements the factory pattern to make it possible to change
 * payment providers with minimal alterations to site code.
 *
 * Example usage:
 * <code>
 * $paramaters = array(
 *     'Mode'   => 'test',
 *     'Vendor' => 'my-vendor-id',
 * );
 * $provider = StorePaymentProvider::factory('Protx', $paramaters);
 * $provider->setAvsMode();
 * $transaction = $provider->hold($order);
 * $transaction = $provider->release($transaction);
 * }
 * </code>
 *
 * @package   Store
 * @copyright 2007-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentProvider::factory()
 * @see       StorePaymentMethodTransaction
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

	/**
	 * Use Three Domain Secure (3-D Secure)
	 */
	const THREE_DOMAIN_SECURE_ON  = true;

	/**
	 * Don't use Three Domain Secure (3-D Secure)
	 */
	const THREE_DOMAIN_SECURE_OFF = false;

	// }}}
	// {{{ protected properties

	/**
	 * The Address Verification Service (AVS) mode
	 *
	 * One of either StorePaymentProvider::AVS_ON or
	 * StorePaymentProvider::AVS_OFF.
	 *
	 * @var boolean
	 * @see StorePaymentProvider::setAvsMode()
	 */
	protected $avs_mode = self::AVS_OFF;

	/**
	 * The Three Domain Secure (3-D Secure) mode
	 *
	 * One of either StorePaymentProvider::THREE_DOMAIN_SECURE_ON or
	 * StorePaymentProvider::THREE_DOMAIN_SECURE_OFF.
	 *
	 * @var boolean
	 * @see StorePaymentProvider::setThreeDomainSecureMode()
	 */
	protected $three_domain_secure_mode = self::THREE_DOMAIN_SECURE_OFF;

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
	// {{{ public function setThreeDomainSecureMode()

	/**
	 * Set the Three Domain Secure (3-D Secure) mode
	 *
	 * Using 3-D Secure (implemented by VISA as Verified by VISA and by
	 * MasterCard as MasterCard SecureCode) provides an additional level of
	 * card verification that usually causes a liability shift from the
	 * merchant to the credit-card company. Using 3-D Secure requires
	 * additional pages to be added to the checkout process. 3-D Secure never
	 * prevents transactions, it allows site code to decided whether or not to
	 * procede with the transaction if 3-D Secure is either not supported or
	 * failed for the card. 3-D Secure is not used by default.
	 *
	 * Not all payment providers support 3-D Secure transactions. If this is
	 * the case, setting the mode has no effect.
	 *
	 * See the Wikipedia article on
	 * {@link http://en.wikipedia.org/wiki/3-D_Secure 3-D Secure} for aditional
	 * details.
	 *
	 * @param boolean $mode optional. The 3-D Secure mode to use. One of either
	 *                       {@link StorePaymentProvider::THREE_DOMAIN_SECURE_ON} or
	 *                       {@link StorePaymentProvider::THREE_DOMAIN_SECURE_OFF}.
	 *                       If not specified, defaults to
	 *                       THREE_DOMAIN_SECURE_ON.
	 */
	public function setThreeDomainSecureMode(
		$mode = self::THREE_DOMAIN_SECURE_ON)
	{
		$this->three_domain_secure_mode = (boolean)$mode;
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
	 * @return StorePaymentMethodTransaction the transaction object for the
	 *                                        payment. This object contains the
	 *                                        transaction date and identifier.
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
	 * @param StorePaymentMethodTransaction $transaction the tranaction used to
	 *                                                    place a hold on funds.
	 *                                                    This should be a
	 *                                                    transaction returned
	 *                                                    by {@link StorePaymentProvider::hold()}.
	 *
	 * @return StorePaymentMethodTransaction a transaction object representing
	 *                                        the released transaction.
	 *
	 * @see StorePaymentProvider::hold()
	 */
	public function release(StorePaymentMethodTransaction $transaction)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
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
	 * @param StorePaymentMethodTransaction $transaction the tranaction used to
	 *                                                    place a hold on the
	 *                                                    funds. This should be
	 *                                                    a transaction returned
	 *                                                    by {@link StorePaymentProvider::hold()}.
	 *
	 * @return StorePaymentMethodTransaction a transaction object representing
	 *                                        the aborted transaction.
	 *
	 * @see StorePaymentProvider::hold()
	 */
	public function abort(StorePaymentMethodTransaction $transaction)
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
	 * @return StorePaymentMethodTransaction the transaction object containing
	 *                                        verification results. This
	 *                                        transaction may optionally be
	 *                                        used to pay for the order at a
	 *                                        later date.
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
	 * @param StorePaymentMethodTransaction $transaction the verified
	 *                                                    transaction to pay
	 *                                                    with. This should be a
	 *                                                    transaction returned
	 *                                                    from a call to
	 *                                                    {@link StorePaymentProvider::vefiry()}.
	 *
	 * @return StorePaymentMethodTransaction a transaction object representing
	 *                                        the verified payment transaction.
	 *
	 * @see StorePaymentProvider::verify()
	 */
	public function verifiedPay(StorePaymentMethodTransaction $transaction)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
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
	 * @param StorePaymentMethodTransaction $transaction the original
	 *                                                    transaction to refund.
	 * @param string $description optional. A description of why the refund is
	 *                             being made. If not specified, a blank string
	 *                             is used.
	 * @param double $amount optional. The amount to refund. This amount cannot
	 *                        exceed the original transaction value. If not
	 *                        specified, the amount defaults to the total value
	 *                        of the order for the original transaction.
	 *
	 * @return StorePaymentMethodTransaction a new transaction object
	 *                                        representing the refund
	 *                                        transaction.
	 */
	public function refund(StorePaymentMethodTransaction $transaction,
		$description = '', $amount = null)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
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
	 * Once a transaction is voided it cannot be refunded, released, repeated,
	 * aborted, or voided again.
	 *
	 * If this method does not throw an exception, the void was successful.
	 *
	 * @param StorePaymentMethodTransaction $transaction the tranaction to void.
	 *
	 * @return StorePaymentMethodTransaction a transaction object representing
	 *                                        the voided transaction.
	 */
	public function void(StorePaymentMethodTransaction $transaction)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	// }}}
	// {{{ public function threeDomainSecureAuth()

	/**
	 * Authenticates an existing 3-D Secure transaction
	 *
	 * After successful completion of the 3-D Secure transaction, the
	 * returned transaction object should be saved.
	 *
	 * @param StorePaymentMethodTransaction $transaction the original
	 *                                                    transaction initiated
	 *                                                    by the 3-D Secure
	 *                                                    authentication
	 *                                                    process. This
	 *                                                    transaction must
	 *                                                    contain the order id
	 *                                                    and merchant data of
	 *                                                    the original
	 *                                                    transaction.
	 * @param string $pares payer authentication response. The base64 encoded,
	 *                       encrypted message retrieved from the issuing bank
	 *                       for the transaction.
	 *
	 * @return StorePaymentMethodTransaction the authenticated transaction. The
	 *                                        authenticated transaction can be
	 *                                        released if the initial request
	 *                                        was a hold request.
	 */
	public function threeDomainSecureAuth(
		StorePaymentMethodTransaction $transaction, $pares)
	{
		require_once 'Store/exceptions/StoreUnimplementedException.php';
		throw new StoreUnimplementedException(sprintf(
			'%s does not implement the %s() method.',
			get_class($this), __FUNCTION__));
	}

	// }}}
}

?>
