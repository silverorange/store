<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreCartEntry.php';

/**
 * A recordset wrapper class for StoreCartEntry objects
 *
 * This class contains cart functionality common to all sites. It is typically
 * extended on a per-site basis.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartEntryWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function __construct()

	public function __construct($rs)
	{
		$this->row_wrapper_class = 'StoreCartEntry';
		parent::__construct($rs);
	}

	// }}}
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set, $fields = '*')
	{
		$sql = 'select %s from cartentries where id in (%s)';
		$sql = sprintf($sql, $fields, $id_set);
		return SwatDB::query($db, $sql, 'StoreCartWrapper');
	}

	// }}}
}

?>
