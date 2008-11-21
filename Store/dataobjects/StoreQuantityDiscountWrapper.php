<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreQuantityDiscount.php';

/**
 * A recordset wrapper class for StoreQuantityDiscount objects
 *
 * @package   Store 
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityDiscountWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function loadSetFromDB()

	public function loadSetFromDB(MDB2_Driver_Common $db,
		array $id_set, StoreRegion $region = null, $limiting = true)
	{
		$quantity_discounts = null;
		$wrapper = SwatDBClassMap::get('StoreQuantityDiscountWrapper');

		if ($region === null) {
			$sql = sprintf('select * from QuantityDiscount
				where QuantityDiscount.item in (%s)
				order by item, QuantityDiscount.quantity asc',
				$db->implodeArray($id_set, 'integer'));

			$quantity_discounts = SwatDB::query($db, $sql, $wrapper);
		} else {
			$sql = sprintf('select QuantityDiscount.*,
					QuantityDiscountRegionBinding.price,
					QuantityDiscountRegionBinding.region as region_id
				from QuantityDiscount
					%s QuantityDiscountRegionBinding on
					quantity_discount = QuantityDiscount.id and
					region = %s
				where QuantityDiscount.item in (%s)
				order by item, QuantityDiscount.quantity asc',
				$limiting ? 'inner join' : 'left outer join',
				$db->quote($region->id, 'integer'),
				$db->implodeArray($id_set, 'integer'));

			$quantity_discounts = SwatDB::query($db, $sql, $wrapper);
			if ($quantity_discounts !== null)
				foreach ($quantity_discounts as $discount)
					$discount->setRegion($region, $limiting);
		}

		return $quantity_discounts;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('StoreQuantityDiscount');
		$this->index_field = 'id';
	}

	// }}}
}

?>
