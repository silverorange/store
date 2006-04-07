<?php

require_once 'Store/dataobjects/StoreCountry.php';

require_once 'SwatDB/SwatDBRecordsetWrapper.php';

/**
 * A recordset wrapper class for StoreCountry objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreCountry
 */
class StoreCountryWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set)
	{
		$sql = 'select * from countries where id in (%s)';
		$sql = sprintf($sql, $id_set);

		return SwatDB::query($db, $sql, 'StoreCountryWrapper');
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'StoreCountry';
	}

	// }}}
}

?>
