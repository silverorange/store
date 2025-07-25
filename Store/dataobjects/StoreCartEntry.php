<?php

/**
 * An entry in a shopping cart for an e-commerce web application.
 *
 * All cart specific item information is stored in this object. This includes
 * things like special finishes or engraving information that is not specific
 * to an item, but is specific to an item in a customer's shopping cart.
 *
 * For specific sites, this class must be subclassed to provide specific
 * features. For example, on a site supporting the engraving of items, a
 * subclass of this class could have a getEngravingCost() method.
 *
 * The StoreCart*View classes handle all the displaying of StoreCartEntry
 * objects. StoreCartEntry must provide sufficient toString() methods to allow
 * the StoreCart*View classes to display cart entries. Remember when
 * subclassing this class to add these toString() methods.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreCart
 *
 * @property ?SiteInstance   $instance
 * @property StoreItem       $item
 * @property ?StoreAccount   $account
 * @property ?StoreItemAlias $alias
 */
class StoreCartEntry extends SwatDBDataObject
{
    /**
     * Valid sources for where the cart entry was created.
     */
    public const SOURCE_PRODUCT_PAGE = 1;
    public const SOURCE_CATEGORY_PAGE = 3;
    public const SOURCE_ACCOUNT_ORDER_PAGE = 4;
    public const SOURCE_INVOICE = 5;
    public const SOURCE_ARTICLE_PAGE = 6;
    public const SOURCE_SEARCH_PAGE = 7;
    public const SOURCE_CART_PAGE = 8;

    /**
     * A unique identifier of this cart entry.
     *
     * The unique identifier is not always present on every cart entry.
     *
     * @var int
     */
    public $id;

    /**
     * The session this cart belongs to.
     *
     * If this cart does not belong to an account, it must belong to a session.
     *
     * @var ?string
     */
    public $sessionid;

    /**
     * Number of individual items in this cart entry.
     *
     * This does not represent the number of StoreItem objects in this cart
     * entry -- that number is always one. This number instead represents the
     * quantity of the StoreItem that the customer has added to their cart.
     *
     * @var int
     */
    public $quantity;

    /**
     * Whether or not this cart entry is saved for later.
     *
     * Entries that are saved for later are not included in orders.
     *
     * @var bool
     */
    public $saved;

    /**
     * Where this cart entry was created.
     *
     * @var ?int
     */
    public $source;

    /**
     * Optional category id which was the source of this cart entry.
     *
     * @var ?int
     */
    public $source_category;

    /**
     * A custom override price for item's without a fixed price like gift
     * certificates.
     *
     * @var ?float
     */
    public $custom_price;

    /**
     * @var ?int
     */
    private $product_max_cart_entry_id;

    /**
     * Gets the number of items this cart entry represents.
     *
     * @return int the number of items this cart entry represents
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Sets the number of items this cart entry represents.
     *
     * @param int $quantity the new quantity of this entry's item
     */
    public function setQuantity($quantity)
    {
        $this->quantity = (int) $quantity;
    }

    /**
     * Gets the id of the item in this cart entry.
     *
     * @return int the id of the item of this cart entry
     */
    public function getItemId()
    {
        return $this->item->id;
    }

    /**
     * Gets the sku of the item in this cart entry.
     *
     * @return string the SKU of the item of this cart entry
     */
    public function getItemSku()
    {
        return $this->item->sku;
    }

    /**
     * Gets the unit cost of the StoreItem with quantity discounts.
     *
     * The unit cost is calculated using the current quantity and quantity
     * discounts.
     *
     * @return float the unit cost of the StoreItem for this cart entry
     */
    public function getQuantityDiscountedItemPrice()
    {
        $price = $this->item->getPrice();

        // This relies on the ordering of quantity discounts. They are ordered
        // with the smallest quantity first.
        foreach ($this->item->quantity_discounts as $quantity_discount) {
            if ($this->getQuantity() >= $quantity_discount->quantity) {
                $price = $quantity_discount->getPrice();
            }
        }

        return $price;
    }

