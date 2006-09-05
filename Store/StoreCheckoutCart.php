<?php

require_once 'Store/StoreCart.php';
require_once 'Store/dataobjects/StoreAddress.php';

/**
 * A checkout cart object
 *
 * The checkout cart is a cart object that is intended for purchase. Checkout
 * carts have price totalling methods and methods to get available and
 * unavailable entries. This class contains checkout cart functionality common
 * to all sites. It is intended to be extended on a per-site basis.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
	 * Loads this cart
	 */
	public function load()
	{
		$this->entries = array();

		if ($this->app->session->isLoggedIn()) {
			$account_id = $this->app->session->getAccountId();
			foreach ($this->module->getEntries() as $entry) {
				if ($entry->getInternalValue('account') == $account_id &&
					!$entry->saved) {

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

	// }}}
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
		return $this->getEntries();
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
	// {{{ protected function preSaveEntry()

	/**
	 * Sets the saved flag to false on entries in this cart that are about to
	 * be saved
	 *
	 * @param StoreCartEntry $entry the entry to process.
	 */
	protected function preSaveEntry(StoreCartEntry $entry)
	{
		$entry->saved = false;
	}

	// }}}
	// {{{ protected function restoreAbandonedCartEntries()

	/**
	 * Checks for a persistant saved session cart and updates the cart entry's
	 * session identifiers to match the current session before this cart is
	 * loaded
	 *
	 * This method makes carrying over session cart content work.
	 */
	protected function restoreAbandonedCartEntries()
	{
		// don't try to restore the cart entries if we don't have a cookie
		// module
		if (!isset($this->app->cookie))
			return;

		try {
			if (isset($this->app->cookie->cart_session)) {
				if (!$this->app->session->isActive())
					$this->app->session->activate();

				$previous_session = $this->app->cookie->cart_session;
				$current_session = $this->app->session->getSessionID();

				if ($previous_session !== $current_session) {
					$sql = 'update CartEntry set sessionid = %s
						where sessionid = %s';

					$sql = sprintf($sql,
						$this->app->db->quote($current_session, 'text'),
						$this->app->db->quote($previous_session, 'text'));
					
					SwatDB::exec($this->app->db, $sql);
				}
			}
		} catch (SiteCookieException $e) {
			// silently handle bad cookie exception
		}

		if ($this->app->session->isActive())
			$this->app->cookie->setCookie('cart_session',
				$this->app->session->getSessionID());
	}

	// }}}

	// price calculation methods
	// {{{ public function getTotal()

	/**
	 * Gets the total cost for an order of the contents of this cart
	 *
	 * By default, the total is calculated as item total + tax + shipping.
	 * Subclasses may override this to calculate totals differently.
	 *
	 * @param StoreAddress $billing_address the billing address of the order.
	 * @param StoreAddress $shipping_address the shipping address of the order.
	 *
	 * @return double the cost of this cart's contents.
	 */
	public function getTotal(StoreAddress $billing_address,
		StoreAddress $shipping_address)
	{
		if ($this->cachedValueExists('store-total')) {
			$total = $this->getCachedValue('store-total');
		} else {
			$total = 0;
			$total += $this->getItemTotal();

			$total += $this->getTaxTotal(
				$billing_address, $shipping_address);

			$total += $this->getShippingTotal(
				$shipping_address, $shipping_address);

			$this->setCachedValue('store-total', $total);
		}

		return $total;
	}

	// }}}
	// {{{ public function getItemTotal()

	/**
	 * Gets the cost of the StoreCartEntry objects in this cart
	 *
	 * This is sometimes called the subtotal.
	 *
	 * @return double the sum of the extensions of all StoreCartEntry objects
	 *                 in this cart.
	 */
	public function getItemTotal()
	{
		if ($this->cachedValueExists('store-item-total')) {
			$total = $this->getCachedValue('store-item-total');
		} else {
			$total = 0;
			$entries = $this->getAvailableEntries();
			foreach ($entries as $entry)
				$total += $entry->getExtension();

			$this->setCachedValue('store-item-total', $total);
		}

		return $total;
	}

	// }}}
	// {{{ abstract public function getShippingTotal()

	/**
	 * Gets the cost of shipping the contents of this cart
	 *
	 * @param StoreAddress $billing_address the billing address of the order.
	 * @param StoreAddress $shipping_address the shipping address of the order.
	 *
	 * @return double the cost of shipping this order.
	 */
	abstract public function getShippingTotal(StoreAddress $billing_address,
		StoreAddress $shipping_address);

	// }}}
	// {{{ abstract public function getTaxTotal()

	/**
	 * Gets the total amount of taxes for this cart
	 *
	 * Calculates applicable taxes based on the contents of this cart. Tax
	 * Calculations need to know where purchase is made in order to correctly
	 * apply tax.
	 *
	 * @param StoreAddress $billing_address the billing address of the order.
	 * @param StoreAddress $shipping_address the shipping address of the order.
	 *
	 * @return double the tax charged for the contents of this cart.
	 */
	abstract public function getTaxTotal(StoreAddress $billing_address,
		StoreAddress $shipping_address);
	
	// }}}
	// {{{ abstract public function getTaxProvState()

	abstract public function getTaxProvState(
		StoreAddress $billing_address, StoreAddress $shipping_address);

	// }}}
}

?>
