<?php

/**
 * An address belonging to an order for an e-commerce web application.
 *
 * This could represent either a billing or a shipping address.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreOrder::$billing_address, StoreOrder::$shipping_address
 */
class StoreOrderAddress extends StoreAddress
{
    // {{{ protected properties

    /**
     * Id of the account address this order address was created from.
     *
     * @var int
     */
    protected $account_address_id;

    // }}}
    // {{{ public function getAccountAddressId()

    public function getAccountAddressId()
    {
        return $this->account_address_id;
    }

    // }}}
    // {{{ public function clearAccountAddress()

    public function clearAccountAddress()
    {
        $this->account_address_id = null;
    }

    // }}}
    // {{{ public function copyFrom()

    public function copyFrom(StoreAddress $address)
    {
        parent::copyFrom($address);

        if ($address instanceof StoreAccountAddress) {
            $this->account_address_id = $address->id;
        }
    }

    // }}}
    // {{{ public function duplicate()

    public function duplicate(): static
    {
        $new_address = parent::duplicate();

        if ($this->account_address_id !== null) {
            $new_address->account_address_id = $this->account_address_id;
        }

        return $new_address;
    }

    // }}}
    // {{{ protected function init()

    protected function init()
    {
        parent::init();
        $this->table = 'OrderAddress';
    }

    // }}}
    // {{{ protected function getSerializablePrivateProperties()

    protected function getSerializablePrivateProperties()
    {
        $properties = parent::getSerializablePrivateProperties();
        $properties[] = 'account_address_id';

        return $properties;
    }

    // }}}
}
