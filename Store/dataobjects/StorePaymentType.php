<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * A payment type data object
 *
 * Payment type shortnames are by convention:
 *
 * <pre>
 * Shortname    | Description         | Type              | Region
 * -------------+---------------------+-------------------+--------
 * visa         | Visa                | credit card       | Global
 * mastercard   | Master Card         | credit card       | Global
 * delta        | Visa Debit/Delta    | debit card        | UK
 * solo         | Solo                | debit card        | UK
 * switch       | Switch              | debit card        | UK
 * electron     | Visa Electron       | credit/debit card | Outside US+CA+AU
 * amex         | American Express    | credit card       | Global
 * dinersclub   | Diners Club         | credit card       | US+CA
 * jcb          | Japan Credit Bureau | credit card       | JA
 * discover     | Discover Card       | credit card       | US
 * unionpay     | China UnionPay      | credit card       | CH
 * paypal       | PayPal              | online payment    | Global
 * cheque       | cheque              | cheque            |
 * account      | on account          | on account        |
 * cod          | cash on delivery    | cash on delivery  |
 * </pre>
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentType extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier of this payment type 
	 *
	 * @var string 
	 */
	public $id;

	/**
	 * Non-visible string indentifier
	 *
	 * This is something like 'VS', 'MC' or 'DS'.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Whether or not this payment type is available
	 *
	 * @var boolean
	 */
	public $enabled;

	/**
	 * User visible title for this payment type
	 *
	 * @var string
	 */
	public $title;

	/**
	 * User visible note for this payment type
	 *
	 * The note field should be used to inform customers of additional
	 * requirements or conditions on this payment method type. For example, it
	 * could contain special shipping information for COD payments.
	 *
	 * @var string
	 */
	public $note;

	/**
	 * Order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * Whether or not this payment type is a credit card
	 *
	 * @var boolean
	 */
	public $credit_card;

	/**
	 * Additional charge applied when using this payment type
	 *
	 * @var double
	 */
	public $surcharge;

	// }}}
	// {{{ public function loadFromShortname()

	/**
	 * Loads a payment type by its shortname
	 *
	 * @param string $shortname the shortname of the payment type to load.
	 */
	public function loadFromShortname($shortname)
	{
		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where shortname = %s',
				$this->table,
				$this->db->quote($shortname, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();
		return true;
	}

	// }}}
	// {{{ public function isAvailableInRegion()

	/**
	 * Whether or not this item is available for purchase in the given region
	 *
	 * The item needs to have an id before this method will work.
	 *
	 * @param StoreRegion $region the region to check the availability of this
	 *                             item for.
	 *
	 * @return boolean true if this item is available for purchase in the
	 *                  given region and false if it is not.
	 *
	 * @throws StoreException if this item has no id defined.
	 */
	public function isAvailableInRegion(StoreRegion $region)
	{
		if ($this->id === null)
			throw new StoreException('Payment type must have an id set '.
				'before region availability can be determined.');

		if (!$this->enabled)
			return false;

		$sql = sprintf('select count(id) from PaymentType
			inner join PaymentTypeRegionBinding on payment_type = id and
				region = %s
			where enabled = true and id = %s',
			$this->db->quote($region->id, 'integer'),
			$this->db->quote($this->id, 'integer'));

		return (SwatDB::queryOne($this->db, $sql) > 0);
	}

	// }}}
	// {{{ public function getCreditCardMaskedFormat()

	/**
	 * Gets the masked format string for this payment type if this payment
	 * type is a credit card
	 *
	 * @return string the masked format string for this payment type. If no
	 *                 suitable mask is available (for example if this type is
	 *                 not a credit card), null is returned.
	 *
	 * @see StorePaymentType::formatCreditCardNumber()
	 */
	public function getCreditCardMaskedFormat()
	{
		$mask = null;

		switch ($this->shortname) {
		case 'visa':
		case 'mastercard':
		case 'discover':
		case 'jcb':
		case 'electron':
		case 'unionpay':
		case 'delta':
		case 'switch':
		case 'solo':
			$mask = '**** **** **** ####';
			break;
		case 'amex':
			$mask = '**** ****** #####';
			break;
		case 'dinersclub':
			$mask = '**** ****** ####';
			break;
		}

		return $mask;
	}

	// }}}
	// {{{ public function getCreditCardFormat()

	/**
	 * Gets the format string for this payment type if this payment type is a
	 * credit card
	 *
	 * @return string the format string for this payment type. If no suitable
	 *                 suitable format is available (for example if this type
	 *                 is not a credit card), null is returned.
	 *
	 * @see StorePaymentType::formatCreditCardNumber()
	 */
	public function getCreditCardFormat()
	{
		$mask = null;

		switch ($this->shortname) {
		case 'visa':
		case 'mastercard':
		case 'discover':
		case 'jcb':
		case 'electron':
		case 'unionpay':
		case 'delta':
		case 'switch':
		case 'solo':
			$mask = '#### #### #### ####';
			break;
		case 'amex':
			$mask = '#### ###### #####';
			break;
		case 'dinersclub':
			$mask = '#### ###### ####';
			break;
		}

		return $mask;
	}

	// }}}
	// {{{ public function isCard()

	/**
	 * Gets whether or not this payment type uses a card (debit or credit)
	 *
	 * Payment types that conventionally use a card are listed in the
	 * class-level documentation of {@link StorePaymentType}.
	 *
	 * @return boolean true if this payment type uses a card and false if this
	 *                  payment type does not use a card.
	 */
	public function isCard()
	{
		$card_types = array(
			'visa',
			'mastercard',
			'delta', 
			'solo',
			'switch',
			'electron',
			'amex',
			'dinersclub',
			'jcb',
			'discover',
			'unionpay',
		);

		return (in_array($this->shortname, $card_types));
	}

	// }}}
	// {{{ public function hasInceptionDate()

	/**
	 * Gets whether or not this payment type uses an inception date
	 *
	 * Payment types that conventionally use an inception date are 'solo',
	 * 'switch' and 'amex'.
	 *
	 * @return boolean true if this payment type uses an inception date and
	 *                  false if this payment type does not use an inception
	 *                  date.
	 *
	 * @see StorePaymentMethod::$inception_date
	 */
	public function hasInceptionDate()
	{
		$card_types = array(
			'solo',
			'switch',
			'amex',
		);

		return (in_array($this->shortname, $card_types));
	}

	// }}}
	// {{{ public function hasIssueNumber()

	/**
	 * Gets whether or not this payment type uses an issue number
	 *
	 * Payment types that conventionally use an issue number are 'solo' and
	 * 'switch'.
	 *
	 * @return boolean true if this payment type uses an issue number and false
	 *                  if this payment type does not use an issue number.
	 *
	 * @see StorePaymentMethod::$issue_number
	 */
	public function hasIssueNumber()
	{
		$card_types = array(
			'solo',
			'switch',
		);

		return (in_array($this->shortname, $card_types));
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
	 * echo StorePaymentType::formatCreditCardNumber(1234567890,
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
			$char = $format[$i];
			switch ($char) {
			case '#':
				$output = $number[$numberpos].$output;
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
		$this->table = 'PaymentType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
