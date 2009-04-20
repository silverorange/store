<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * A payment method for an order for an e-commerce Web application
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentMethod
 * @see       StorePaymentMethodTransaction
 * @see       StorePaymentType
 * @see       StoreCardType
 */
class StoreOrderPaymentMethod extends StorePaymentMethod
{
	// {{{ public properties

	/**
	 * Card Verification Value (CVV)
	 *
	 * This value is encrypted using GPG encryption. The only way the number
	 * may be retrieved is by using GPG decryption with the correct GPG private
	 * key.
	 *
	 * NOTE: Visa forbids storing this code after authorization is obtained.
	 * PCI compliance standards forbit storing this code altogether. This class
	 * supports storing the CVV only for offline authorization. The code must
	 * be deleted from the database upon authorization.
	 *
	 * @var string
	 *
	 * @see StoreOrderPaymentMethod::setCardVerificationValue()
	 */
	public $card_verification_value;

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

	// }}}
	// {{{ protected properties

	/**
	 * Id of the account payment method this order payment method was created
	 * from
	 *
	 * @var integer
	 */
	protected $account_payment_method_id = null;

	/**
	 * The card verification value of this payment method
	 *
	 * Note: This should NEVER be saved. Not ever.
	 *
	 * @var string
	 *
	 * @see StoreOrderPaymentMethod::getUnencryptedCardVerificationValue()
	 */
	protected $unencrypted_card_verification_value = '';

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

	// }}}
	// {{{ public function setCardVerificationValue()

	/**
	 * Sets the card verification value (CVV) of this payment method
	 *
	 * When setting the CVV, use this method rather than modifying the public
	 * {@link StorePaymentMethod::$card_verification_value} property.
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
	 * @param boolean $store_encrypted optional flag to store a GPG encrypted
	 *                                  version of the CVV in the public
	 *                                  {@link StoreOrderPaymentMethod::$card_verification_value}
	 *                                  property. The encrypted value will be
	 *                                  saved in the database when this object
	 *                                  is saved. If not specified, the
	 *                                  encrypted version is <em>not</em>
	 *                                  stored.
	 *
	 * @see StoreOrderPaymentMethod::getUnencryptedCardVerificationValue()
	 */
	public function setCardVerificationValue($value, $store_unencrypted = true,
		$store_encrypted = false)
	{

		if ($store_encrypted) {
			/*
			 * We throw an exception here to prevent silent failures when
			 * saving payment information. Sites that do not require GPG
			 * encryption should use the third parameter of this method to
			 * not store the encrypted value.
			 */
			if ($this->gpg_id === null) {
				throw new StoreException('GPG ID is required.');
			}

			$gpg = $this->getGPG();
			$this->card_verification_value = self::encrypt($gpg, $value,
				$this->gpg_id);
		}

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
		return ($this->card_verification_value != '' ||
			$this->unencrypted_card_verification_value != '');
	}

	// }}}
	// {{{ public function getCardVerificationValue()

	/**
	 * @param $passphrase the passphrase required for decrypting the card
	 *                     verification value.
	 *
	 * @return string the unencrypted card verification value.
	 *
	 * @sensitive $passphrase
	 */
	public function getCardVerificationValue($passphrase)
	{
		$value = null;

		if ($this->card_verification_value !== null) {
			$gpg = $this->getGPG();
			$value = self::decrypt($gpg, $this->card_verification_value,
				$this->gpg_id, $passphrase);
		}

		return $value;
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

		if ($method instanceof StoreOrderPaymentMethod)
			$this->card_verification_value = $method->card_verification_value;
	}

	// }}}
	// {{{ public function duplicate()

	public function duplicate()
	{
		$new_payment_method = parent::duplicate();

		$card_verification_value = $this->getUnencryptedCardVerificationValue();
		if ($card_verification_value != '') {
			$new_payment_method->setCardVerificationValue(
				$card_verification_value, true, false);
		}

		return $new_payment_method;
	}

	// }}}
	// {{{ public function displayAmount()

	public function displayAmount()
	{
		if ($this->amount !== null) {
			$locale = SwatI18NLocale::get();
			echo $locale->formatCurrency($this->amount);
		}
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
		$properties = parent::getSerializablePrivateProperties();
		$properties[] = 'card_verification_value';
		$properties[] = 'unencrypted_card_verification_value';
		$properties[] = 'account_payment_method_id';
		$properties[] = 'max_amount';
		$properties[] = 'tag';

		return $properties;
	}

	// }}}
	// {{{ protected function displayCard()

	protected function displayCard($passphrase)
	{
		parent::displayCard($passphrase);

		$cvv_span = new SwatHtmlTag('span');
		$has_cvv = false;

		if ($this->payment_type->isCard() &&
			$this->gpg_id !== null && $passphrase !== null) {
			$display_card = true;

			$card_verification_value =
				$this->getCardVerificationValue($passphrase);

			if ($card_verification_value !== null) {
				$has_cvv = true;
				$cvv_span->setContent(sprintf('(CVV: %s)',
					$card_verification_value));
			}
		}

		if ($has_cvv) {
			$cvv_span->class = 'store-payment-method-card-verification-value';
			echo ' ';
			$cvv_span->display();
		}
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
