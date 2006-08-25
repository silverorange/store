<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * A payment type data object
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

		$sql = sprintf('select count(id) from PaymentType
			inner join PaymentTypeRegionBinding on payment_type = id and
				region = %s
			where enabled = true and id = %s',
			$this->db->quote($region->id, 'integer'),
			$this->db->quote($this->id, 'integer'));

		$available = (SwatDB::queryOne($this->db, $sql) > 0);

		return $available;
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
