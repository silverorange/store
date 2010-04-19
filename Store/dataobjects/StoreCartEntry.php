<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrderItem.php';
require_once 'Store/dataobjects/StoreItemAlias.php';

/**
 * An entry in a shopping cart for an e-commerce web application
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
 * @package   Store
 * @copyright 2005-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCart
 */
class StoreCartEntry extends SwatDBDataObject
{
	// {{{ class constants

	/**
	 * Valid sources for where the cart entry was created.
	 */
	const SOURCE_PRODUCT_PAGE       = 1;
	const SOURCE_QUICK_ORDER        = 2;
	const SOURCE_CATEGORY_PAGE      = 3;
	const SOURCE_ACCOUNT_ORDER_PAGE = 4;
	const SOURCE_INVOICE            = 5;
	const SOURCE_ARTICLE_PAGE       = 6;
	const SOURCE_SEARCH_PAGE        = 7;
	const SOURCE_CART_PAGE          = 8;

	// }}}
	// {{{ public properties

	/**
	 * A unique identifier of this cart entry
	 *
	 * The unique identifier is not always present on every cart entry.
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The session this cart belongs to
	 *
	 * If this cart does not belong to an account, it must belong to a session.
	 *
	 * @var string
	 */
	public $sessionid;

	/**
	 * Number of individual items in this cart entry
	 *
	 * This does not represent the number of StoreItem objects in this cart
	 * entry -- that number is always one. This number instead represents the
	 * quantity of the StoreItem that the customer has added to their cart.
	 *
	 * @var integer
	 */
	public $quantity;

	/**
	 * Whether or not this cart entry is saved for later
	 *
	 * Entries that are saved for later are not included in orders.
	 *
	 * @var boolean
	 */
	public $saved;

	/**
	 * Whether or not this cart entry was created on the quick order page
	 *
	 * @var boolean
	 */
	public $quick_order;

	/**
	 * Where this cart entry was created.
	 *
	 * @var integer
	 */
	public $source;

	/**
	 * Optional category id which was the source of this cart entry.
	 *
	 * @var integer
	 */
	public $source_category;

	/*
	 * A custom override price for item's without a fixed price like gift
	 * certificates
	 *
	 * @var float
	 */
	public $custom_price;

	// }}}
	// {{{ public function getQuantity()

	/**
	 * Gets the number of items this cart entry represents
	 *
	 * @return integer the number of items this cart entry represents.
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	// }}}
	// {{{ public function setQuantity()

	/**
	 * Sets the number of items this cart entry represents
	 *
	 * @param integer $quantity the new quantity of this entry's item.
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = (integer)$quantity;
	}

	// }}}
	// {{{ public function getItemId()

	/**
	 * Gets the id of the item in this cart entry
	 *
	 * @return integer the id of the item of this cart entry.
	 */
	public function getItemId()
	{
		return $this->item->id;
	}

	// }}}
	// {{{ public function getQuantityDiscountedItemPrice()

	/**
	 * Gets the unit cost of the StoreItem with quantity discounts
	 *
	 * The unit cost is calculated using the current quantity and quantity
	 * discounts.
	 *
	 * @return double the unit cost of the StoreItem for this cart entry.
	 */
	public function getQuantityDiscountedItemPrice()
	{
		$price = $this->item->getPrice();

		// This relies on the ordering of quantity discounts. They are ordered
		// with the smallest quantity first.
		foreach ($this->item->quantity_discounts as $quantity_discount) {
			if ($this->getQuantity() >= $quantity_discount->quantity)
				$price = $quantity_discount->getPrice();
		}

		return $price;
	}

	// }}}
	// {{{ public function getCalculatedItemPrice()

	/**
	 * Gets the unit cost of the StoreItem for this cart entry
	 *
	 * The unit cost is calculated based on discounts.
	 *
	 * @return double the unit cost of the StoreItem for this cart entry.
	 */
	public function getCalculatedItemPrice($apply_sale_discounts = true)
	{
		if ($this->custom_price !== null) {
			$price = $this->custom_price;
		} else {
			$price = $this->getQuantityDiscountedItemPrice();

			if ($apply_sale_discounts) {
				$sale = $this->item->getActiveSaleDiscount();
				if ($sale !== null)
					$price = round($price * (1 - $sale->discount_percentage), 2);
			}
		}

		return $price;
	}

	// }}}
	// {{{ public function getDiscount()

	/**
	 * Gets how much money is saved by discounts
	 *
	 * Discounts include all types of discount schemes. By default, this is
	 * quantity discounts. Subclasses are encouraged to account for other
	 * site-specific discounts in this method.
	 *
	 * @return double how much money is saved from discounts or zero if no
	 *                 discount applies.
	 */
	public function getDiscount()
	{
		return $this->item->getOriginalPrice() - $this->getCalculatedItemPrice();
	}

	// }}}
	// {{{ public function getDiscountExtension()

	/**
	 * Gets how much total money is saved by discounts
	 *
	 * @return double how much money is saved from discounts or zero if no
	 *                 discount applies.
	 *
	 * @see StoreCartEntry::getDiscount()
	 */
	public function getDiscountExtension()
	{
		return $this->getDiscount() * $this->getQuantity();
	}

	// }}}
	// {{{ public function getExtension()

