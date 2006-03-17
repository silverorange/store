<?php

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
}

?>
