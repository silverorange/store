<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Manages the checkout session and progress for a web-store application
 *
 * Depends on the SiteAccountSessionModule and SiteDatabaseModule modules.
 *
 * @package   Store
 * @copyright 2009 silverorange
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
			$account_class = SwatDBClassMap::get('StoreAccount');
			$session->account = new $account_class();
			$session->account->setDatabase($this->getDB());
			$this->resetProgress();
		}

		if (!isset($session->order)) {
			unset($session->order);
			$order_class = SwatDBClassMap::get('StoreOrder');
			$session->order = new $order_class();
			$session->order->setDatabase($this->getDB());
			$this->resetProgress();
		}
	}

	// }}}
	// {{{ public function addProgress()

	/**
	 * Sets a progress dependency as 'met'
	 *
	 * @param string $dependency the progress dependency that is now met.
	 */
	public function addProgress($dependency)
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
		$progress = $this->getSession()->checkout_progress;
		return (in_array($dependency, $progress->getArrayCopy()));
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
		$session->checkout_progress     = new ArrayObject();
		$session->checkout_with_account = false;
		$session->checkout_email        = null;
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
}

?>
