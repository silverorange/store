<?php

/**
 * A saved-cart object.
 *
 * The saved cart is a cart object that is saved for later. Saved carts are not
 * intended for purchase. Saved carts do not have price totalling methods. This
 * This class contains saved-cart functionality common to all sites. It is
 * intended to be extended on a per-site basis.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreCartModule, StoreCart
 */
class StoreSavedCart extends StoreCart
{
    /**
     * Loads this cart.
     */
    public function load()
    {
        $this->entries = [];

        if ($this->module instanceof StoreCartModule) {
            foreach ($this->module->getEntries() as $entry) {
                if ($entry->saved) {
                    $this->entries[] = $entry;
                    $this->entries_by_id[$entry->id] = $entry;
                }
            }
        }
    }

    /**
     * Sets the saved flag to true on entries in this cart that are about to be
     * saved.
     *
     * @param StoreCartEntry $entry the entry to process
     */
    protected function preSaveEntry(StoreCartEntry $entry)
    {
        parent::preSaveEntry($entry);

        $entry->saved = true;
    }
}
