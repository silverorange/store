<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItem.php';

/**
 * A recordset wrapper class for Item objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreItemWrapper extends StoreRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set)
	{
		$sql = 'select Item.* from Item
			where Item.id in (%s)
			order by Item.displayorder, Item.sku, 
				Item.part_count';

		$sql = sprintf($sql, $id_set);

		$class_map = StoreClassMap::instance();
		return SwatDB::query($db, $sql,
			$class_map->resolveClass('StoreItemWrapper'));
	}

	// }}}
	// {{{ public static function loadSetFromDBWithRegion()

	/**
	 * Loads a set of items with region-specific fields filled in with a
	 * specific region
	 *
	 * Also loads item availability for the given region in the is_available
	 * field.
	 *
	 * @param MDB2_Driver_Common $db
	 * @param string $id_set
	 * @param integer $region the region to use for region-specific fields.
	 * @param boolean $limiting whether or not to load items that are not in
	 *                           the given region. If true, these items are not
	 *                           loaded. If false, the items are loaded without
	 *                           region specific fields.
	 */
	public static function loadSetFromDBWithRegion($db, $id_set, $region,
		$limiting = true)
	{
		$sql = 'select Item.*, ItemRegionBinding.price,
			ItemRegionBinding.enabled, ItemRegionBinding.region,
			case when AvailableItemView.item is null then false
				else true
				end as is_available
			from Item
				%s ItemRegionBinding on ItemRegionBinding.item = Item.id and
					ItemRegionBinding.region = %s
				left outer join AvailableItemView on
					AvailableItemView.item = Item.id and
						AvailableItemView.region = %s
			where Item.id in (%s)
			order by Item.displayorder, Item.sku, 
				Item.part_count';

		$sql = sprintf($sql,
			$limiting ? 'inner join' : 'left outer join',
			$db->quote($region, 'integer'),
			$db->quote($region, 'integer'),
			$id_set);

		$class_map = StoreClassMap::instance();
		return SwatDB::query($db, $sql,
			$class_map->resolveClass('StoreItemWrapper'));
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = $this->class_map->resolveClass('StoreItem');
		$this->index_field = 'id';
	}

	// }}}
}

?>
