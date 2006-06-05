<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreProductWrapper extends StoreRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set, $fields = '*')
	{
		$sql = 'select %s from Product where id in (%s)';
		$sql = sprintf($sql, $fields, $id_set);
		$class_map = StoreDataObjectClassMap::instance();
		return SwatDB::query($db, $sql,
			$class_map->resolveClass('StoreProductWrapper'));
	}

	// }}}
	// {{{ public static function loadSetFromDBWithPrimaryCategory()

	public static function loadSetFromDBWithPrimaryCategory($db, $id_set,
		$fields = '*')
	{
		$sql = 'select %s, ProductPrimaryCategoryView.primary_category
			from Product
			left outer join ProductPrimaryCategoryView
			on Product.id = ProductPrimaryCategoryView.product
			where id in (%s)';

		$sql = sprintf($sql, $fields, $id_set);
		$class_map = StoreDataObjectClassMap::instance();
		return SwatDB::query($db, $sql,
			$class_map->resolveClass('StoreProductWrapper'));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreProduct');

		$this->index_field = 'id';
	}

	// }}}
}

?>
