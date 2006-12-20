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
 * *Bankcard is ceasing all operations in 
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
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PaymentType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
