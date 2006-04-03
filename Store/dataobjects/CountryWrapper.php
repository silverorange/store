<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Country.php';

/**
 * A recordset wrapper class for Country objects
 *
 * @package   vesey2
 * @copyright 2006 silverorange
 * @see       Country
 */
class CountryWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function __construct()

	public function __construct($rs)
	{
		$this->row_wrapper_class = 'Country';
		parent::__construct($rs);
	}

	// }}}
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set)
	{
		$sql = 'select * from countries where id in (%s)';

		$sql = sprintf($sql, $id_set);
		return SwatDB::query($db, $sql, 'CountryWrapper');
	}

	// }}}
}

?>