    /**
     * Gets the unit cost of the StoreItem for this cart entry.
     *
     * The unit cost is calculated based on discounts.
     *
     * @param mixed $apply_sale_discounts
     *
     * @return float the unit cost of the StoreItem for this cart entry
     */
    public function getCalculatedItemPrice($apply_sale_discounts = true)
    {
        if ($this->custom_price !== null) {
            $price = $this->custom_price;
        } else {
            $price = $this->getQuantityDiscountedItemPrice();

            if ($apply_sale_discounts) {
                $sale = $this->item->getActiveSaleDiscount();
                if ($sale !== null) {
                    $sale_discount_price = $this->item->getSaleDiscountPrice();
                    if ($sale_discount_price == null) {
                        $price = round($price * (1 - $sale->discount_percentage), 2);
                    } else {
                        $single_price = $this->item->getPrice();
                        $price = round($sale_discount_price * $price / $single_price, 2);
                    }
                }
            }
        }

        return $price;
    }

    /**
     * Gets how much money is saved by discounts.
     *
     * Discounts include all types of discount schemes. By default, this is
     * quantity discounts. Subclasses are encouraged to account for other
     * site-specific discounts in this method.
     *
     * @return float how much money is saved from discounts or zero if no
     *               discount applies
     */
    public function getDiscount()
    {
        return $this->item->getOriginalPrice() - $this->getCalculatedItemPrice();
    }

    /**
     * Gets how much total money is saved by discounts.
     *
     * @return float how much money is saved from discounts or zero if no
     *               discount applies
     *
     * @see StoreCartEntry::getDiscount()
     */
    public function getDiscountExtension()
    {
        return $this->getDiscount() * $this->getQuantity();
    }

    /**
     * Gets the extension cost of this cart entry.
     *
     * The cost is calculated as this cart entry's item unit cost multiplied
     * by this cart entry's quantity. This value is called the extension.
     *
     * @param mixed $apply_sale_discounts
     *
     * @return float the extension cost of this cart entry
     */
    public function getExtension($apply_sale_discounts = true)
    {
        $price = $this->getCalculatedItemPrice($apply_sale_discounts);

        return $price * $this->getQuantity();
    }

    /**
     * Compares this entry with another entry by item.
     *
     * @param StoreCartEntry $entry the entry to compare this entry to
     *
     * @return bool True if the two items are the same, false if they're
     *              not. Items are considered the same if they have the same
     *              id and the same custom price.
     */
    public function hasSameItem(StoreCartEntry $entry)
    {
        return ($this->custom_price == $entry->custom_price)
            && ($this->getItemId() === $entry->getItemId());
    }

    /**
     * Compares this entry with another entry by item.
     *
     * @param StoreCartEntry $entry the entry to compare this entry to
     *
     * @return int a tri-value indicating how this entry compares to the
     *             given entry. The value is negative if this entry is
     *             less than the given entry, zero if this entry is equal
     *             to the given entry and positive it this entry is
     *             greater than the given entry.
     */
    public function compare(StoreCartEntry $entry)
    {
        // order by date of most recent items ordered (with product
        // grouping preserved), item-displayorder, item-id

        $item1 = $this->item;
        $item2 = $entry->item;
        $product1 = $this->item->product;
        $product2 = $entry->item->product;

        if ($product1->id != $product2->id) {
            return ($this->getProductMaxCartEntryId() <
                $entry->getProductMaxCartEntryId()) ? 1 : -1;
        }
        if ($item1->displayorder != $item2->displayorder) {
            return ($item1->displayorder < $item2->displayorder) ? -1 : 1;
        }
        if ($this->getItemId() != $entry->getItemId()) {
            return ($this->getItemId() < $entry->getItemId()) ? -1 : 1;
        }

        return 0;
    }

