<?php

require_once 'Site/pages/SiteAccountLoginPage.php';

/**
 * @package   Store
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountLoginPage extends SiteAccountLoginPage
{
	// {{{ protected function postLoginProcess()

	protected function postLoginProcess()
	{
		parent::postLoginProcess();

		// Save cart on login before redirecting. If your cart is updated
		// by the account login process this ensures it is saved.
		if ($this->app->hasModule('StoreCartModule')) {
			$cart = $this->app->getModule('StoreCartModule');
			$cart->save();
		}
	}

	// }}}
}

?>
