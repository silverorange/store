<?php

require_once 'Store/StoreCartModule.php';

/**
 * A shopping-cart object
 *
 * The shopping cart is a cart object that is intended for purchase. Shopping
 * carts have price totalling methods and methods to get available and
 * unavailable entries. This class contains shopping-cart functionality common
 * to all sites. It is typically extended on a per-site basis.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreShoppingCartModule
 */
abstract class StoreShoppingCartModule extends StoreCartModule
{
	// {{{ public function getAvailableEntries()

	/**
	 * Gets the entries of this cart that are available for order
	 *
	 * Only available entries are used for cart cost totalling methods.
	 *
	 * @return array the entries of this cart that are available for order.
	 *                All entries are returned by default. Subclasses may
	 *                override this method to provide additional availability
	 *                filtering on entries.
	 *
	 * @see StoreCartModule::getUnavailableEntries()
	 */
	public function &getAvailableEntries()
	{
		return $this->entries;
	}

	// }}}
	// {{{ public function getUnavailableEntries()

	/**
	 * Gets the entries of this cart that are not available for order
	 *
	 * Only available entries are used for cart cost totalling methods.
	 *
	 * @return array the entries of this cart that are not available for order.
	 *                No entries are returned by default. Subclasses may
	 *                override this method to provide additional availability
	 *                filtering on entries.
	 *
	 * @see StoreCartModule::getAvailableEntries()
	 */
	public function &getUnavailableEntries()
	{
		$entries = array();
		return $entries;
	}

	// }}}

	// price calculation methods
	// {{{ public abstract function getTaxCost()

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
	 */
	public abstract function getTaxCost(StoreProvState $provstate);
	
	// }}}
	// {{{ public abstract function getTotalCost()

	/**
	 * Gets the total cost for an order of the contents of this cart
	 *
	 * The total is calculated as subtotal + tax + shipping.
	 *
	 * @return double the cost of this cart's contents.
	 */
	public abstract function getTotalCost(StoreProvState $provstate);

	// }}}
	// {{{ public abstract function getShippingCost()

	/**
	 * Gets the cost of shipping the contents of this cart
	 *
	 * @return double the cost of shipping this order.
	 */
	public abstract function getShippingCost();

	// }}}
	// {{{ public function getSubtotalCost()

	/**
	 * Gets the cost of the StoreCartEntry objects in this cart
	 *
	 * @return double the sum of the extensions of all StoreCartEntry objects
	 *                 in this cart.
	 */
	public function getSubtotalCost()
	{
		if ($this->cachedValueExists('store-subtotal')) {
			$subtotal = $this->getCachedValue('store-subtotal');
		} else {
			$subtotal = 0;
			$entries = $this->getAvailableEntries();
			foreach ($entries as $entry)
				$subtotal += $entry->getExtensionCost();

			$this->setCachedValue('store-subtotal', $subtotal);
		}

		return $subtotal;
	}

	// }}}
}

?>
