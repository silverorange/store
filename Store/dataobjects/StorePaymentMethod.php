<?php

require_once 'SwatDB/SwatDBDataObject.php';
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
 * Sensitive fields such as credit or debit card numbers are stored using GPG
 * encryption.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentType
 */
abstract class StorePaymentMethod extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Payment method identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Full name on the card
	 *
	 * @var string
	 */
	public $card_fullname;

	/**
	 * Last X digits of the card
	 *
	 * This value is stored unencrypted and is displayed to the customer to
	 * allow the customer to identify his or her cards.  Field length in the
	 * database is 6, but stored length is dependent on card type.
	 *
	 * @var string
	 */
	public $card_number_preview;

	/**
	 * Number of the card
	 *
	 * This value is encrypted using GPG encryption. The only way the number
	 * may be retrieved is by using GPG decryption with the correct GPG private
	 * key.
	 *
	 * @var string
	 */
	public $card_number;

	/**
	 * The expiry date of the card
	 *
	 * @var Date
	 */
	public $card_expiry;

	/**
	 * The inception date of the card
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
	 * The GPG id used to encrypt card numbers for this payment method
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
	// {{{ public function setCardNumber()

	/**
	 * Sets the card number of this payment method
	 *
	 * When setting the card number, use this method rather than modifying
	 * the public {@link StorePaymentMethod::$card_number} property.
	 *
	 * Card numbers are stored encrypted. There is no way to retrieve the actual
	 * card number after setting it without knowing the GPG private key needed
	 * to decrypt the card number.
	 *
	 * @param string $number the new card number.
	 * @param boolean $store_unencrypted optional flag to store an uncrypted
	 *                                    version of the card number as an
	 *                                    internal property. This value is
	 *                                    never saved in the database but can
	 *                                    be retrieved for the lifetime of
	 *                                    this object using the
	 *                                    {@link StorePaymentMethod::getUnencryptedCardNumber()}
	 *                                    method.
	 *
	 * @todo make this smart based on card type.
	 */
	public function setCardNumber($number, $store_unencrypted = false)
	{
		$this->card_number_preview = substr($number, -4);

		if ($this->gpg_id !== null)
			$this->card_number =
				self::encryptCardNumber($number, $this->gpg_id);

		if ($store_unencrypted)
			$this->unencrypted_card_number = (string)$number;
	}

	// }}}
	// {{{ public function getCardNumber()

	public function getCardNumber(Crypt_GPG $gpg, $passphrase)
	{
		$card_number = $this->card_number;

		if ($card_number !== null)
			$card_number = self::decryptCardNumber(
				$card_number, $gpg, $passphrase);

		return $card_number;
	}

	// }}}
	// {{{ public function getUnencryptedCardNumber()

	/**
	 * Gets the unencrypted card number stored in this payment method
	 *
	 * The card number must have been stored in this payment method using the
	 * <i>$store_unencrypted</i> paramater on the
	 * {@link StorePaymentMethod::setCardNumber()} method.
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
	 * Displays this payment method
	 *
	 * @param boolean $display_details optional. Include additional details
	 *                                  for card-type payment methods.
	 */
	public function display($display_details = true)
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'store-payment-method';
		$span_tag->open();

		echo SwatString::minimizeEntities(
			$this->payment_type->title);

		if ($this->card_number_preview !== null) {
			echo ': ';
			$span_tag->class = 'store-payment-method-card-number';
			$span_tag->setContent(StorePaymentType::formatCardNumber(
				$this->card_number_preview,
				$this->payment_type->getCardMaskedFormat()));

			$span_tag->display();
		}

		if ($display_details &&
			($this->card_expiry !== null || $this->card_fullname !== null)) {

			echo '<br />';

			$span_tag->class = 'store-payment-method-info';
			$span_tag->open();

			if ($this->card_expiry !== null) {
				echo 'Expiry: ',
					$this->card_expiry->format(SwatDate::DF_CC_MY);

				if ($this->card_fullname !== null)
					echo ', ';
			}

			if ($this->card_fullname !== null)
				echo SwatString::minimizeEntities($this->card_fullname);

			$span_tag->close();
		}

		$span_tag->close();
	}

	// }}}
	// {{{ public function displayAsText()

	/**
	 * Displays this payment method
	 *
	 * This method is ideal for email.
	 *
	 * @param boolean $display_details optional. Include additional details
	 *                                  for card-type payment methods.
	 * @param string $line_break optional. The character or characters used to
	 *                            represent line-breaks in the text display of
	 *                            this payment method.
	 */
	public function displayAsText($display_details = true, $line_break = "\n")
	{
		echo $this->payment_type->title;

		if ($this->card_number_preview !== null) {
			echo $line_break, StorePaymentType::formatCardNumber(
				$this->card_number_preview,
				$this->payment_type->getCardMaskedFormat());
		}

		if ($display_details) {
			if ($this->card_expiry !== null) {
				echo $line_break, 'Expiry: ',
					$this->card_expiry->format(SwatDate::DF_CC_MY);
			}

			if ($this->card_fullname !== null) {
				echo $line_break, $this->card_fullname;
			}
		}
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StorePaymentMethod $method)
	{
		$this->card_fullname        = $method->card_fullname;
		$this->card_number_preview  = $method->card_number_preview;
		$this->card_number          = $method->card_number;
		$this->card_expiry          = $method->card_expiry;
		$this->payment_type         = $method->getInternalValue('payment_type');
	}

	// }}}
	// {{{ public function duplicate()

	public function duplicate()
	{
		$new_payment_method = parent::duplicate();

		$new_payment_method->setCardVerificationValue(
			$this->getCardVerificationValue());

		$card_number = $this->getUnencryptedCardNumber();

		if ($card_number !== null)
			$new_payment_method->setCardNumber($card_number, true);

		return $new_payment_method;
	}

	// }}}
	// {{{ public static function encryptCardNumber()

	/**
	 * Encrypts a card number using GPG encryption
	 *
	 * @param string $number the card number to encrypt.
	 * @param string $gpg_id the GPG id to encrypt with.
	 *
	 * @return string the encrypted card number.
	 */
	public static function encryptCardNumber($number, $gpg_id)
	{
		$gpg = new Crypt_GPG();
		return $gpg->encrypt($number, $gpg_id);
	}

	// }}}
	// {{{ public static function decryptCardNumber()

	public static function decryptCardNumber($encrypted_number,
		Crypt_GPG $gpg, $passphrase)
	{
		$decrypted_data = $gpg->decrypt($encrypted_number, $passphrase);
		return $decrypted_data;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';
		$this->registerInternalProperty('payment_type',
			SwatDBClassMap::get('StorePaymentType'));

		$this->registerDateProperty('card_expiry');
		$this->registerDateProperty('card_inception');
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		$properties = parent::getSerializablePrivateProperties();
		$properties[] = 'card_verification_value';
		$properties[] = 'unencrypted_card_number';
		return $properties;
	}

	// }}}
}

?>
