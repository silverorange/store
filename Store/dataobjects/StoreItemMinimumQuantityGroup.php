<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Dataobject to group {@link StoreItem} objects within a group, of which a
 * minimum quantity of items must be purchased.
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemMinimumQuantityGroup extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Shortname
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Minimum quantity
	 *
	 * @var integer
	 */
	public $minimum_quantity;

	/**
	 * User visible xhtml description
	 *
	 * @var string
	 */
	public $description;

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads a group by its shortname
	 *
	 * @param string $shortname the shortname of the group to load.
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
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ItemMinimumQuantityGroup';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
