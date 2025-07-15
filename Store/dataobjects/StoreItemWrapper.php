<?php

/**
 * A recordset wrapper class for Item objects.
 *
 * @copyright 2006-2016 silverorange
 */
class StoreItemWrapper extends SwatDBRecordsetWrapper
{
    /**
     * Sets the region for all items in this record set.
     *
     * @param StoreRegion $region   the region to use
     * @param bool        $limiting whether or not to not load this item if it is
     *                              not available in the given region
     */
    public function setRegion(StoreRegion $region, $limiting = true)
    {
        foreach ($this as $item) {
            $item->setRegion($region, $limiting);
        }
    }

    public function loadProducts()
    {
        return $this->loadAllSubDataObjects(
            'product',
            $this->db,
            'select * from Product where id in (%s)',
            SwatDBClassMap::get(StoreProductWrapper::class)
        );
    }

    public static function loadSetFromDB($db, $id_set)
    {
        $sql = 'select Item.* from Item
				left outer join ItemGroup on Item.item_group = ItemGroup.id
			where Item.id in (%s)
			order by coalesce(ItemGroup.displayorder, -1), ItemGroup.title,
				ItemGroup.id, Item.displayorder, Item.sku';

        $sql = sprintf($sql, $id_set);

        return SwatDB::query(
            $db,
            $sql,
            SwatDBClassMap::get(StoreItemWrapper::class)
        );
    }

    /**
     * Loads a set of items with region-specific fields filled in with a
     * specific region.
     *
     * Also loads item availability for the given region in the is_available
     * field.
     *
     * @param MDB2_Driver_Common $db
     * @param string             $id_set
     * @param StoreRegion        $region   the region to use for region-specific fields
     * @param bool               $limiting whether or not to load items that are not in
     *                                     the given region. If true, these items are not
     *                                     loaded. If false, the items are loaded without
     *                                     region specific fields.
     */
    public static function loadSetFromDBWithRegion(
        $db,
        $id_set,
        StoreRegion $region,
        $limiting = true
    ) {
        $sql = 'select Item.*, ItemRegionBinding.price, ItemRegionBinding.original_price,
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
				ItemGroup.id, Item.displayorder, ItemRegionBinding.price, Item.sku';

        $sql = sprintf(
            $sql,
            $limiting ? 'inner join' : 'left outer join',
            $db->quote($region->id, 'integer'),
            $db->quote($region->id, 'integer'),
            $id_set
        );

        $items = SwatDB::query(
            $db,
            $sql,
            SwatDBClassMap::get(StoreItemWrapper::class)
        );

        if ($items !== null) {
            $items->setRegion($region, $limiting);
        }

        return $items;
    }

    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(StoreItem::class);
        $this->index_field = 'id';
    }
}
