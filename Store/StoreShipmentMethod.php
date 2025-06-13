<?php

/**
 * A shipment method for an e-commerce web application.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShipmentMethod
{
    // {{{ public function getTimeToDeliver()

    /**
     * An approximation of how long it takes to ship items with this method.
     *
     * @param mixed $address
     *
     * @return ?DateInterval the approximate time it will take to deliver a
     *                       shipment using this method, or null
     */
    public function getTimeToDeliver($address): ?DateInterval
    {
        return null;
    }

    // }}}
}
