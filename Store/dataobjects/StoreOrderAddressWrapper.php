<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreOrderAddress.php';

/**
 * A recordset wrapper class for StoreOrderAddress objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreOrderAddress
 */
class StoreOrderAddressWrapper extends StoreRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set)
	{
		$sql = 'select * from OrderAddress where id in (%s)';
		$sql = sprintf($sql, $id_set);
		return SwatDB::query($db, $sql,
			$this->class_map->resolveClass('StoreOrderAddressWrapper'));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreOrderAddress');
	}

	// }}}
}

?>
