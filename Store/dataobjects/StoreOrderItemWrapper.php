<?php

/**
 * A recordset wrapper class for StoreOrderItem objects.
 *
 * @copyright 2006-2016 silverorange
 *
 * @see       StoreOrderItem
 */
class StoreOrderItemWrapper extends SwatDBRecordsetWrapper
{
    public function loadAllAvailableItems(StoreRegion $region)
    {
        $item_ids = [];
        $item_skus = [];
        foreach ($this as $order_item) {
            $item_ids[] = $order_item->item;
            $item_skus[] = $order_item->sku;
        }

        $item_ids = array_unique(
            array_filter(
                $item_ids,
                function ($value) {
                    return $value !== null;
                }
            )
        );

        $item_skus = array_unique(
            array_filter(
                $item_skus,
                function ($value) {
                    return $value !== null;
                }
            )
        );

        $this->checkDB();

        $wrapper = SwatDBClassMap::get('StoreItemWrapper');

        // return empty recordset if there are no item ids or skus in
        // this set of orderitems
        if (count($item_ids) === 0 && count($item_skus) === 0) {
            $recordset = new $wrapper();
            $recordset->setDatabase($this->db);

            return $recordset;
        }

        $this->db->loadModule('Datatype', null, true);

        $item_ids = $this->db->implodeArray($item_ids, 'integer');
        $item_skus = $this->db->implodeArray($item_skus, 'text');

        $items_sql = sprintf(
            'select Item.* from Item
				inner join AvailableItemView on
					AvailableItemView.item = Item.id and
					AvailableItemView.region = %s
			where Item.id in (%s) or Item.sku in (%s)
			order by id desc',
            $this->db->quote($region->id, 'integer'),
            $item_ids,
            $item_skus
        );

        $items = SwatDB::query($this->db, $items_sql, $wrapper);

        // Index by SKU. Because we ordered by id desc, lower ids are
        // preferred for items with duplicate SKUs.
        $items_by_sku = [];
        foreach ($items as $item) {
            $items_by_sku[$item->sku] = $item;
        }

        // attach items by id and sku
        foreach ($this as $order_item) {
            if (isset($items[$order_item->item])) {
                $order_item->setAvailableItemCache(
                    $region,
                    $items[$order_item->item]
                );
            } elseif (isset($items_by_sku[$order_item->sku])) {
                $order_item->setAvailableItemCache(
                    $region,
                    $items_by_sku[$order_item->sku]
                );
            } else {
                $order_item->setAvailableItemCache(
                    $region,
                    null
                );
            }
        }

        return $items;
    }

    protected function init()
    {
        parent::init();
        $this->index_field = 'id';
        $this->row_wrapper_class = SwatDBClassMap::get('StoreOrderItem');
    }
}
