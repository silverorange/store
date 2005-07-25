<?php

/**
 * This class represents an entry in the customer's shopping cart.
 *
 * All cart specific item information is stored in this object. This includes
 * things like special finishes or engraving information that is not specific
 * to an item, but is specific to an item in a customer's shopping cart.
 *
 * For specific sites, this class will be subclassed to provide specific
 * features. For example, on the Hampshire Pewter webstore, this class would 
 * have a getEngravingCost() method.
 *
 * The StoreCart*View classes handle all the displaying of StoreCartEntry
 * objects. StoreCartEntry must provide sufficient toString() methods to allow
 * the StoreCart*View classes to display cart entries. Remember when
 * subclassing to add these toString() methods.
 */
class StoreCartEntry {

	/**
	 * A reference to a StoreItem object
	 *
	 * @var StoreItem
	 * @access private
	 */
	private var $item;

	/**
	 * Number of individual StoreItems in this cart entry
	 *
	 * @var int
	 * @access private
	 */
	private var $quantity;

	/**
	 * Creates a new StoreCartItem
	 *
	 * @param StoreItem $item a reference to the item that this entry holds
	 * @param int $quantity the number of individual items in this entry
	 *
	 * @access public
	 */
	public function __construct($item, $quantity);

	/**
	 * Gets the number of StoreItems this cart entry represents
	 *
	 * @return int
	 *
	 * @access public
	 */
	public function getQuantity();

	/**
	 * Sets the number of StoreItems this cart entry represents
	 *
	 * @param int $quantity the new number
	 *
	 * @access public
	 */
	public function setQuantity($quantity);

	/**
	 * Gets the unit cost of the StoreItem for this cart entry
	 *
	 * @return double the unit cost of the StoreItem for this cart entry.
	 *
	 * @access public
	 */
	public function getItemCost();
	
	/**
	 * Gets the extension cost of this cart entry
	 *
	 * The cost is calculated as this cart entry's item unit cost multiplied
	 * by this cart entry's quantity. This value is called the extension.
	 *
	 * @return double the extension cost of this cart entry.
	 *
	 * @access public
	 */
	public function getExtensionCost();

}


?>
