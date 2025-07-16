<?php

/**
 * A cart object.
 *
 * Carts are containers for cart entry objects. This class contains cart
 * functionality common to all sites. Most site code will want to extend either
 * {@link StoreCheckoutCart} or {@link StoreSavedCart}.
 *
 * Cart entries are automatically indexed by their id. When adding new cart
 * entries to this cart, the entries are assigned an id and indexed.
 *
 * Cart entries may be accessed through their entry id which guarentees a
 * unique cart entry. Alternatively, cart entries may be accessed through item
 * ids which do not necessarily reference a unique cart entry.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreCheckoutCart, StoreSavedCart
 */
abstract class StoreCart extends SwatObject
{
    /**
     * The entries in this cart.
     *
     * This is an array of StoreCartEntry data objects.
     *
     * @var array
     */
    protected $entries = [];

    /**
     * The entries in this cart indexed by their id.
     *
     * This is an array of StoreCartEntry data objects indexed by the entry
     * ids.
     *
     * @var array
     */
    protected $entries_by_id = [];

    /**
     * A cache of cart totals.
     *
     * Cart totalling methods may optionally use this array to cache their
     * values. When the setChanged() method is called, the cache is cleared.
     *
     * @var bool
     *
     * @see StoreCartModule::setChanged()
     */
    protected $totals = [];

    /**
     * The cart module this cart belongs to.
     *
     * @var StoreCartModule
     */
    protected $module;

    /**
     * The application this cart belongs to.
     *
     * @var SiteApplication
     */
    protected $app;

    /**
     * An internal flag marking whether or not the {@link StoreCart::$entries}
     * array is sorted.
     *
     * @var bool
     */
    protected $sorted = false;

    /**
     * Whether to combine entries with identical items.
     *
     * @var bool
     */
    protected $combine_entries = true;

    /**
     * An array of SwatMessages used to display cart entry status messages.
     *
     * @var array
     */
    private $messages = [];

    /**
     * Creates a new cart.
     *
     * @param SiteApplication $app the application this cart belongs to
     */
    public function __construct(SiteApplication $app)
    {
        $this->app = $app;
    }

    public function init() {}

    /**
     * Loads this cart.
     *
     * Subclasses may load this cart from the database, from the session or
     * from this cart's cart module.
     *
     * @see StoreCartModule::getEntries()
     */
    abstract public function load();

    /**
     * Saves this cart to the database.
     */
    public function save()
    {
        foreach ($this->entries as $entry) {
            $this->preSaveEntry($entry);
            $entry->save();
        }
    }

    /**
     * Checks if this cart is empty.
     *
     * @return bool true if this cart is empty and false if it is not
     */
    public function isEmpty()
    {
        return count($this->entries) ? false : true;
    }

    /**
     * Adds a StoreCartEntry to this cart.
     *
     * If an equivalent entry already exists in the cart, the two entries are
     * combined. Validity of an entry is defined in the
     * {@link StoreCart::validateEntry()} method. The entry is only added if
     * it is valid. Equivalency is determined by the
     * {@link StoreCartEntry::hasSameItem()} method.
     *
     * @param StoreCartEntry $entry the StoreCartEntry to add
     *
     * @return StoreCartEntry the added entry. If the entry to be added is not
     *                        valid, null is returned.
     *
     * @see StoreCart::validateEntry()
     * @see StoreCart::validateCombinedEntry()
     */
    public function addEntry(StoreCartEntry $entry)
    {
        $added_entry = null;

        $entry->setDatabase($this->app->db);

        if ($this->validateEntry($entry)) {
            $already_in_cart = false;

            if ($this->combine_entries) {
                // check for existing entry to combine with
                foreach ($this->entries as $key => $existing_entry) {
                    if ($existing_entry->hasSameItem($entry)) {
                        $already_in_cart = true;
                        $backup_entry = clone $existing_entry;
                        $existing_entry->combine($entry);

                        if ($this->validateCombinedEntry($existing_entry)) {
                            $added_entry = $existing_entry;
                            $this->setChanged();
                        } else {
                            // rollback to original entry
                            $this->entries[$key] = $backup_entry;
                            $this->entries_by_id[$backup_entry->id] =
                                $backup_entry;
                        }

                        // we don't need this anymore
                        unset($backup_entry);

                        break;
                    }
                }
            }

            // not combining. add individual entry
            if (!$already_in_cart && $this->validateCombinedEntry($entry)) {
                $this->addNewEntry($entry);
                $added_entry = $entry;
            }
        }

        return $added_entry;
    }

    /**
     * Removes a StoreCartEntry from this cart.
     *
     * @param int $entry_id the index value of the StoreCartEntry object
     *                      to remove
     *
     * @return StoreCartEntry the entry that was removed or null if no entry
     *                        was removed
     */
    public function removeEntryById($entry_id)
    {
        $old_entry = null;

        if (array_key_exists($entry_id, $this->entries_by_id)) {
            $old_entry = $this->removeEntry($this->entries_by_id[$entry_id]);
        }

        return $old_entry;
    }

