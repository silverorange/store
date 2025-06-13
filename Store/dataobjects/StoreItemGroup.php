<?php

/**
 * Dataobject to group {@link StoreItem} objects within a {@link StoreProduct}.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroup extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * User visible title.
     *
     * @var string
     */
    public $title;

    /**
     * Order of display.
     *
     * @var int
     */
    public $displayorder;

    /**
     * The region to use when loading region-specific fields in item sub-data-
     * objects.
     *
     * @var StoreRegion
     *
     * @see StoreItemGroup::setRegion()
     */
    protected $region;

    /**
     * Sets the region to use when loading region-specific fields for item
     * sub-data-objects.
     *
     * @param StoreRegion $region the region to use
     */
    public function setRegion(StoreRegion $region)
    {
        $this->region = $region;

        if ($this->hasSubDataObject('items')) {
            foreach ($this->items as $item) {
                $item->setRegion($region);
            }
        }
    }

    protected function init()
    {
        $this->table = 'ItemGroup';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'product',
            SwatDBClassMap::get('StoreProduct')
        );
    }

    // loader methods

    protected function loadItems()
    {
        $sql = sprintf(
            'select * from Item where item_group = %s
				order by Item.displayorder, Item.sku',
            $this->db->quote($this->id, 'integer')
        );

        $items = SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get('StoreItemWrapper')
        );

        foreach ($items as $item) {
            $item->setRegion($this->region);
        }

        return $items;
    }

    /**
     * Loads the cheapest item of this item group.
     */
    protected function loadCheapestItem()
    {
        $cheapest_item = null;

        if ($this->hasInternalValue('cheapest_item')
            && $this->getInternalValue('cheapest_item') !== null) {
            $cheapest_item_id = $this->getInternalValue('cheapest_item');

            $sql = 'select * from Item where id = %s';

            $sql = sprintf(
                $sql,
                $this->db->quote($cheapest_item_id, 'integer')
            );
        } else {
            $sql = 'select * from Item where id in
				(select getItemGroupCheapestItem(%s, %s))';

            $sql = sprintf(
                $sql,
                $this->db->quote($this->id, 'integer'),
                $this->db->quote($this->region->id, 'integer')
            );
        }

        $wrapper = SwatDBClassMap::get('StoreItemWrapper');
        $rs = SwatDB::query($this->db, $sql, $wrapper);
        if (count($rs) > 0) {
            $cheapest_item = $rs->getFirst();
            $cheapest_item->setRegion($this->region);
        }

        return $cheapest_item;
    }
}
