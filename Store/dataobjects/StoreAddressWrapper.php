<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreAddress.php';

/**
 * A recordset wrapper class for StoreAddress objects
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddressWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreAddress');
	}

	// }}}
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set, $fields = '*')
	{
		$sql = 'select %s from PaymentMethod where id in (%s)';
		$sql = sprintf($sql, $fields, $id_set);
		$wrapper = $this->class_map->resolveClass('StoreAddressWrapper');
		return SwatDB::query($db, $sql, $wrapper);
	}

	// }}}
}

?>
