<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreCategory.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCategoryWrapper extends StoreRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set, $fields = '*')
	{
		$sql = 'select %s from Category where id in (%s)';
		$sql = sprintf($sql, $fields, $id_set);
		return SwatDB::query($db, $sql, 'CategoryWrapper');
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreCategory');

		$this->index_field = 'id';
	}

	// }}}
}

?>
