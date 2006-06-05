<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreQuantityDiscount.php';

/**
 * A recordset wrapper class for StoreQuantityDiscount objects
 *
 * @package   Store 
 * @copyright 2006 silverorange
 */
class StoreQuantityDiscountWrapper extends StoreRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set, $region)
	{
		$sql = 'select QuantityDiscount.*, QuantityDiscountRegionBinding.price
			from QuantityDiscount 
				inner join QuantityDiscountRegionBinding on
					quantity_discount = QuantityDiscount.id ';

		if ($region !== null)
			$sql.= sprintf(' and region = %s', $db->quote($region, 'integer'));
			
		$sql.= 'where QuantityDiscount.id in (%s)
			order by QuantityDiscount.quantity desc';

		$sql = sprintf($sql, $id_set);

		$class_map = StoreDataObjectClassMap::instance();
		return SwatDB::query($db, $sql,
			$class_map->resolveClass('StoreQuantityDiscountWrapper'));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class =
			$this->class_map->resolveClass('StoreQuantityDiscount');

		$this->index_field = 'id';
	}

	// }}}
}

?>
