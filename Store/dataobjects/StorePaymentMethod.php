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
 * @copyright 2006 silverorange
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
	 *
	 * @throws StoreException a StoreException is thrown if this class has no
	 *                         defined GPG id and you try to set the credit
	 *                         card number.
	 */
	public function setCreditCardNumber($number)
	{
		if ($this->gpg_id === null)
			throw new StoreException('No GPG id provided.');

		$this->credit_card_last4 = substr($number, -4);

		$this->credit_card_number =
			self::encryptCreditCardNumber($number, $this->gpg_id);
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
	// {{{ public function display()

	/**
	 * Displays this payment method formatted
	 */
	public function display()
	{
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'store-payment-method';
		$span_tag->open();

		echo SwatString::minimizeEntities($this->payment_type->title), ': ';

		if ($this->credit_card_last4 !== null) {
			$span_tag->class = 'store-payment-method-credit-card-number';
			// TODO: use $this->payment_type->cc_mask
			$span_tag->setContent(self::formatCreditCardNumber(
				$this->credit_card_last4, '**** **** **** ####'));

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
			// TODO: use $this->payment_type->cc_mask
			echo "\n";
			echo self::formatCreditCardNumber($this->credit_card_last4,
				'**** **** **** ####');
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
	 */
	public static function formatCreditCardNumber($number,
		$format = '#### #### #### ####', $zero_fill = true)
	{
		$number = trim((string)$number);
		$output = '';
		$format_len = strlen(str_replace(' ', '', $format));

		// trim the number if it is too big
		if (strlen($number) > $format_len)
			$number = substr($number, 0, $format_len);

		// expand the number if it is too small
		if (strlen($number) < $format_len) {
			$number = ($zero_fill) ?
				str_pad($number, $format_len, '0', STR_PAD_LEFT) :
				str_pad($number, $format_len, '*', STR_PAD_LEFT);
		}

		// format number (from right to left)
		$numberpos = strlen($number) - 1;
		for ($i = strlen($format) - 1; $i >= 0; $i--) {
			$char = $format{$i};
			switch ($char) {
			case '#':
				$output = $number{$numberpos}.$output;
				$numberpos--;
				break;
			case '*':
				$output = '*'.$output;
				$numberpos--;
				break;
			case ' ':
				$output = ' '.$output;
				break;
			}
		}
		return $output;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->id_field = 'integer:id';
		$this->registerInternalProperty('payment_type',
			$this->class_map->resolveClass('StorePaymentType'));

		$this->registerDateProperty('credit_card_expiry');
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
}

?>
