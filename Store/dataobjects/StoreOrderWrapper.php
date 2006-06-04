<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * A recordset wrapper class for StoreOrder objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @see       StoreOrder
 */
class StoreOrderWrapper extends StoreRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set, $fields = '*')
	{
		$sql = 'select %s from Orders where id in (%s) order by id asc';
		$sql = sprintf($sql, $fields, $id_set);
		return SwatDB::query($db, $sql,
			$this->class_map->resolveClass('StoreOrderWrapper'));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = $this->class_map->resolveClass('StoreOrder');
	}

	// }}}
}

?>
