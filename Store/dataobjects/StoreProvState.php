<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreCountry.php';

/**
 * A province/state data object
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
class StoreProvState extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier of this province or state
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title of this province or state
	 *
	 * @var string
	 */
	public $title;

	/**
	 * A two letter abbreviation used to identify this province of state
	 *
	 * This is also used for displaying addresses.
	 *
	 * @var string
	 */
	public $abbreviation;

	// }}}
	// {{{ public function loadFromAbbreviation()

	/**
	 * Loads this province/state from an abbreviation
	 *
	 * @param string $abbreviation the abbreviation of this province/state.
	 *
	 * @return boolean true if this province/state was loaded and false if it
	 *                  was not.
	 */
	public function loadFromAbbreviation($abbreviation)
	{
		$this->checkDB();

		$row = null;
		$loaded = false;

		if ($this->table !== null) {
			$sql = sprintf('select * from ProvState where abbreviation = %s',
				$this->db->quote($abbreviation, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ProvState';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('country',
			SwatDBClassMap::get('StoreCountry'));
	}

	// }}}
}

?>
