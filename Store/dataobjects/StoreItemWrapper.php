<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreItem.php';

/**
 * A recordset wrapper class for Item objects
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreItemWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function setRegion()

	/**
	 * Sets the region for all items in this record set
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to not load this item if it is
	 *                           not available in the given region.
	 */
	public function setRegion(StoreRegion $region, $limiting = true)
	{
		foreach ($this as $item)
			$item->setRegion($region, $limiting);
	}

	// }}}
	// {{{ public static function loadSetFromDB()

	public static function loadSetFromDB($db, $id_set)
	{
		$sql = 'select Item.* from Item
				left outer join ItemGroup on Item.item_group = ItemGroup.id
			where Item.id in (%s)
			order by coalesce(ItemGroup.displayorder, -1), ItemGroup.title,
				Item.displayorder, Item.sku';

		$sql = sprintf($sql, $id_set);

		return SwatDB::query($db, $sql,
			SwatDBClassMap::get('StoreItemWrapper'));
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
	 * @param StoreRegion $region the region to use for region-specific fields.
	 * @param boolean $limiting whether or not to load items that are not in
	 *                           the given region. If true, these items are not
	 *                           loaded. If false, the items are loaded without
	 *                           region specific fields.
	 */
	public static function loadSetFromDBWithRegion($db, $id_set,
		StoreRegion $region, $limiting = true)
	{
		$sql = 'select Item.*, ItemRegionBinding.price,
			ItemRegionBinding.enabled, ItemRegionBinding.region as region_id,
			case when AvailableItemView.item is null then false
				else true
				end as is_available
			from Item
				%s ItemRegionBinding on ItemRegionBinding.item = Item.id and
					ItemRegionBinding.region = %s
				left outer join AvailableItemView on
					AvailableItemView.item = Item.id and
						AvailableItemView.region = %s
				left outer join ItemGroup on Item.item_group = ItemGroup.id
			where Item.id in (%s)
			order by coalesce(ItemGroup.displayorder, -1), ItemGroup.title,
				Item.displayorder, Item.sku';

		$sql = sprintf($sql,
			$limiting ? 'inner join' : 'left outer join',
			$db->quote($region->id, 'integer'),
			$db->quote($region->id, 'integer'),
			$id_set);

		$items = SwatDB::query($db, $sql,
			SwatDBClassMap::get('StoreItemWrapper'));

		if ($items !== null)
			$items->setRegion($region, $limiting);

		return $items;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreItem');
		$this->index_field = 'id';
	}

	// }}}
}

?>
