<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StorePaymentType.php';
require_once 'Crypt/GPG.php';

/**
 * A payment method for an ecommerce web application
 *
 * Payment methods are usually tied to {@link StoreAccount} objects or
 * {@link StoreOrder} objects.
 *
 * A payment method represents a way to pay for a purchase. A payment method
 * stores the type of payment (VISA, MC, COD) as well as necessary payment
 * details such as name, card number and expiry date.
 *
 * Sensitive fields such as credit card numbers are stored using GPG
 * encryption.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentType
 */
abstract class StorePaymentMethod extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Payment method identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Full name on the credit card
	 *
	 * @var string
	 */
	public $credit_card_fullname;

	/**
	 * Last 4 digits of the credit card
	 *
	 * This value is stored unencrypted and is displayed to the customer to
	 * allow the customer to identify his or her credit cards.
	 *
	 * @var string
	 */
	public $credit_card_last4;

	/**
	 * Number of the credit card
	 *
	 * This value is encrypted using GPG encryption. The only way the number
	 * may be retrieved is by using GPG decryption with the correct GPG private
	 * key.
	 *
	 * @var string
	 */
	public $credit_card_number;

	/**
	 * The expiry date of the credit card
	 *
	 * @var Date
	 */
	public $credit_card_expiry;

	/**
	 * The inception date of the credit card
	 *
	 * This is required for some debit cards.
	 *
	 * @var Date
	 */
	public $card_inception;

	/**
	 * The issue number for Switch and Solo debit cards
	 *
	 * This is a 1 or 2 character string containing the issue number exactly as
	 * it appears on the card. Note: This is a string not an integer. An issue
	 * number of '04' is different than an issue number of '4' and both numbers
	 * are valid issue numbers.
	 *
	 * @var string
	 */
	public $card_issue_number;

	// }}}
	// {{{ protected properties

	/**
	 * The GPG id used to encrypt credit card numbers for this payment method
	 *
	 * Subclasses should set this value in their
	 * {@link StorePaymentMethod::init()} or
	 * {@link StorePaymentMethod::__construct()} methods.
	 *
	 * @var string
	 */
	protected $gpg_id = null;

	/**
	 * The card verification value of this payment method
	 *
	 * @var string
	 *
	 * @see StorePaymentMethod::getCardVerificationValue()
	 */
	protected $card_verification_value = '';

	/**
	 * The unencrypted card number of this payment method
	 *
	 * @var string
	 *
	 * @see StorePaymentMethod::getUnencryptedCardNumber()
	 */
	protected $unencrypted_card_number = '';

	// }}}
	// {{{ public function setCreditCardNumber()

	/**
	 * Sets the credit card number of this payment method
	 *
	 * When setting the credit card number, use this method rather than
	 * modifying the public {@link StorePaymentMethod::$credit_card_number}
	 * property.
	 *
	 * Credit card numbers are stored encrypted. There is no way to retrieve
	 * the actual credit card number after setting it without knowing the GPG
	 * private key needed to decrypt the credit card number.
	 *
	 * @param string $number the new credit card number.
	 * @param boolean $store_unencrypted optional flag to store an uncrypted
	 *                                    version of the card number as an
	 *                                    internal property. This value is
	 *                                    never saved in the database but can
	 *                                    be retrieved for the lifetime of
	 *                                    this object using the
	 *                                    {@link StorePaymentMethod::getUnencryptedCardNumber()}
	 *                                    method.
	 *                                   
	 *
	 * @throws StoreException a StoreException is thrown if this class has no
	 *                         defined GPG id and you try to set the credit
	 *                         card number.
	 */
	public function setCreditCardNumber($number, $store_unencrypted = false)
	{
		if ($this->gpg_id === null)
			throw new StoreException('No GPG id provided.');

		$this->credit_card_last4 = substr($number, -4);

		$this->credit_card_number =
			self::encryptCreditCardNumber($number, $this->gpg_id);

		if ($store_unencrypted)
			$this->unencrypted_card_number = (string)$number;
	}

	// }}}
	// {{{ public function getCreditCardNumber()

	public function getCreditCardNumber(Crypt_GPG $gpg, $passphrase)
	{
		$credit_card_number = $this->credit_card_number;

		if ($credit_card_number !== null)
			$credit_card_number = self::decryptCreditCardNumber(
				$credit_card_number, $gpg, $passphrase);

		return $credit_card_number;
	}

	// }}}
	// {{{ public function getUnencryptedCardNumber()

	/**
	 * Gets the unencrypted card number stored in this payment method
	 *
	 * The card number must have been stored in this payment method using the
	 * <i>$store_unencrypted</i> paramater on the
	 * {@link StorePaymentMethod::setCreditCardNumber()} method.
	 *
	 * @return string the unencrypted card number stored in this payment method.
	 */
	public function getUnencryptedCardNumber()
	{
		return $this->unencrypted_card_number;
	}

	// }}}
	// {{{ public function setCardVerificationValue()

	/**
	 * Sets the card verification value (CVV) of this payment method
	 *
	 * Due to VISA rules on how CVVs are processed, CVVs are never saved in
	 * the database. This method sets an internal property that may be accessed
	 * for the lifetime of this object using the
	 * {@link StorePaymentMethod::getCardVerificationValue()} method.
	 *
	 * @param string $value the card verification value.
	 *
	 * @see StorePaymentMethod::getCardVerificationValue()
	 */
	public function setCardVerificationValue($value)
	{
		$this->card_verification_value = (string)$value;
	}

	// }}}
	// {{{ public function getCardVerificationValue()

	/**
	 * Gets the card verification value (CVV) of this payment method
	 *
	 * @return string the card verification value of this payment method.
	 *
	 * @see StorePaymentMethod::setCardVerificationValue()
	 */
	public function getCardVerificationValue()
	{
		return $this->card_verification_value;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this payment method formatted
	 */
	public function display()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'store-payment-method';
		$span_tag->open();

		echo SwatString::minimizeEntities($this->payment_type->title);

		if ($this->credit_card_last4 !== null) {
			echo ': ';
			$span_tag->class = 'store-payment-method-credit-card-number';
			$span_tag->setContent(StorePaymentType::formatCreditCardNumber(
				$this->credit_card_last4,
				$this->payment_type->getCreditCardMaskedFormat()));

			$span_tag->display();
		}

		if ($this->credit_card_expiry !== null ||
			$this->credit_card_fullname !== null) {

			echo '<br />';

			$span_tag->class = 'store-payment-method-info';
			$span_tag->open();

			if ($this->credit_card_expiry !== null) {
				echo 'Expiry: ',
					$this->credit_card_expiry->format(SwatDate::DF_CC_MY);

				if ($this->credit_card_fullname !== null)
					echo ', ';
			}

			if ($this->credit_card_fullname !== null)
				echo SwatString::minimizeEntities($this->credit_card_fullname);

			$span_tag->close();
		}

		$span_tag->close();
	}

	// }}}
	// {{{ public function displayAsText()

	/**
	 * Displays this payment method formatted
	 *
	 * This method is ideal for email.
	 */
	public function displayAsText()
	{
		echo $this->payment_type->title;

		if ($this->credit_card_last4 !== null) {
			echo "\n";
			echo StorePaymentType::formatCreditCardNumber(
				$this->credit_card_last4,
				$this->payment_type->getCreditCardMaskedFormat());
		}

		if ($this->credit_card_expiry !== null) {
			echo "\nExpiry: ",
				$this->credit_card_expiry->format(SwatDate::DF_CC_MY);
		}

		if ($this->credit_card_fullname !== null) {
			echo "\n",
				$this->credit_card_fullname;
		}
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StorePaymentMethod $method)
	{
		$this->credit_card_fullname = $method->credit_card_fullname;
		$this->credit_card_last4    = $method->credit_card_last4;
		$this->credit_card_number   = $method->credit_card_number;
		$this->credit_card_expiry   = $method->credit_card_expiry;
		$this->payment_type         = $method->getInternalValue('payment_type');
	}

	// }}}
	// {{{ public static function encryptCreditCardNumber()

	/**
	 * Encrypts a credit card number using GPG encryption
	 *
	 * @param string $number the credit card number to encrypt.
	 * @param string $gpg_id the GPG id to encrypt with.
	 *
	 * @return string the encrypted credit card number.
	 */
	public static function encryptCreditCardNumber($number, $gpg_id)
	{
		$gpg = new Crypt_GPG();
		return $gpg->encrypt($number, $gpg_id);
	}

	// }}}
	// {{{ public static function decryptCreditCardNumber()

	public static function decryptCreditCardNumber($encrypted_number,
		Crypt_GPG $gpg, $passphrase)
	{
		$decrypted_data = $gpg->decrypt($encrypted_number, $passphrase);
		return $decrypted_data;
	}

	// }}}
	// {{{  public static function formatCreditCardNumber()

	/**
	 * Formats a credit card number according to a format string
	 *
	 * Format strings may contain spaces, hash marks or stars.
	 * ' ' - inserts a space at this position
	 * '*' - displays a * at this position
	 * '#' - displays the number at this position
	 *
	 * For example:
	 * <code>
	 * // displays '*** **6 7890'
	 * echo StorePaymentMethod::formatCreditCardNumber(1234567890,
	 *      '*** **# ####');
	 * </code>
	 *
	 * @param string $number the creditcard number to format.
	 * @param string $format the format string to use.
	 * @param boolean $zero_fill whether or not the prepend the credit card
	 *                            number with zeros until it is as long as the
	 *                            format string.
	 *
	 * @return string the formatted credit card number.
	 *
	 * @see StorePaymentType::formatCreditCardNumber()
	 *
	 * @deprecated Use StorePaymentType::formatCreditCardNumber() instead. This
	 *             method will be removed in future versions of Store.
	 */
	public static function formatCreditCardNumber($number,
		$format = '#### #### #### ####', $zero_fill = true)
	{
		return StorePaymentType::formatCreditCardNumber($number, $format,
			$zero_fill);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';
		$this->registerInternalProperty('payment_type',
			$this->class_map->resolveClass('StorePaymentType'));

		$this->registerDateProperty('credit_card_expiry');
		$this->registerDateProperty('card_inception');
	}

	// }}}
}

?>
