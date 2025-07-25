<?php

/**
 * Quantity discount object.
 *
 * Quantity discounts are a discount scheme whereby the unit price of an item
 * changes when more items are purchased at the same time. For example:
 *
 * - 1  item  at $5.00 each
 * - 5  items at $4.00 each
 * - 10 items at $3.00 each
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityDiscount extends SwatDBDataObject
{
    /**
     * Unique identifier of this quantity discount.
     *
     * @var int
     */
    public $id;

    /**
     * Quantity required for this discount to apply.
     *
     * @var int
     */
    public $quantity;

    /**
     * @var StoreRegion
     */
    protected $region;

    /**
     * @var bool
     */
    protected $limit_by_region = true;

    /**
     * Cache of price of item to use at this quantity indexed by region id.
     *
     * This is an array of floats.
     *
     * @var array
     */
    protected $price = [];

    /**
     * Sets the region to use when loading region-specific fields for this
     * quantity discount.
     *
     * @param StoreRegion $region   the region to use
     * @param bool        $limiting whether or not to not load this quantity
     *                              discount if it is not available in the given
     *                              region
     */
    public function setRegion(StoreRegion $region, $limiting = true)
    {
        $this->region = $region;
        $this->limit_by_region = $limiting;
    }

    /**
     * Gets the price of this quantity discount in a region.
     *
     * @param StoreRegion $region optional. Region for which to get price. If
     *                            no region is specified, the region set using
     *                            {@link StoreQuantityDiscount::setRegion()}
     *                            is used.
     *
     * @return float the price of this quantity discount in the given region
     *               or null if this quantity discount has no price in the
     *               given region
     */
    public function getPrice($region = null)
    {
        if ($region !== null && !($region instanceof StoreRegion)) {
            throw new StoreException(
                '$region must be an instance of StoreRegion.'
            );
        }

        // If region is not specified but is set through setRegion() use
        // that region instead.
        if ($region === null && $this->region !== null) {
            $region = $this->region;
        }

        // A region is required.
        if ($region === null) {
            throw new StoreException(
                '$region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        $price = null;

        if ($this->region->id == $region->id
            && isset($this->price[$region->id])) {
            $price = $this->price[$region->id];
        } else {
            // Price is not loaded, load from specified region through region
            // bindings.
            $region_bindings = $this->region_bindings;
            foreach ($region_bindings as $binding) {
                if ($binding->getInternalValue('region') == $region->id) {
                    $price = $binding->price;
                    $this->price[$region->id] = $price;
                    break;
                }
            }
        }

        return $price;
    }

    /**
     * Gets the displayable price of this quantity discount including any
     * sale discounts.
     *
     * @param StoreRegion $region optional. Region for which to get price. If
     *                            no region is specified, the region set using
     *                            {@link StoreItem::setRegion()} is used.
     * @param SwatDate    $date   optional. Date on which to get price. If no date is
     *                            specified, we get the current price. Otherwise we
     *                            get the price as it would be on that date.
     *
     * @return float the displayable price of this quanitity discount in the
     *               given region or null if this quantity discount has no
     *               price in the given region
     */
    public function getDisplayPrice($region = null, ?SwatDate $date = null)
    {
        $price = $this->getPrice($region);

        $sale = $this->item->getActiveSaleDiscount($date);
        if ($sale !== null) {
            $sale_discount_price = $this->item->getSaleDiscountPrice();
            if ($sale_discount_price == null) {
                $price = round($price * (1 - $sale->discount_percentage), 2);
            } else {
                $single_price = $this->item->getPrice();
                $price = round($sale_discount_price * $price / $single_price, 2);
            }
        }

        return $price;
    }

    protected function init()
    {
        $this->table = 'QuantityDiscount';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'item',
            SwatDBClassMap::get(StoreItem::class)
        );
    }

    protected function initFromRow($row)
    {
        parent::initFromRow($row);

        if (is_object($row)) {
            $row = get_object_vars($row);
        }

        if (isset($row['region_id'])) {
            if (isset($row['price'])) {
                $this->price[$row['region_id']] = $row['price'];
            }
        }
    }

    protected function getSerializableSubDataObjects()
    {
        return array_merge(
            parent::getSerializableSubDataObjects(),
            ['region_bindings']
        );
    }

    // loader methods

    protected function loadRegionBindings()
    {
        $sql = 'select * from QuantityDiscountRegionBinding
			where quantity_discount = %s';

        $sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreQuantityDiscountRegionBindingWrapper::class)
        );
    }

    // saver methods

    /**
     * Automatically saves StoreQuantityDiscountRegionBinding sub-data-objects when this
     * StoreQuantityDiscount object is saved.
     */
    protected function saveRegionBindings()
    {
        foreach ($this->region_bindings as $binding) {
            $binding->quantity_discount = $this;
        }

        $this->region_bindings->setDatabase($this->db);
        $this->region_bindings->save();
    }
}
