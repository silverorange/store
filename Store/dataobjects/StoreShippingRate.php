<?php

/**
 * A shiping rate data object.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingRate extends SwatDBDataObject
{
    // {{{ public properties

    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Threshold.
     *
     * @var float
     */
    public $threshold;

    /**
     * Amount in dollars.
     *
     * @var float
     */
    public $amount;

    /**
     * Percentage.
     *
     * @var float
     */
    public $percentage;

    // }}}
    // {{{ protected function init()

    protected function init()
    {
        $this->table = 'ShippingRate';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'region',
            SwatDBClassMap::get('StoreRegion')
        );

        $this->registerInternalProperty(
            'shipping_type',
            SwatDBClassMap::get('StoreShipingType')
        );
    }

    // }}}
}