    /**
     * Removes a StoreCartEntry from this cart.
     *
     * @param StoreCartEntry $entry the StoreCartEntry object to remove
     *
     * @return StoreCartEntry the entry that was removed or null if no entry
     *                        was removed
     */
    public function removeEntry(StoreCartEntry $entry)
    {
        $old_entry = null;

        if (in_array($entry, $this->entries, true)) {
            foreach ($this->entries as $key => $cart_entry) {
                if ($cart_entry === $entry) {
                    $old_entry = $this->entries[$key];
                    unset($this->entries[$key], $this->entries_by_id[$entry->id]);

                    if ($this->module instanceof StoreCartModule) {
                        $this->module->registerRemovedEntry($old_entry);
                    }

                    $this->setChanged();
                    break;
                }
            }
        }

        return $old_entry;
    }

    /**
     * Removes all entries from this cart.
     *
     * @return array the array of StoreCartEntry objects that were removed from
     *               this cart
     */
    public function &removeAllEntries()
    {
        $entries = $this->entries;
        $this->entries = [];
        $this->setChanged();

        foreach ($entries as $entry) {
            if ($this->module instanceof StoreCartModule) {
                $this->module->registerRemovedEntry($entry);
            }
        }

        return $entries;
    }

    /**
     * Gets an entry in this cart by its id.
     *
     * @param int $entry_id the database id of the entry in the cart to
     *                      be returned
     *
     * @return StoreCartEntry the entry with the given id or null if no such
     *                        entry exists in this cart
     */
    public function getEntryById($entry_id)
    {
        $entry = null;

        if (array_key_exists($entry_id, $this->entries_by_id)) {
            $entry = $this->entries_by_id[$entry_id];
        }

        return $entry;
    }

    /**
     * Gets a reference to the internal array of StoreCartEntry objects.
     *
     * The array is sorted by the {@link StoreCart::sort()} method.
     *
     * @return array an array of StoreCartEntry objects
     */
    public function &getEntries()
    {
        $this->sort();

        return $this->entries;
    }

