<?php

/**
 * A checkout cart object.
 *
 * The checkout cart is a cart object that is intended for purchase. Checkout
 * carts have price totalling methods and methods to get available and
 * unavailable entries. This class contains checkout cart functionality common
 * to all sites. It is intended to be extended on a per-site basis.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreCartModule, StoreCart
 */
abstract class StoreCheckoutCart extends StoreCart
{
    // {{{ public function init()

    public function init()
    {
        parent::init();
        $this->restoreAbandonedCartEntries();
    }

    // }}}
    // {{{ public function load()

    /**
     * Loads this cart.
     */
    public function load()
    {
        $this->entries = [];

        if ($this->module instanceof StoreCartModule) {
            if ($this->app->session->isLoggedIn()) {
                $account_id = $this->app->session->getAccountId();
                foreach ($this->module->getEntries() as $entry) {
                    if ($entry->getInternalValue('account') == $account_id
                        && !$entry->saved) {
                        $this->entries[] = $entry;
                        $this->entries_by_id[$entry->id] = $entry;
                    }
                }
            } else {
                foreach ($this->module->getEntries() as $entry) {
                    if (session_id() == $entry->sessionid && !$entry->saved) {
                        $this->entries[] = $entry;
                        $this->entries_by_id[$entry->id] = $entry;
                    }
                }
            }
        }
    }

    // }}}
    // {{{ public function checkoutEnabled()

    /**
     * Whether or not the customer is allowed to check out.
     *
     * This method can be useful for checking item purchasing rules (
     * such as a minimum purchase amount). If the check out is not enabled,
     * the buttons will be insensitive and the checkout inaccessible (
     * the customer will be relocated back to the cart where a message can
     * be displayed).
     *
     * @return bool Whether or not the customer is allowed to check out
     */
    public function checkoutEnabled()
    {
        // no cart, no checkout
        if (count($this->app->cart->checkout->getAvailableEntries()) <= 0) {
            return false;
        }

        // check item minimum quantity group
        $groups = [];

        foreach ($this->getAvailableEntries() as $entry) {
            $group = $entry->item->getInternalValue('minimum_quantity_group');

            if ($group !== null) {
                if (!isset($groups[$group])) {
                    $groups[$group] = new stdClass();
                    $groups[$group]->quantiy = 0;
                    $groups[$group]->group =
                        $entry->item->minimum_quantity_group;
                }

                $groups[$group]->quantity +=
                    ($entry->quantity * $entry->item->part_count);
            }
        }

        foreach ($groups as $g) {
            if ($g->quantity < $g->group->minimum_quantity) {
                return false;
            }
        }

        return true;
    }

    // }}}
    // {{{ public function getAvailableEntries()

