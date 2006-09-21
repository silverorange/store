<?php

require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/StoreUI.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAccountResetPasswordThankyouPage extends StoreArticlePage
{
	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();


		$this->layout->startCapture('content');

		echo '<div class="notification">';
		echo '<h4>Your Password has been Reset</h4>';
		echo '<p>Your password has been reset and you are now logged-in.</p>';
		echo '<ul class="redirect-links">';

		if ($this->app->cart->checkout->getEntryCount() != 0)
			echo '<li><a href="checkout">Proceed to Checkout</a></li>';

		echo '<li><a href="account">View your Account</a></li>';

		echo '</ul>';
		echo '</div>';

		$this->layout->endCapture();
	}

	// }}}
}

?>