	/**
	 * Gets the extension cost of this cart entry
	 *
	 * The cost is calculated as this cart entry's item unit cost multiplied
	 * by this cart entry's quantity. This value is called the extension.
	 *
	 * @return double the extension cost of this cart entry.
	 */
	public function getExtension($apply_sale_discounts = true)
	{
		$price = $this->getCalculatedItemPrice($apply_sale_discounts);
		$extension = $price * $this->getQuantity();

		return $extension;
	}

	// }}}
	// {{{ public function hasSameItem()

	/**
	 * Compares this entry with another entry by item
	 *
	 * @param StoreCartEntry $entry the entry to compare this entry to.
	 *
	 * @return boolean True if the two items are the same, false if they're
	 *                  not. Items are considered the same if they have the same
	 *                  id and the same custom price.
	 */
	public function hasSameItem(StoreCartEntry $entry)
	{
		return (($this->custom_price == $entry->custom_price) &&
			($this->getItemId() === $entry->getItemId()));
	}

	// }}}
	// {{{ public function compare()

	/**
	 * Compares this entry with another entry by item
	 *
	 * @param StoreCartEntry $entry the entry to compare this entry to.
	 *
	 * @return integer a tri-value indicating how this entry compares to the
	 *                  given entry. The value is negative if this entry is
	 *                  less than the given entry, zero if this entry is equal
	 *                  to the given entry and positive it this entry is
	 *                  greater than the given entry.
	 */
	public function compare(StoreCartEntry $entry)
	{
		// order by product-title, product-id, item-displayorder, item-id

		$item1 = $this->item;
		$item2 = $entry->item;
		$product1 = $this->item->product;
		$product2 = $entry->item->product;

		$title_cmp = strcmp($product1->title, $product2->title);

		if ($title_cmp != 0)
			return $title_cmp;
		elseif ($product1->id != $product1->id)
			return ($product1->id < $product2->id) ? -1 : 1;
		elseif ($item1->displayorder != $item2->displayorder)
			return ($item1->displayorder < $item2->displayorder) ? -1 : 1;
		elseif ($this->getItemId() != $entry->getItemId())
			return ($this->getItemId() < $entry->getItemId()) ? -1 : 1;

		return 0;
	}

	// }}}
	// {{{ public function combine()

	/**
	 * Combines an entry with this entry
	 *
	 * The quantity is updated to the sum of quantities of the two entries.
	 * This is useful if you want to add entries to a cart that already has
	 * an equivalent entry.
	 *
	 * @param StoreCartEntry $entry the entry to combine with this entry.
	 */
	public function combine(StoreCartEntry $entry)
	{
		if ($this->hasSameItem($entry))
			$this->quantity += $entry->getQuantity();
	}

	// }}}
	// {{{ public function isSaved()

	/**
	 * Whether or not this entry is saved for later
	 *
	 * @return boolean whether or not this entry is saved for later.
	 */
	public function isSaved()
	{
		return $this->saved;
	}

	// }}}
	// {{{ public function isAvailable()

	/**
	 * Whether or not this entry is available for order
	 *
	 * @return boolean Whether or not this entry is available for order. Entries
	 *                  are based on item isAvailableInRegion() by default.
	 *                  Subclasses can override this method to provide
	 *                  additional availability filtering.
	 *
	 * @see StoreItem::isAvailableInRegion()
	 */
	public function isAvailable(StoreRegion $region = null)
	{
		return $this->item->isAvailableInRegion($region);
	}

	// }}}
	// {{{ public function createOrderItem()

	/**
	 * Creates a new order item dataobject that corresponds to this cart entry
	 *
	 * @return StoreOrderItem a new StoreOrderItem object that corresponds to
	 *                         this cart entry.
	 */
	public function createOrderItem()
	{
		$class = SwatDBClassMap::get('StoreOrderItem');
		$order_item = new $class();

		$order_item->setCartEntryId($this->id);
		$order_item->sku                = $this->item->sku;
		$order_item->price              = $this->getCalculatedItemPrice();
		$order_item->custom_price       = ($this->custom_price !== null);
		$order_item->quantity           = $this->getQuantity();
		$order_item->extension          = $this->getExtension();
		$order_item->description        = $this->getOrderItemDescription();
		$order_item->item               = $this->item->id;
		$order_item->product            = $this->item->product->id;
		$order_item->product_title      = $this->item->product->title;
		$order_item->source             = $this->source;
		$order_item->source_category    = $this->source_category;
		$order_item->discount           = $this->getDiscount();
		$order_item->discount_extension = $this->getDiscountExtension();

		if ($this->alias !== null)
			$order_item->alias_sku = $this->alias->sku;

		$sale = $this->item->getActiveSaleDiscount();
		if ($sale !== null)
			$order_item->sale_discount = $sale->id;

		// set database if it exists
		if ($this->db !== null)
			$order_item->setDatabase($this->db);

		return $order_item;
	}

	// }}}
	// {{{ protected function getOrderItemDescription()

	protected function getOrderItemDescription()
	{
		$description = array();

		foreach ($this->item->getDescriptionArray() as $element)
			$description[] = '<div>'.$element.'</div>';

		return implode("\n", $description);
	}

	// }}}
	// {{{ protected function init()

	/**
	 * Sets up this cart entry data object
	 */
	protected function init()
	{
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->registerInternalProperty('item',
			SwatDBClassMap::get('StoreItem'));

		$this->registerInternalProperty('account',
			SwatDBClassMap::get('StoreAccount'));

		$this->registerInternalProperty('alias',
			SwatDBClassMap::get('StoreItemAlias'));

		$this->registerDeprecatedProperty('quick_order');

		$this->table = 'CartEntry';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
