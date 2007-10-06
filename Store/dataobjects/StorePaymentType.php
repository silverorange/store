<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A payment type data object
 *
 * Payment type shortnames are by convention:
 *
 * <pre>
 * Shortname    | Description         | Type              | Region
 * -------------+---------------------+-------------------+--------
 * visa         | Visa                | credit card       | Global
 * mastercard   | MasterCard          | credit card       | Global
 * maestro      | Maestro             | debit card        | Global
 * delta        | Visa Debit/Delta    | debit card        | UK
 * solo         | Solo                | debit card        | UK
 * switch       | UK Maestro (Switch) | debit card        | UK
 * electron     | Visa Electron       | credit/debit card | Outside US+CA+AU
 * amex         | American Express    | credit card       | Global
 * dinersclub   | Diners Club         | credit card       | US+CA
 * jcb          | Japan Credit Bureau | credit card       | JA
 * discover     | Discover Card       | credit card       | US
 * unionpay     | China UnionPay      | credit card       | CH
 * paypal       | PayPal              | online payment    | Global
 * cheque       | cheque              | cheque            |
 * invoice      | invoice             | printed invoice   |
 * cod          | cash on delivery    | cash on delivery  |
 * </pre>
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentType extends SwatDBDataObject
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

		$sql = sprintf('select count(id) from PaymentType
			inner join PaymentTypeRegionBinding on payment_type = id and
				region = %s
			where id = %s',
			$this->db->quote($region->id, 'integer'),
			$this->db->quote($this->id, 'integer'));

		return (SwatDB::queryOne($this->db, $sql) > 0);
	}

	// }}}
	// {{{ public function getCardMaskedFormat()

	/**
	 * Gets the masked format string for this payment type if this payment
	 * type is a credit or debit card
	 *
	 * @return string the masked format string for this payment type. If no
	 *                 suitable mask is available (for example if this type is
	 *                 "invoice"), null is returned.
	 *
	 * @see StorePaymentType::formatCardNumber()
	 */
	public function getCardMaskedFormat()
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
	// {{{ public function getCardFormat()

	/**
	 * Gets the format string for this payment type if this payment type is a
	 * debit or credit card
	 *
	 * @return string the format string for this payment type. If no suitable
	 *                 suitable format is available (for example if this type
	 *                 is "invoice"), null is returned.
	 *
	 * @see StorePaymentType::formatCardNumber()
	 */
	public function getCardFormat()
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
	// {{{ public function getCardNumberPreviewLength()

	/**
	 * Gets the length of the card number preview field for this payment type
	 * if this payment type is a debit or credit card
	 *
	 * @return integer the length of the card number preview field for this
	 *                  payment type. If this payment type is not a known debit
	 *                  or credit card, the card number preview length is
	 *                  returned as zero.
	 */
	public function getCardNumberPreviewLength()
	{
		$length = 0;

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
		case 'dinersclub':
			$length = 16;
			break;
		case 'amex':
			$length = 15;
			break;
		}

		return $length;
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
	// {{{  public static function formatCardNumber()

	/**
	 * Formats a card number according to a format string
	 *
	 * Format strings may contain spaces, hash marks or stars.
	 * ' ' - inserts a space at this position
	 * '*' - displays a * at this position
	 * '#' - displays the number at this position
	 *
	 * For example:
	 * <code>
	 * // displays '*** **6 7890'
	 * echo StorePaymentType::formatCardNumber(1234567890, '*** **# ####');
	 * </code>
	 *
	 * @param string $number the card number to format.
	 * @param string $format the format string to use.
	 * @param boolean $zero_fill whether or not the prepend the card number
	 *                            with zeros until it is as long as the format
	 *                            string.
	 *
	 * @return string the formatted card number.
	 */
	public static function formatCardNumber($number,
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
	// {{{  public static function getInfoFromCardNumber()

	/**
	 * Looks up details about a card based on the card number
	 *
	 * Information about the card type, card length, and card number prefixes
	 * is returned.
	 *
	 * Each card type has a unique set of prefix for their card numbers. This
	 * method gets card information based on the number prefix data from
	 * {@link http://en.wikipedia.org/wiki/Credit_card_number}.
	 *
	 * @param string $number the card number for which to get card details.
	 *                        Space characters in the number are ignored,
	 *                        allowing formatted numbers to be checked.
	 *
	 * @return stdClass an object describing the card type containing the
	 *                   following properties:
	 *                    - (string) description
	 *                    - (string) shortname
	 *                    - (array)  prefixes
	 *                    - (array)  length
	 *                   If no suitable card information is available, null is
	 *                   returned.
	 */
	public static function getInfoFromCardNumber($number)
	{
		static $types = null;

		// {{{ static card type data

		if ($types === null) {

			/*
			 * Note: The order of types is important. Types are checked in
			 * order so a Visa Electron card with prefix 4917 will be detected
			 * before a Visa card with prefix 4.
			 */

			$types = array();

			$type = new stdClass();
			$type->description = Store::_('American Express');
			$type->shortname = 'amex';
			$type->prefixes = array(34, 37);
			$type->length = array(15);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('Diners Club');
			$type->shortname = 'dinersclub';
			$type->prefixes = array(36);
			$type->length = array(14);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('China Union Pay');
			$type->shortname = 'unionpay';
			$type->prefixes = array(622);
			$type->length = array(16, 17, 18, 19);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('JCB');
			$type->shortname = 'jcb';
			$type->prefixes = array(35);
			$type->length = array(16);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('JCB');
			$type->shortname = 'jcb';
			$type->prefixes = array(1800, 2131);
			$type->length = array(15);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('Maestro');
			$type->shortname = 'maestro';
			$type->prefixes = array(5020, 5038, 6304, 6759);
			$type->length = array(16, 18);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('MasterCard');
			$type->shortname = 'mastercard';
			$type->prefixes = array(51, 52, 53, 54, 55);
			$type->length = array(16);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('Solo');
			$type->shortname = 'solo';
			$type->prefixes = array(6334, 6767);
			$type->length = array(16, 18, 19);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('UK Maestro');
			$type->shortname = 'switch';
			$type->prefixes = array(4903, 4905, 4911, 4936,
				564182, 633110, 6333, 6759);

			$type->length = array(16, 18, 19);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('Visa Debit');
			$type->shortname = 'delta';
			$type->prefixes = array(); // TODO: missing data for Visa Debit
			$type->length = array(16);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('Visa Electron');
			$type->shortname = 'electron';
			$type->prefixes = array(417500, 4917, 4913, 4508, 4844);
			$type->length = array(16);
			$types[] = $type;

			$type = new stdClass();
			$type->description = Store::_('Visa');
			$type->shortname = 'visa';
			$type->prefixes = array(4);
			$type->length = array(13, 16);
			$types[] = $type;
		}

		// }}}

		$info = null;
		$number = str_replace(' ', '', $number);
		$number_length = strlen($number);

		foreach ($types as $type) {
			if (!in_array($number_length, $type->length))
				continue;

			foreach ($type->prefixes as $prefix) {
				if (strncmp($number, $prefix, strlen($prefix) == 0) {
					$info = clone $type;
					break 2;
				}
			}
		}

		return $info;
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
