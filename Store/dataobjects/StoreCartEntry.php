<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrderItem.php';

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
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCart
 */
abstract class StoreCartEntry extends StoreDataObject
{
	// {{{ public properties

	/**
	 * The id of this cart entry
	 *
	 * @var string
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

	// }}}
	// {{{ protected function init()

	/**
	 * Sets up this cart entry data object
	 *
	 * IMPORTANT:
	 * You better override this in your subclass or you'll get weird errors.
	 */
	protected function init()
	{
		$this->registerInternalProperty('item',
			$this->class_map->resolveClass('StoreItem'));

		$this->registerInternalProperty('account',
			$this->class_map->resolveClass('StoreAccount'));

		$this->table = 'CartEntry';
		$this->id_field = 'integer:id';
	}

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
	// {{{ public function getCalculatedItemPrice()

	/**
	 * Gets the unit cost of the StoreItem for this cart entry
	 *
	 * The unit cost is caucluated based on discounts.
	 *
	 * @return double the unit cost of the StoreItem for this cart entry.
	 */
	public function getCalculatedItemCost()
	{
		$price = $this->item->price;

		// This relies on the ordering of quantity discounts. They are ordered
		// with the largest quantity first.
		foreach ($this->item->quantitydiscounts as $quantity_discount) {
			if ($this->getQuantity() >= $quantity_discount->quantity) {
				$price = $quantity_discount->price;
				break;
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
		$return = 0;
		$extension = $this->item->price * $this->getQuantity();

		// This relies on the ordering of quantity discounts. They are ordered
		// with the largest quantity first.
		foreach ($this->item->quantitydiscounts as $quantity_discount) {
			if ($this->getQuantity() >= $quantity_discount->quantity) {
				$return = $extension -
					$quantity_discount->price * $this->getQuantity();

				break;
			}
		}

		return $return;
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
	public function getExtension()
	{
		return ($this->getCalculatedItemCost() * $this->getQuantity());
	}

	// }}}
	// {{{ public function compare()

	/**
	 * Compares this entry with another entry by item
	 *
	 * @param StoreCartEntry $entry the entry to compare this entry to.
	 *
	 * @return integer a tri-value indicating how this entry compares to the
	 *                  given entry. The value is negative is this entry is
	 *                  less than the given entry, zero if this entry is equal
	 *                  to the given entry and positive it this entry is
	 *                  greater than the given entry.
	 */
	public function compare(StoreCartEntry $entry)
	{
		return strcmp($this->getItemId(), $entry->getItemId());
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
		if ($this->compare($entry) == 0)
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
	// {{{ public function createOrderItem()

	/**
	 * Create a new order item dataobject that corresponds to this cart entry.
	 *
	 * @return StoreOrderItem a new StoreOrderItem object.
	 */
	public function createOrderItem(StoreCartEntry $entry)
	{
		$class = $this->class_map->resolveClass('StoreOrderItem');
		$order_item = new $class();

		$order_item->price = $this->item->getCalculatedItemPrice();
		$order_item->quantity = $this->item->getQuantity();
		$order_item->extension = $this->item->getExtension();
		$order_item->description = $this->item->getDescription();
		$order_item->product = $this->item->product->id;
		$order_item->product_title = $this->item->product->title;
		$order_item->quick_order = $this->quick_order;

		return $order_item;
	}

	// }}}
}

?>