    /**
     * Returns an array of entries in the cart based on the database item id.
     *
     * An array is returned because database ids are not required to be unique
     * across StoreCartItems in a single cart.
     *
     * @param int $item_id the database id of the StoreItem in the cart to
     *                     be returned
     *
     * @return array an array of StoreCartEntry objects
     */
    public function &getEntriesByItemId($item_id)
    {
        $entries = [];
        foreach ($this->entries as $entry) {
            if ($entry->getItemId() == $item_id) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Returns an array of entries in the cart based on the database item SKU.
     *
     * An array is returned because item SKUs are not required to be unique
     * across StoreCartItems in a single cart.
     *
     * @param string $sku the SKU of the StoreItem in the cart to be returned
     *
     * @return array an array of StoreCartEntry objects
     */
    public function &getEntriesByItemSku($sku)
    {
        $entries = [];
        foreach ($this->entries as $entry) {
            if ($entry->getItemSku() == $sku) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Gets the number of StoreCartEntry objects in this cart.
     *
     * @return int the number of StoreCartEntry objects in this cart
     */
    public function getEntryCount()
    {
        return count($this->entries);
    }

    /**
     * Returns the number of StoreItems in this cart.
     *
     * The number is calculated based based on StoreCartEntry quantities.
     *
     * @return int the number of StoreItems in this cart
     */
    public function getItemCount()
    {
        $total_quantity = 0;

        foreach ($this->entries as $entry) {
            $total_quantity += $entry->getQuantity();
        }

        return $total_quantity;
    }

    /**
     * Gets the number of unique products in this cart.
     *
     * @return int the number of unique products in this cart
     */
    public function getProductCount()
    {
        $count = 0;

        $product_id = null;
        foreach ($this->entries as $entry) {
            if ($entry->item->product->id !== $product_id) {
                $count++;
            }

            $product_id = $entry->item->product->id;
        }

        return $count;
    }

    /**
     * Updates the quantity of an entry in this cart.
     *
     * @param StoreCartEntry $entry the entry to update
     * @param int            $value the new entry value
     */
    public function setEntryQuantity(StoreCartEntry $entry, $value)
    {
        foreach ($this->entries as $key => $existing_entry) {
            if ($existing_entry === $entry) {
                if ($value <= 0) {
                    $this->removeEntry($entry);
                } else {
                    $backup_entry = clone $entry;
                    $entry->setQuantity($value);
                    if ($this->validateEntry($entry)
                        && $this->validateCombinedEntry($entry)) {
                        $this->setChanged();
                        unset($backup_entry);
                    } else {
                        // rollback to original entry
                        $this->entries[$key] = $backup_entry;
                        $this->entries_by_id[$backup_entry->id] =
                            $backup_entry;
                    }
                }
            }
        }
    }

    /**
     * Set whether to combine entries for identical items in this cart.
     *
     * @param bool $combine_entries whether to combine entries
     */
    public function setCombineEntries($combine_entries)
    {
        $this->combine_entries = $combine_entries;
    }

    /**
     * Gets the status messages of this cart.
     *
     * @return array an array of SwatMessages
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Returns whether or not this cart has messages.
     *
     * @return bool whether or not this cart has messages
     */
    public function hasMessages()
    {
        return count($this->messages > 0);
    }

    /**
     * Sets the cart module this cart belongs to.
     *
     * @param StoreCartModule $module the cart module this cart belongs to
     */
    public function setCartModule(StoreCartModule $module)
    {
        $this->module = $module;
    }

    /**
     * Adds a status messages to this cart.
     *
     * @param SwatMessage $message status message
     */
    protected function addMessage(SwatMessage $message)
    {
        $this->messages[] = $message;
    }

    /**
     * Checks to see if an entry is valid.
     *
     * Use this method to verify that the item referenced by an entry exists
     * and that the item is available for purchase. Also use this method to
     * ensure an entry's quantity is valid.
     *
     * While validating an entry, this method should add validation error
     * messages to this cart. See {@link StoreCart::addMessage()}.
     *
     * @param StoreCartEntry $entry the cart entry to validate
     *
     * @return bool true if the entry is valid and false if it is not valid
     */
    protected function validateEntry(StoreCartEntry $entry)
    {
        $valid = true;

        if ($entry->quantity <= 0) {
            $message = sprintf(
                Store::_('Quantity of “%s” item #%s must be at least one.'),
                $entry->item->product->title,
                $entry->item->sku
            );

            $this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
            $valid = false;
        }

        return $valid;
    }

    /**
     * Checks to see if an entry is valid after combining the entry with
     * existing entries in this cart.
     *
     * Used to verify quantities meet requirments for purchase.
     *
     * While validating an entry, this method should add validation error
     * messages to this cart. See {@link StoreCart::addMessage()}.
     *
     * @param StoreCartEntry $entry the cart entry to validate when combined
     *                              with the other entries in this cart
     *
     * @return bool true if the entry is valid in combination with the
     *              entries in this cart and false if it is not valid
     */
    protected function validateCombinedEntry(StoreCartEntry $entry)
    {
        return true;
    }

    /**
     * Performs pre-save processing on a single entry.
     *
     * This is meant to be called on all entries before they are saved.
     *
     * @param StoreCartEntry $entry the entry to process
     */
    protected function preSaveEntry(StoreCartEntry $entry)
    {
        $entry->instance = $this->app->getInstance();
    }

    /**
     * Adds a new entry to this cart.
     *
     * This performs maintenance tasks required when adding an actual new
     * entry object to this cart.
     *
     * @param StoreCartEntry $entry the entry to add
     */
    protected function addNewEntry(StoreCartEntry $entry)
    {
        $this->preSaveEntry($entry);
        $entry->save();

        $this->entries[] = $entry;
        $this->entries_by_id[$entry->id] = $entry;

        if ($this->module instanceof StoreCartModule) {
            $this->module->registerAddedEntry($entry);
        }

        $this->setChanged();
    }

    /**
     * Sorts the entries of this cart.
     *
     * This method is called by getEntries() if the cart's sort order is stale.
     */
    protected function sort()
    {
        if (!$this->sorted) {
            $max_ids = [];
            foreach ($this->entries as $entry) {
                $key = $entry->item->product->id;

                if (!isset($max_ids[$key])) {
                    $max_ids[$key] = $entry->id;
                } else {
                    $max_ids[$key] = max($entry->id, $max_ids[$key]);
                }
            }

            foreach ($this->entries as $entry) {
                $key = $entry->item->product->id;
                $entry->setProductMaxCartEntryId($max_ids[$key]);
            }

            usort($this->entries, ['StoreCart', 'compare']);
            $this->sorted = true;
        }
    }

    /**
     * Compares two entries in this cart.
     *
     * This comparison method is used by the StoreCart::sort() method.
     *
     * @param StoreCartEntry $entry1 the cart entry on the left side of the
     *                               comparison
     * @param StoreCartEntry $entry2 the cart entry on the right side of the
     *                               comparison
     *
     * @return int a tri-value where -1 means the left side is less than
     *             the right side, 1 means the left side is greater than
     *             the right side and 0 means the left side and right
     *             side are equivalent
     */
    protected static function compare(
        StoreCartEntry $entry1,
        StoreCartEntry $entry2
    ) {
        return $entry1->compare($entry2);
    }

    // caching methods

    /**
     * Sets this cart as modified.
     *
     * This clears the totals cache if it has entries.
     */
    public function setChanged()
    {
        $this->totals = [];
        $this->sorted = false;

        // clear memcache of mini-cart data
        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs($this->app->session->getSessionId());
        }
    }

    protected function cachedValueExists($name)
    {
        return isset($this->totals[$name]);
    }

    protected function getCachedValue($name)
    {
        return $this->totals[$name];
    }

    protected function setCachedValue($name, $value)
    {
        if (isset($this->totals[$name])) {
            throw new StoreException('Overwriting cached cart value ' .
                "'{$name}'.");
        }

        $this->totals[$name] = $value;
    }
}
