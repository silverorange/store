<?php

require_once 'Store/dataobjects/StorePaymentMethodType.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A payment method for an ecommerce web application
 *
 * Payment methods are usually tied to {@link StoreCustomer} objects or
 * {@link StoreOrder} objects.
 *
 * A payment method is uses to represent differnt ways of paying for an order
 * in an ecommerce web application. Some examples are:
 *
 * - VISA
 * - AMEX
 * - Cheque
 * - COD
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePaymentMethod extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Payment method identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Credit Card Full Name
	 *
	 * @var string
	 */
	public $creditcard_fullname;

	/**
	 * Credit Card Last 4 Digits
	 *
	 * @var integer
	 */
	public $creditcard_last4;

	/**
	 * Default Payment Method
	 *
	 * @var boolean
	 */
	public $default_paymentmethod;

	/**
	 * The expiry date of the creditcard
	 *
	 * @var Date
	 */
	public $creditcard_expiry;	

	// }}}
	// {{{ protection function init()

	protected function init()
	{
		$this->id_field = 'integer:id';

		$this->registerInternalField('paymentmethod', 'StorePaymentMethodType');
		$this->registerDateField('createdate');
		$this->registerDateField('creditcard_expiry');
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this payment method formatted
	 */
	public function display()
	{
		$br_tag = new SwatHtmlTag('br');
		$div_tag = new SwatHtmlTag('div');
		$div_tag->open();

		echo SwatString::minimizeEntities($this->paymentmethod->title);
		$br_tag->display();

		if ($this->creditcard_last4 !== null) {
			echo self::creditcardFormat($this->creditcard_last4, '**** **** **** ####');
			$br_tag->display();

			echo 'Expiry: '.$this->creditcard_expiry->format(SwatDate::DF_CC_MY);
			$br_tag->display();

			echo SwatString::minimizeEntities($this->creditcard_fullname);
			$br_tag->display();
		}

		$div_tag->close();
	}

	// }}}
	// {{{  public static function creditcardFormat()
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
	 * echo StorePaymentMethod::creditcardFormat(1234567890, '*** **# ####');
	 * </code>
	 *
	 * @param string $number
	 * @param string $format
	 * @param boolean $zero_fill
	 *
	 * @return string
	 */
	public static function creditcardFormat($number,
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
