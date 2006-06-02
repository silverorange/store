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
 * A payment method represents a way to pay for a particular customer.
 * It stores the type of payment (VISA, MC, COD) as well as necessary
 * payment details such as card number and expriy date.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePaymentMethod extends StoreDataObject
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
	 * @var string
	 */
	public $credit_card_last4;

	/**
	 * Number of the credit card
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

	protected $gpg_id = null;

	// }}}
	// {{{ protection function init()

	protected function init()
	{
		$this->id_field = 'integer:id';
		$this->registerInternalField('payment_type',
			$this->class_map->resolveClass('StorePaymentType'));

		$this->registerDateField('credit_card_expiry');
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this payment method formatted
	 */
	public function display()
	{
		$br_tag = new SwatHtmlTag('br');
		$span_tag = new SwatHtmlTag('span');
		$span_tag->open();

		echo SwatString::minimizeEntities($this->payment_type->title);
		$br_tag->display();

		if ($this->credit_card_last4 !== null) {
			// TODO: use $this->payment_type->cc_mask
			echo self::creditCardFormat($this->credit_card_last4, '**** **** **** ####');

			$br_tag->display();

			echo 'Expiry: '.$this->credit_card_expiry->format(SwatDate::DF_CC_MY);
			$br_tag->display();

			echo SwatString::minimizeEntities($this->credit_card_fullname);
			$br_tag->display();
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
		echo SwatString::minimizeEntities($this->payment_type->title);

		if ($this->credit_card_last4 !== null) {
			// TODO: use $this->payment_type->cc_mask
			echo "\n";
			echo self::creditCardFormat($this->credit_card_last4, '**** **** **** ####');

			echo "\n";
			echo 'Expiry: '.$this->credit_card_expiry->format(SwatDate::DF_CC_MY);

			echo "\n";
			echo SwatString::minimizeEntities($this->credit_card_fullname);
		}

	}

	// }}}
	// {{{ public function setCreditCardNumber()

	/**
	 * Encrypts a number and stores it in the credit_card_number property
	 */
	public function setCreditCardNumber($number)
	{
		if ($this->gpg_id === null)
			throw new StoreException('No GPG id provided.');

		$this->credit_card_number =
			self::encryptCreditCardNumber($number, $this->gpg_id);
	}

	// }}}
	// {{{ public static function encryptCreditCardNumber()

	public static function encryptCreditCardNumber($number, $gpg_id)
	{
		$gpg = new Crypt_GPG();
		return $gpg->encrypt($number, $gpg_id);
	}

	// }}}	
	// {{{  public static function creditCardFormat()
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
	 * echo StorePaymentMethod::creditCardFormat(1234567890, '*** **# ####');
	 * </code>
	 *
	 * @param string $number
	 * @param string $format
	 * @param boolean $zero_fill
	 *
	 * @return string
	 */
	public static function creditCardFormat($number,
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
}

?>
