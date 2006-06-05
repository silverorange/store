<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * A recordset wrapper class for StoreRegion objects
 *
 * @package   Store 
 * @copyright 2006 silverorange
 */
class StoreRegionWrapper extends StoreRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set, $fields)
	{
		$sql = 'select %s from Region where id in (%s)';
		$sql = sprintf($sql, $fields, $id_set);

		$class_map = StoreDataObjectClassMap::instance();
		return SwatDB::query($db, $sql,
			$class_map->resolveClass('StoreRegionWrapper'));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreRegion');
	}

	// }}}
}

?>
