<?php

/**
 * A payment method for an order for an e-commerce Web application
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentMethod
 * @see       StorePaymentMethodTransaction
 * @see       StorePaymentType
 * @see       StoreCardType
 * @see       StoreVoucher
 */
class StoreOrderPaymentMethod extends StorePaymentMethod
{
	// {{{ public properties

	/**
	 * Optional amount to charge to this payment method
	 *
	 * @var float
	 */
	public $amount;

	/**
	 * Whether this payment method is adjustable when calculating multiple
	 * payment amounts.
	 *
	 * @var boolean
	 */
	public $adjustable = false;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * Additional field for voucher code
	 *
	 * Used for gift certificatess, merchandise credits and coupons.
	 *
	 * @var string
	 */
	public $voucher_code;

	/**
	 * Additional field for voucher type
	 *
	 * Used for gift certificatess, merchandise credits and coupons.
	 *
	 * @var integer
	 */
	public $voucher_type;

	// }}}
	// {{{ protected properties

	/**
	 * Id of the account payment method this order payment method was created
	 * from
	 *
	 * @var integer
	 */
	protected $account_payment_method_id;

	/**
	 * The card verification value of this payment method
	 *
	 * Note: This should NEVER be saved. Not ever.
	 *
	 * @var string
	 *
	 * @see StoreOrderPaymentMethod::getUnencryptedCardVerificationValue()
	 */
	protected $unencrypted_card_verification_value;

	/**
	 * The max amount this payment method can be adjusted to if it is
	 * adjustable.
	 *
	 * @var float
	 */
	protected $max_amount;

	/**
	 * Tag used to identify this payment method before it is saved and has a
	 * database id.
	 *
	 * @var string
	 */
	protected $tag;

	/**
	 * @var string
	 *
	 * @see StoreOrderPaymentMethod::setPayPalToken()
	 * @see StoreOrderPaymentMethod::getPayPalToken()
	 */
	protected $paypal_token;

	// }}}
	// {{{ public function setCardVerificationValue()

	/**
	 * Sets the card verification value (CVV) of this payment method
	 *
	 * NOTE: Visa forbids storing this code after authorization is obtained.
	 * PCI compliance standards forbit storing this code altogether. This class
	 * supports storing the CVV only for offline authorization. The code must
	 * be deleted from the database upon authorization.
	 *
	 * @param string $value the card verification value.
	 * @param boolean $store_unencrypted optional flag to store an uncrypted
	 *                                    version of the CVV as an internal
	 *                                    property. This value is never saved
	 *                                    in the database but can be retrieved
	 *                                    for the lifetime of this object using
	 *                                    the {@link StoreOrderPaymentMethod::getUnencryptedCardVerificationValue()}
	 *                                    method. If not specified, the
	 *                                    unencrypted version is set.
	 *
	 * @see StoreOrderPaymentMethod::getUnencryptedCardVerificationValue()
	 */
	public function setCardVerificationValue($value, $store_unencrypted = true)
	{
		if ($store_unencrypted) {
			$this->unencrypted_card_verification_value = strval($value);
		}
	}

	// }}}
	// {{{ public function hasCardVerificationValue()

	/**
	 * @return boolean true if this payment method has a card verification
	 *                 value and false if it does not. Either an encrypted or
	 *                 unencrypted version of the value counts.
	 */
	public function hasCardVerificationValue()
	{
		return ($this->unencrypted_card_verification_value != '');
	}

	// }}}
	// {{{ public function getUnencryptedCardVerificationValue()

	/**
	 * Gets the unencrypted card verification value (CVV) of this payment
	 * method
	 *
	 * @return string the unencrypted card verification value of this payment
	 *                method.
	 *
	 * @see StorePaymentMethod::setCardVerificationValue()
	 */
	public function getUnencryptedCardVerificationValue()
	{
		return $this->unencrypted_card_verification_value;
	}

	// }}}
	// {{{ public function getAccountPaymentMethodId()

	public function getAccountPaymentMethodId()
	{
		return $this->account_payment_method_id;
	}

	// }}}
	// {{{ public function setAdjustable()

	public function setAdjustable($value = true)
	{
		$this->adjustable = $value;
	}

	// }}}
	// {{{ public function isAdjustable()

	public function isAdjustable()
	{
		return $this->adjustable;
	}

	// }}}
	// {{{ public function setMaxAmount()

	public function setMaxAmount($amount)
	{
		$this->max_amount = $amount;
	}

	// }}}
	// {{{ public function getMaxAmount()

	public function getMaxAmount()
	{
		return $this->max_amount;
	}

	// }}}
	// {{{ public function setTag()

	public function setTag($tag)
	{
		$this->tag = $tag;
	}

	// }}}
	// {{{ public function getTag()

	public function getTag()
	{
		if ($this->tag === null)
			$this->tag = uniqid();

		return $this->tag;
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StorePaymentMethod $method)
	{
		parent::copyFrom($method);

		if ($method instanceof StoreAccountPaymentMethod)
			$this->account_payment_method_id = $method->id;
	}

	// }}}
	// {{{ public function duplicate()

	public function duplicate()
	{
		$new_payment_method = parent::duplicate();

		$fields = array(
			'unencrypted_card_verification_value',
			'account_payment_method_id',
			'max_amount',
			'tag',
			'paypal_token',
		);

		foreach ($fields as $field) {
			$new_payment_method->$field = $this->$field;
		}

		return $new_payment_method;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'OrderPaymentMethod';
		$this->registerInternalProperty('ordernum',
			SwatDBClassMap::get('StoreOrder'));
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(
			parent::getSerializablePrivateProperties(),
			array(
				'unencrypted_card_verification_value',
				'account_payment_method_id',
				'max_amount',
				'tag',
				'paypal_token',
			)
		);
	}

	// }}}

	// display methods
	// {{{ public function displayAmount()

	public function displayAmount()
	{
		if ($this->amount !== null) {
			$locale = SwatI18NLocale::get();
			echo $locale->formatCurrency($this->amount);
		}
	}

	// }}}
	// {{{ protected function displayInternal()

	protected function displayInternal($display_details = true)
	{
		if ($this->payment_type->isVoucher()) {
			$this->displayVoucher();
		} else {
			parent::displayInternal($display_details);
		}
	}

	// }}}
	// {{{ protected function displayVoucher()

	protected function displayVoucher()
	{
		switch ($this->voucher_type) {
		case 'gift-certificate':
			$type = Store::_('Gift Certificate');
			break;

		case 'merchandise-credit':
			$type = Store::_('Merchandise Voucher');
			break;

		case 'coupon':
			$type = Store::_('Coupon');
			break;

		default :
			$type = Store::_('Voucher');
			break;
		}

		printf(
			Store::_('%s #%s'),
			$type,
			$this->voucher_code
		);
	}

	// }}}

	// PayPal fields
	// {{{ public function setPayPalToken()

	public function setPayPalToken($token)
	{
		$this->paypal_token = strval($token);
	}

	// }}}
	// {{{ public function getPayPalToken()

	public function getPayPalToken()
	{
		return $this->paypal_token;
	}

	// }}}
	// {{{ public function hasPayPalToken()

	public function hasPayPalToken()
	{
		return ($this->paypal_token != '');
	}

	// }}}

	// loader methods
	// {{{ protected function loadTransactions()

	protected function loadTransactions()
	{
		$sql = sprintf('select * from PaymentMethodTransaction
			where payment_method = %s
			order by createdate, id',
			$this->db->quote($this->id, 'integer'));

		$transactions = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StorePaymentMethodTransactionWrapper'));

		return $transactions;
	}

	// }}}
}

?>
