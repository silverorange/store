<?php

/**
 * Dataobject for item region bindings.
 *
 * @copyright 2006-2016 silverorange
 */
class StoreItemRegionBinding extends SwatDBDataObject
{
    /**
     * Price of the item.
     *
     * @var float
     */
    public $price;

    /**
     * Optional original price of the item.
     *
     * @var float
     */
    public $original_price;

    /**
     * Optional sale discount price of the item.
     *
     * Used when a sale discount is attached to the item.
     *
     * @var float
     */
    public $sale_discount_price;

    /**
     * If the item should be available.
     *
     * @var bool
     */
    public $enabled;

    protected function init()
    {
        $this->registerInternalProperty(
            'region',
            SwatDBClassMap::get('StoreRegion')
        );

        $this->registerInternalProperty(
            'item',
            SwatDBClassMap::get('StoreItem')
        );

        $this->table = 'ItemRegionBinding';
    }
}
