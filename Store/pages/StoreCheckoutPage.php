<?php

require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Base class for checkout pages
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutPage extends StoreArticlePage
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// relocate to cart if no session
		if (!$this->app->session->isActive())
			$this->app->relocate('cart');

		$this->checkCart();
		$this->app->session->activate();

		// initialize session variable to track checkout progress
		if (!isset($this->app->session->checkout_progress))
			$this->resetProgress();

		if ($this->app->session->isLoggedIn())
			$this->app->session->checkout_with_account = true;

		$this->initDataObjects();

		// enforce dependencies for progressing through the checkout
		foreach ($this->getProgressDependencies() as $dependency)
			if (!in_array($dependency, $this->app->session->checkout_progress))
				$this->app->relocate($dependency);
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array();
	}

	// }}}
	// {{{ protected function initDataObjects()

	protected function initDataObjects()
	{
		if (!isset($this->app->session->account) ||
			$this->app->session->account === null) {
				$class_map = StoreClassMap::instance();
				$account_class = $class_map->resolveClass('StoreAccount');
				$this->app->session->account = new $account_class();
				$this->app->session->account->setDatabase($this->app->db);
				$this->resetProgress();
		}

		if (!isset($this->app->session->order) ||
			$this->app->session->order === null ||
			$this->app->session->order->id !== null) {
				unset($this->app->session->order);
				$class_map = StoreClassMap::instance();
				$order_class = $class_map->resolveClass('StoreOrder');
				$this->app->session->order = new $order_class();
				$this->app->session->order->setDatabase($this->app->db);
				$this->resetProgress();
		}
	}

	// }}}
	// {{{ protected function checkCart()

	protected function checkCart()
	{
		// relocate to cart if no items in the cart
		if (count($this->app->cart->checkout->getAvailableEntries()) <= 0)
			$this->app->relocate('cart');
	}

	// }}}

	// process phase
	// {{{ protected function updateProgress()

	protected function updateProgress()
	{
		if (!isset($this->app->session->checkout_progress))
			$this->app->session->checkout_progress = new ArrayObject();

		$this->app->session->checkout_progress[] = $this->source;
	}

	// }}}
	// {{{ protected function resetProgress()

	protected function resetProgress()
	{
		$this->app->session->checkout_progress = array();
		$this->app->session->checkout_with_account = false;
	}

	// }}}
}

?>
