<?php

/**
 * An entry in a shopping cart for an e-commerce web application
 *
 * All cart specific item information is stored in this object. This includes
 * things like special finishes or engraving information that is not specific
 * to an item, but is specific to an item in a customer's shopping cart.
 *
 * For specific sites, this class will be subclassed to provide specific
 * features. For example, on a site supporting the engraving of items, a
 * subclass of this class could have a getEngravingCost() method.
 *
 * The StoreCart*View classes handle all the displaying of StoreCartEntry
 * objects. StoreCartEntry must provide sufficient toString() methods to allow
 * the StoreCart*View classes to display cart entries. Remember when
 * subclassing this class to add these toString() methods.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCart
 */
class StoreCartEntry
{
	/**
	 * A reference to a StoreItem object
	 *
	 * @var StoreItem
	 */
	private $item;

	/**
	 * Number of individual items in this cart entry
	 *
	 * This does not represent the number of StoreItem objects in this cart
	 * entry -- that number is always one. This number instead represents the
	 * quantity of the StoreItem that the customer has added to their cart.
	 *
	 * @var integer
	 */
	private $quantity;

	/**
	 * Creates a new StoreCartItem
	 *
	 * @param StoreItem $item a reference to the item that this entry holds.
	 * @param integer $quantity the number of individual items in this entry.
	 */
	public function __construct(StoreItem $item, $quantity)
	{
		$this->item = $item;
		$this->quantity = (int)$quantity;
	}

	/**
	 * Gets the number of items this cart entry represents
	 *
	 * @return integer the number of items this cart entry represents.
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * Sets the number of items this cart entry represents
	 *
	 * @param integer $quantity the new quantity of this entry's item.
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = (int)$quantity;
	}

	/**
	 * Gets the unit cost of the StoreItem for this cart entry
	 *
	 * @return double the unit cost of the StoreItem for this cart entry.
	 */
	public function getItemCost()
	{
	}
	
	/**
	 * Gets the extension cost of this cart entry
	 *
	 * The cost is calculated as this cart entry's item unit cost multiplied
	 * by this cart entry's quantity. This value is called the extension.
	 *
	 * @return double the extension cost of this cart entry.
	 */
	public function getExtensionCost()
	{
	}

	/**
	 * Compares this entry with another entry
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
	}

	/**
	 * Combines an entry with this entry
	 *
	 * The quantity is updated to the sum of quantities of the two entries.
	 * This is useful if you want to add entries to this cart that already
	 * exist in this cart.
	 *
	 * @param StoreCartEntry $entry the entry to combine with this entry.
	 */
	public function combine(StoreCartEntry $entry)
	{
		$this->quantity += $entry->getQuantity();
	}
}

?>