    /**
     * Gets the entries of this cart that are available for order.
     *
     * Only available entries are used for cart cost totalling methods.
     *
     * @return array the entries of this cart that are not available for order.
     *               This availabiliy is determined by the isAvailable() method
     *               on each CartEntry. Subclasses may override this method to
     *               provide additional availability filtering on entries.
     *
     * @see StoreCheckoutCart::getUnavailableEntries()
     * @see StoreCartEntry::isAvailable()
     */
    public function &getAvailableEntries()
    {
        $entries = [];
        $region = $this->app->getRegion();

        foreach ($this->getEntries() as $entry) {
            if ($entry->isAvailable($region) === true) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    // }}}
    // {{{ public function getUnavailableEntries()

    /**
     * Gets the entries of this cart that are not available for order.
     *
     * Only available entries are used for cart cost totalling methods.
     *
     * @return array the entries of this cart that are not available for order.
     *               This availabiliy is determined by the isAvailable() method
     *               on each CartEntry. Subclasses may override this method to
     *               provide additional availability filtering on entries.
     *
     * @see StoreCheckoutCart::getAvailableEntries()
     * @see StoreCartEntry::isAvailable()
     */
    public function &getUnavailableEntries()
    {
        $entries = [];
        $region = $this->app->getRegion();

        foreach ($this->getEntries() as $entry) {
            if ($entry->isAvailable($region) === false) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    // }}}
    // {{{ protected function preSaveEntry()

    /**
     * Sets the saved flag to false on entries in this cart that are about to
     * be saved.
     *
     * @param StoreCartEntry $entry the entry to process
     */
    protected function preSaveEntry(StoreCartEntry $entry)
    {
        parent::preSaveEntry($entry);

        $entry->saved = false;
    }

    // }}}
    // {{{ protected function validateCombinedEntry()

    protected function validateCombinedEntry(StoreCartEntry $entry)
    {
        $valid = parent::validateCombinedEntry($entry);

        // Check minimum quantity
        if ($entry->item->minimum_quantity > 1) {
            if ($entry->getQuantity() < $entry->item->minimum_quantity) {
                $entry->setQuantity($entry->item->minimum_quantity);

                $message = new SwatMessage(Store::_('Minimum Quantity'));
                $message->secondary_content = sprintf(
                    Store::_(
                        '“%s” item #%s is only available in a minimum quantity ' .
                        'of %s. The quantity in your cart has been increased ' .
                        'to %s.'
                    ),
                    $entry->item->product->title,
                    $entry->item->sku,
                    $entry->item->minimum_quantity,
                    $entry->getQuantity()
                );

                $this->addMessage($message);
            }

            if ($entry->item->minimum_multiple) {
                $remainder = $entry->getQuantity() %
                    $entry->item->minimum_quantity;

                if ($remainder !== 0) {
                    $entry->setQuantity($entry->getQuantity() +
                        $entry->item->minimum_quantity - $remainder);

                    $message = new SwatMessage(Store::_('Required Quantity'));
                    $message->secondary_content = sprintf(
                        Store::_(
                            '“%s” item #%s is only available in multiples of %s. ' .
                            'The quantity in your cart has been increased to %s.'
                        ),
                        $entry->item->product->title,
                        $entry->item->sku,
                        $entry->item->minimum_quantity,
                        $entry->getQuantity()
                    );

                    $this->addMessage($message);
                }
            }
        }

        return $valid;
    }

    // }}}
    // {{{ protected function restoreAbandonedCartEntries()

    /**
     * Checks for a persistant saved session cart and updates the cart entry's
     * session identifiers to match the current session before this cart is
     * loaded.
     *
     * This method makes carrying over session cart content work.
     */
    protected function restoreAbandonedCartEntries()
    {
        // don't try to restore the cart entries if we don't have a cookie
        // module
        if (!isset($this->app->cookie)) {
            return;
        }

        try {
            $cookie_name = $this->getCartSessionCookieName();

            if (isset($this->app->cookie->{$cookie_name})) {
                if (!$this->app->session->isActive()) {
                    $this->app->session->activate();
                }

                $previous_session = $this->app->cookie->{$cookie_name};
                $current_session = $this->app->session->getSessionId();

                if ($previous_session !== $current_session) {
                    $sql = 'update CartEntry set sessionid = %s
						where sessionid = %s';

                    $sql = sprintf(
                        $sql,
                        $this->app->db->quote($current_session, 'text'),
                        $this->app->db->quote($previous_session, 'text')
                    );

                    SwatDB::exec($this->app->db, $sql);
                }
            }
        } catch (SiteCookieException $e) {
            // silently handle bad cookie exception
        }

        if ($this->app->session->isActive()) {
            $this->app->cookie->setCookie(
                $cookie_name,
                $this->app->session->getSessionId()
            );
        }
    }

    // }}}
    // {{{ protected function getCartSessionCookieName()

    protected function getCartSessionCookieName()
    {
        return 'cart_session';
    }

    // }}}

    // price calculation methods
    // {{{ public function getTotal()

    /**
     * Gets the total cost for an order of the contents of this cart.
     *
     * By default, the total is calculated as item total + tax + shipping.
     * Subclasses may override this to calculate totals differently.
     *
     * @param StoreAddress                   $billing_address  the billing address of the order
     * @param StoreAddress                   $shipping_address the shipping address of the order
     * @param StoreShippingType              $shipping_type    the shipping type of the order
     * @param StoreOrderPaymentMethodWrapper $payment_methods  the payment
     *                                                         methods of the
     *                                                         order
     *
     * @return float the cost of this cart's contents
     */
    public function getTotal(
        ?StoreAddress $billing_address = null,
        ?StoreAddress $shipping_address = null,
        ?StoreShippingType $shipping_type = null,
        ?StoreOrderPaymentMethodWrapper $payment_methods = null
    ) {
        if ($this->cachedValueExists('store-total')) {
            $total = $this->getCachedValue('store-total');
        } else {
            $total = 0;
            $total += $this->getItemTotal();

            $total += $this->getSurchargeTotal($payment_methods);

            $total += $this->getTaxTotal(
                $billing_address,
                $shipping_address,
                $shipping_type,
                $payment_methods
            );

            $total += $this->getShippingTotal(
                $billing_address,
                $shipping_address,
                $shipping_type
            );

            $this->setCachedValue('store-total', $total);
        }

        return $total;
    }

    // }}}
    // {{{ public function getSubtotal()

    public function getSubtotal()
    {
        if ($this->cachedValueExists('store-subtotal')) {
            $total = $this->getCachedValue('store-subtotal');
        } else {
            $total = 0;
            $total += $this->getItemTotal();
            $this->setCachedValue('store-subtotal', $total);
        }

        return $total;
    }

    // }}}
    // {{{ public function getItemTotal()

    /**
     * Gets the cost of the StoreCartEntry objects in this cart.
     *
     * This is sometimes called the subtotal.
     *
     * @return float the sum of the extensions of all StoreCartEntry objects
     *               in this cart
     */
    public function getItemTotal()
    {
        if ($this->cachedValueExists('store-item-total')) {
            $total = $this->getCachedValue('store-item-total');
        } else {
            $total = 0;
            $entries = $this->getAvailableEntries();
            foreach ($entries as $entry) {
                $total += $entry->getExtension();
            }

            $this->setCachedValue('store-item-total', $total);
        }

        return $total;
    }

    // }}}
    // {{{ public function getSurchargeTotal()

    /**
     * Gets the total of any surcharges.
     *
     * @param StoreOrderPaymentMethodWrapper $payment_methods the payment method
     *                                                        of the order
     *
     * @return float the sum of all surcharges
     */
    public function getSurchargeTotal(
        ?StoreOrderPaymentMethodWrapper $payment_methods = null
    ) {
        if ($this->cachedValueExists('store-surcharge-total')) {
            $total = $this->getCachedValue('store-surcharge-total');
        } else {
            $total = 0;

            if ($payment_methods instanceof StoreOrderPaymentMethodWrapper) {
                foreach ($payment_methods as $payment_method) {
                    if ($payment_method->surcharge !== null) {
                        $total += $payment_method->surcharge;
                    }
                }
            }

            $this->setCachedValue('store-surcharge-total', $total);
        }

        return $total;
    }

    // }}}
    // {{{ public function getShippingType()

    public function getShippingType()
    {
        $shortname = $this->getShippingTypeDefaultShortname();
        $class_name = SwatDBClassMap::get('StoreShippingType');
        $shipping_type = new $class_name();
        $shipping_type->setDatabase($this->app->db);
        $found = $shipping_type->loadByShortname($shortname);
        if (!$found) {
            throw new StoreException(sprintf(
                '%s shipping rate type missing!',
                $shortname
            ));
        }

        return $shipping_type;
    }

    // }}}
    // {{{ public function getVoucherTotal()

    public function getVoucherTotal(
        ?StoreAddress $billing_address = null,
        ?StoreAddress $shipping_address = null
    ) {
        $cache_key = 'store-voucher-total';

        if ($this->cachedValueExists($cache_key)) {
            $total = $this->getCachedValue($cache_key);
        } else {
            $total = 0;

            if (isset($this->app->session->vouchers)
                && count($this->app->session->vouchers) > 0) {
                foreach ($this->app->session->vouchers as $voucher) {
                    $total += $voucher->amount;
                }

                $cart_total = 0;
                $cart_total += $this->getItemTotal();

                $cart_total += $this->getTaxTotal(
                    $billing_address,
                    $shipping_address
                );

                $cart_total += $this->getShippingTotal(
                    $billing_address,
                    $shipping_address
                );

                $total = min($total, $cart_total);
            }

            $this->setCachedValue($cache_key, $total);
        }

        return $total;
    }

    // }}}
    // {{{ protected function calculateShippingRate()

    protected function calculateShippingRate(
        $item_total,
        ?StoreShippingType $shipping_type = null
    ) {
        if ($shipping_type === null) {
            $shipping_type = $this->getShippingType();
        }

        return $shipping_type->calculateShippingRate(
            $item_total,
            $this->app->getRegion()
        );
    }

    // }}}
    // {{{ protected function getShippingTypeDefaultShortname()

    protected function getShippingTypeDefaultShortname()
    {
        return 'default';
    }

    // }}}
    // {{{ abstract public function getShippingTotal()

    /**
     * Gets the cost of shipping the contents of this cart.
     *
     * @param StoreAddress      $billing_address  the billing address of the order
     * @param StoreAddress      $shipping_address the shipping address of the order
     * @param StoreShippingType $shipping_type    the shipping type of the order
     *
     * @return float the cost of shipping this order
     */
    abstract public function getShippingTotal(
        ?StoreAddress $billing_address = null,
        ?StoreAddress $shipping_address = null,
        ?StoreShippingType $shipping_type = null
    );

    // }}}
    // {{{ abstract public function getTaxTotal()

    /**
     * Gets the total amount of taxes for this cart.
     *
     * Calculates applicable taxes based on the contents of this cart. Tax
     * Calculations need to know where purchase is made in order to correctly
     * apply tax. Payment method is passed in case related surcharges need to be
     * taxed.
     *
     * @param StoreAddress                   $billing_address  the billing address of the order
     * @param StoreAddress                   $shipping_address the shipping address of the order
     * @param StoreShippingType              $shipping_type    the shipping type of the order
     * @param StoreOrderPaymentMethodWrapper $payment_methods  the payment
     *                                                         methods of the
     *                                                         order
     *
     * @return float the tax charged for the contents of this cart
     */
    abstract public function getTaxTotal(
        ?StoreAddress $billing_address = null,
        ?StoreAddress $shipping_address = null,
        ?StoreShippingType $shipping_type = null,
        ?StoreOrderPaymentMethodWrapper $payment_methods = null
    );

    // }}}
    // {{{ abstract public function getTaxProvState()

    abstract public function getTaxProvState(
        ?StoreAddress $billing_address = null,
        ?StoreAddress $shipping_address = null
    );

    // }}}
}
