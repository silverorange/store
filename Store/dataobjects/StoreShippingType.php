<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A shiping type data object
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingType extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier of this shipping type
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Non-visible string indentifier
	 *
	 * This is something like 'express' or 'ground'.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible title for this shipping type
	 *
	 * @var string
	 */
	public $title;

	/**
	 * User visible note for this shipping type
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

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a shipping type by its shortname
	 *
	 * @param string $shortname the shortname of the shipping type to load.
	 */
	public function loadByShortname($shortname)
	{
		$this->checkDB();
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
	 * Whether or not this type is available in the given region
	 *
	 * @param StoreRegion $region the region to check the availability of this
	 *                             type for.
	 *
	 * @return boolean true if this type is available in the
	 *                  given region and false if it is not.
	 *
	 * @throws StoreException if this dataobject has no id defined.
	 */
	public function isAvailableInRegion(StoreRegion $region)
	{
		$this->checkDB();

		if ($this->id === null)
			throw new StoreException('Shipping type must have an id set '.
				'before region availability can be determined.');

		$sql = sprintf('select count(id) from ShippingType
			inner join ShippingTypeRegionBinding on shipping_type = id and
				region = %s
			where id = %s',
			$this->db->quote($region->id, 'integer'),
			$this->db->quote($this->id, 'integer'));

		return (SwatDB::queryOne($this->db, $sql) > 0);
	}

	// }}}
	// {{{ public function getSurcharge()

	/**
	 * Get the shipping surcharge for the item in the given region
	 *
	 * @param StoreRegion $region the region to check the availability of this
	 *                             type for.
	 *
	 * @return float The shipping surcharge in the given region.
	 *
	 * @throws StoreException if this dataobject has no id defined.
	 */
	public function getSurcharge(StoreRegion $region)
	{
		$this->checkDB();

		if ($this->id === null)
			throw new StoreException('Shipping type must have an id set '.
				'before region availability can be determined.');

		$sql = sprintf('select ShippingTypeRegionBinding.price
			from ShippingType
			inner join ShippingTypeRegionBinding on shipping_type = id and
				region = %s
			where id = %s',
			$this->db->quote($region->id, 'integer'),
			$this->db->quote($this->id, 'integer'));

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ShippingType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
