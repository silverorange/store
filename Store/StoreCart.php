<?php

/**
 * Shopping-cart class for silverorange Store package
 *
 * This class contains cart functionality common to all sites. It is typically
 * extended on a per-site basis. For example, HortonCart.
 *
 * StoreCart objects are created per-user and stored in the session.
 * A StoreCart contains a set of CartEntries.
 */
class StoreCart {

	/**
	 * The entries in this cart
	 *
	 * The entries are indexed from 0 to infinity. Most CartEntry lookup methods
	 * use the array index to find entries.
	 *
	 * @var array
	 * @access private
	 */
	private var $entries;

	/**
	 * Adds a StoreCartEntry to this cart
	 *
	 * @param StoreCartEntry $cartEntry a reference to a StoreCartEntry to add.
	 *
	 * @access public
	 */
	public function addEntry($cartEntry);

	/**
	 * Removes a StoreCartEntry from this cart
	 *
	 * @param int $cartEntryId the index value of the StoreCartEntry to remove.
	 *
	 * @throws StoreCartEntryNotFoundException
	 *
	 * @access public
	 */
	public function removeEntryById($cartEntryId);

	/**
	 * Gets the internal array of StoreCartEntries
	 *
	 * @return array an array of StoreCartEntry objects.
	 *
	 * @access public
	 */
	public function getEntries();

	/**
	 * Get a reference to a specific StoreCartEntry in this cart
	 *
	 * @param int $cartEntryId the index value of the StoreCartEntry to get.
	 *
	 * @return StoreCartEntry
	 *
	 * @throws StoreCartEntryNotFoundException
	 *
	 * @access public
	 */
	public function getEntryById($cartEntryId);

	/**
	 * Returns an array of entries in the cart based on the database item id
	 *
	 * An array is returned because database ids are not required to be unique
	 * across StoreCartItems in a single cart.
	 *
	 * @param int $itemId the database id of the StoreItem in the cart to be
	 *                     returned.
	 *
	 * @return array and array of StoreCartEntries
	 *
	 * @access public
	 */
	public function getEntriesByItemId($itemId);

	/**
	 * Removes all entries from this cart
	 *
	 * @return the array of StoreCartEntry objects that were removed from this
	 *          cart.
	 *
	 * @access public
	 */
	public function removeAllEntries();
	
	/**
	 * Checks if this cart is empty
	 *
	 * @return boolean whether this cart is empty or not.
	 *
	 * @access public
	 */
	public function isEmpty();
	
	/**
	 * Gets the number of StoreCartEntry items in this cart
	 *
	 * @return int the number of StoreCartEntry objects in this cart
	 *
	 * @access public
	 */
	public function getEntryCount();

	/**
	 * Returns the number of StoreItems in this cart
	 *
	 * The number is calculated based based on StoreCartEntry quantities.
	 *
	 * @return int the number of StoreItems in this cart.
	 *
	 * @access public
	 */
	public function getTotalQuantity();

	/*
	 * Implementation note:
	 *   Totalling methods should call protected methods to ease sub-classing
	 *   the StoreCart class.
	 */

	/**
	 * Gets the value of taxes for this cart
	 *
	 * Calculates applicable taxes based on the contents of this cart. Tax
	 * Calculations need to know where purchase is made in order to correctly
	 * apply tax.
	 *
	 * @param StoreAddress $address a StoreAddress where this purchase is made
	 *                               from.
	 *
	 * @return double the value of tax for this cart.
	 *
	 * @access public
	 */
	public function getTaxCost($address);

	/**
	 * Gets the total cost for an order of the contents of this cart
	 *
	 * The total is calculated as subtotal + tax + shipping
	 *
	 * @return double the cost of this cart's contents
	 *
	 * @access public
	 */
	public function getTotalCost();

	/**
	 * Gets the cost of shipping the contents of this cart to a location
	 *
	 * Needs to know where it is shipping to, and what shipment method is used.
	 *
	 * @param StoreAddress $address the address the cart contents will be
	 *                               shipped to.
	 * @param StoreShipmentMethod $method the method the cart contents will be
	 *                                     shipped via.
	 *
	 * @return double the cost of shipping this order
	 *
	 * @access public
	 */
	public function getShippingCost($address, $method);

	/**
	 * Gets the cost of the StoreCartEntries in this cart
	 *
	 * @return double the sum of the extensions of all StoreCartEntry objects
	 *                 in this cart.
	 *
	 * @access public
	 */
	public function getSubtotalCost();
}

?>
