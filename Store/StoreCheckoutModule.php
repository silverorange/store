<?php

/**
 * Manages the checkout session and progress for a web-store application
 *
 * Depends on the SiteAccountSessionModule and SiteDatabaseModule modules.
 *
 * @package   Store
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutModule extends SiteApplicationModule
{
	// {{{ public function init()

	public function init()
	{
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The checkout module depends on the SiteAccountSessionModule and
	 * SiteDatabaseModule features.
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency(
			'SiteAccountSessionModule');

		$depends[] = new SiteApplicationModuleDependency(
			'StoreCartModule');

		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');

		return $depends;
	}

	// }}}
	// {{{ public function initDataObjects()

	/**
	 * Ensures the data-objects required by the checkout are created
	 *
	 * If the dataobjects are not created, the checkout progress is reset and
	 * the objects are created.
	 *
	 * The checkout session data-objects created by default are:
	 *  - 'order'   - a {@link StoreOrder} object, and
	 *  - 'account' - a {@link StoreAccount} object.
	 */
	public function initDataObjects()
	{
		$session = $this->getSession();

		if (!isset($session->account)) {
			unset($session->account);
			$session->account = $this->createAccount();
			$this->resetProgress();
		}

		if (!isset($session->order)) {
			unset($session->order);
			$session->order = $this->createOrder();
			$this->resetProgress();
		}
	}

	// }}}
	// {{{ public function setProgress()

	/**
	 * Sets a progress dependency as 'met'
	 *
	 * @param string $dependency the progress dependency that is now met.
	 */
	public function setProgress($dependency)
	{
		$session = $this->getSession();

		// if the progress session variable somehow got removed, recreate it
		if (!isset($session->checkout_progress)) {
			$session->checkout_progress = new ArrayObject();
		}

		// add the met dependency
		$session->checkout_progress[] = $dependency;
	}

	// }}}
	// {{{ public function unsetProgress()

	/**
	 * Sets a progress dependency as 'unmet'
	 *
	 * @param string $dependency the progress dependency that is now unmet.
	 */
	public function unsetProgress($dependency)
	{
		$session = $this->getSession();

		// if the progress session variable somehow got removed, recreate it
		if (!isset($session->checkout_progress)) {
			$session->checkout_progress = new ArrayObject();
		}

		// remove the met dependency
		$progress_array = $session->checkout_progress->getArrayCopy();
		$progress_array = array_diff($progress_array, array($dependency));
		$session->checkout_progress = new ArrayObject($progress_array);
	}

	// }}}
	// {{{ public function hasProgressDependency()

	/**
	 * Gets whether or not the checkout progress includes the specified
	 * dependency
	 *
	 * This can be used to ensure the customer has entered a shipping address
	 * before proceeding to the shipping page, for example.
	 *
	 * @param string $dependency the dependency to check.
	 *
	 * @return boolean true if the checkout progress includes the specified
	 *                 dependency, false if not.
	 */
	public function hasProgressDependency($dependency)
	{
		$has_dependency = false;
		$session = $this->getSession();
		
		if (isset($session->checkout_progress)) {
			$has_dependency = in_array(
				$dependency,
				$session->checkout_progress->getArrayCopy()
			);
		}

		return $has_dependency;
	}

	// }}}
	// {{{ public function resetProgress()

	/**
	 * Resets checkout progress
	 *
	 * After calling this method, the customer is required to enter the
	 * checkout again from a page with no checkout dependencies.
	 *
	 * This usually means they have to begin the full checkout again.
	 */
	public function resetProgress()
	{
		$session = $this->getSession();

		// ArrayObject is used because magic get method can not return by
		// reference since PHP 5.2 and we want the array to be returned by
		// reference from the session.
		$session->checkout_progress = new ArrayObject();
		$session->checkout_email    = null;
	}

	// }}}
	// {{{ public function buildOrder()

	public function buildOrder(StoreOrder $order)
	{
		$cart = $this->app->getModule('StoreCartModule');
		$cart = $cart->checkout;

		$this->createOrderItems($order);

		$order->locale = $this->app->getLocale();

		$order->item_total = $cart->getItemTotal();

		$order->surcharge_total = $cart->getSurchargeTotal(
			$order->payment_methods
		);

		$order->shipping_total = $cart->getShippingTotal(
			$order->billing_address,
			$order->shipping_address,
			$order->shipping_type
		);

		$order->tax_total = $cart->getTaxTotal(
			$order->billing_address,
			$order->shipping_address,
			$order->shipping_type,
			$order->payment_methods
		);

		$order->total = $cart->getTotal(
			$order->billing_address,
			$order->shipping_address,
			$order->shipping_type,
			$order->payment_methods
		);

		// Reload ad from the database to esure it exists before trying to
		// build the order. This prevents order failure when a deleted or
		// disabled ad ends up in the session.
		if ($this->app->hasModule('SiteAdModule')) {
			$ad_module = $this->app->getModule('SiteAdModule');
			$session_ad = $ad_module->getAd();
			if ($session_ad !== null) {
				$ad_class = SwatDBClassMap::get('SiteAd');
				$ad = new $ad_class();
				$ad->setDatabase($this->app->db);
				if ($ad->load($session_ad->id)) {
					$order->ad = $ad;
				}
			}
		}

		return $order;
	}

	// }}}
	// {{{ protected function createOrderItems()

	protected function createOrderItems($order)
	{
		$region = $this->app->getRegion();

		$wrapper = SwatDBClassMap::get('StoreOrderItemWrapper');
		$order->items = new $wrapper();

		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			$order_item = $entry->createOrderItem();
			$order_item->setDatabase($this->app->db);
			$order_item->setAvailableItemCache($region, $entry->item);
			$order_item->setItemCache($entry->item);
			$order->items->add($order_item);
		}
	}

	// }}}
	// {{{ protected function getSession()

	/**
	 * Gets the session module of this module's application
	 *
	 * @return SiteSessionModule the session module of this module's
	 *                           application.
	 */
	protected function getSession()
	{
		return $this->app->getModule('SiteAccountSessionModule');
	}

	// }}}
	// {{{ protected function getDB()

	/**
	 * Gets the database connection of this module's application's database
	 * module
	 *
	 * @return MDB2_Driver_Common the database connection of the application.
	 */
	protected function getDB()
	{
		$module = $this->app->getModule('SiteDatabaseModule');
		return $module->getConnection();
	}

	// }}}
	// {{{ protected function createAccount()

	protected function createAccount()
	{
		$account_class = SwatDBClassMap::get('StoreAccount');
		$account = new $account_class();
		$account->setDatabase($this->getDB());

		return $account;
	}

	// }}}
	// {{{ protected function createOrder()

	protected function createOrder()
	{
		$order_class = SwatDBClassMap::get('StoreOrder');
		$order = new $order_class();
		$order->setDatabase($this->getDB());

		return $order;
	}

	// }}}
}

?>
