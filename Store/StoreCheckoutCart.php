<?php

require_once 'Store/StoreCart.php';

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
		$this->findPersistentCartEntries();
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
				}
			}
		} else {
			foreach ($this->module->getEntries() as $entry) {
				if (session_id() == $entry->sessionid && !$entry->saved)
					$this->entries[] = $entry;
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
	// {{{ protected function findPersistentCartEntries()

	/**
	 * Checks for a persistant saved session cart and updates the cart entry's
	 * session identifiers to match the current session before this cart is
	 * loaded
	 *
	 * This method makes carrying over session cart content work.
	 */
	protected function findPersistentCartEntries()
	{
		$persistent_cart_cookie = $this->app->id.'_previous_session';

		if (isset($_COOKIE[$persistent_cart_cookie])) {
			if (!$this->app->session->isActive())
				$this->app->session->activate();

			$previous_session = $_COOKIE[$persistent_cart_cookie];
			$current_session = $this->app->session->getSessionID();

			if ($previous_session != $current_session) {
				$sql = 'update CartEntry set sessionid = %s
					where sessionid = %s';

				$sql = sprintf($sql,
					$this->app->db->quote($current_session, 'text'),
					$this->app->db->quote($previous_session, 'text'));
				
				SwatDB::exec($this->app->db, $sql);
			}
		}

		if ($this->app->session->isActive()) {
			$expiry = strtotime('+90 days');
			// TODO: get domain from application
			// setcookie($persistent_cart_cookie,
			//	$this->app->session->getSessionID(), $expiry, '/', $domain);
			setcookie($persistent_cart_cookie,
				$this->app->session->getSessionID(), $expiry, '/');
		}
	}

	// }}}

	// price calculation methods
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
}

?>