    /**
     * Combines an entry with this entry.
     *
     * The quantity is updated to the sum of quantities of the two entries.
     * This is useful if you want to add entries to a cart that already has
     * an equivalent entry.
     *
     * @param StoreCartEntry $entry the entry to combine with this entry
     */
    public function combine(StoreCartEntry $entry)
    {
        if ($this->hasSameItem($entry)) {
            $this->quantity += $entry->getQuantity();
        }
    }

    /**
     * Whether or not this entry is saved for later.
     *
     * @return bool whether or not this entry is saved for later
     */
    public function isSaved()
    {
        return $this->saved;
    }

    /**
     * Whether or not this entry is available for order.
     *
     * @return bool Whether or not this entry is available for order. Entries
     *              are based on item isAvailableInRegion() by default.
     *              Subclasses can override this method to provide
     *              additional availability filtering.
     *
     * @see StoreItem::isAvailableInRegion()
     */
    public function isAvailable(?StoreRegion $region = null)
    {
        return $this->item->isAvailableInRegion($region);
    }

    /**
     * Creates a new order item dataobject that corresponds to this cart entry.
     *
     * @return StoreOrderItem a new StoreOrderItem object that corresponds to
     *                        this cart entry
     */
    public function createOrderItem()
    {
        $class = SwatDBClassMap::get(StoreOrderItem::class);
        $order_item = new $class();

        $order_item->setCartEntryId($this->id);
        $order_item->sku = $this->item->sku;
        $order_item->price = $this->getCalculatedItemPrice();
        $order_item->custom_price = ($this->custom_price !== null);
        $order_item->quantity = $this->getQuantity();
        $order_item->extension = $this->getExtension();
        $order_item->description = $this->getOrderItemDescription();
        $order_item->item = $this->item->id;
        $order_item->product = $this->item->product->id;
        $order_item->product_title = $this->item->product->title;
        $order_item->source = $this->source;
        $order_item->source_category = $this->source_category;
        $order_item->discount = $this->getDiscount();
        $order_item->discount_extension = $this->getDiscountExtension();

        if ($this->alias !== null) {
            $order_item->alias_sku = $this->alias->sku;
        }

        $sale = $this->item->getActiveSaleDiscount();
        if ($sale !== null) {
            $order_item->sale_discount = $sale->id;
        }

        if ($this->item->getInternalValue('item_group') !== null) {
            $group = $this->item->item_group;
            $order_item->item_group_title = $group->title;
        }

        // set database if it exists
        if ($this->db instanceof MDB2_Driver_Common) {
            $order_item->setDatabase($this->db);
        }

        return $order_item;
    }

    /**
     * Sets the maximum StoreCartEntry::$id for all cart entries in the same
     * product as this entry.
     *
     * Used for ordering items in the cart by the most recently added items
     * first, while still grouping by product.
     *
     * @see StoreCart::sort()
     * @see StoreCartEntry::compare()
     *
     * @param mixed $id
     */
    public function setProductMaxCartEntryId($id)
    {
        $this->product_max_cart_entry_id = $id;
    }

    /**
     * Get the maximum StoreCartEntry::$id for all cart entries in the same
     * product as this entry.
     *
     * @see StoreCart::sort()
     * @see StoreCartEntry::compare()
     */
    public function getProductMaxCartEntryId()
    {
        return $this->product_max_cart_entry_id;
    }

    protected function getOrderItemDescription()
    {
        $description = [];

        foreach ($this->item->getDescriptionArray() as $element) {
            $description[] = '<div>' . SwatString::minimizeEntities($element) .
                '</div>';
        }

        return implode("\n", $description);
    }

    /**
     * Sets up this cart entry data object.
     */
    protected function init()
    {
        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );

        $this->registerInternalProperty(
            'item',
            SwatDBClassMap::get(StoreItem::class)
        );

        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(StoreAccount::class)
        );

        $this->registerInternalProperty(
            'alias',
            SwatDBClassMap::get(StoreItemAlias::class)
        );

        $this->table = 'CartEntry';
        $this->id_field = 'integer:id';
    }
}
