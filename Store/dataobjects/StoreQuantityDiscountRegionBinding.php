<?php

/**
 * Dataobject for quantity-discount region bindings.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property ?float                $price
 * @property StoreRegion           $region
 * @property StoreQuantityDiscount $quantity_discount
 */
class StoreQuantityDiscountRegionBinding extends SwatDBDataObject
{
    /**
     * Price of the quantity discount.
     *
     * @var float
     */
    public $price;

    protected function init()
    {
        $this->registerInternalProperty(
            'region',
            SwatDBClassMap::get(StoreRegion::class)
        );

        $this->registerInternalProperty(
            'quantity_discount',
            SwatDBClassMap::get(StoreQuantityDiscount::class)
        );

        $this->table = 'QuantityDiscountRegionBinding';
    }
}
