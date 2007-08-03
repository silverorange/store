<?php

require_once 'Site/pages/SiteArticlePage.php';
require_once 'Store/StoreUI.php';

/**
 * Confirmation page for resetting an account password
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountResetPasswordThankyouPage extends SiteArticlePage
{
	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('content');

		echo '<div class="notification">',
			'<h4>', Store::_('Your Password has been Reset'), '</h4>',
			'<p>', Store::_('Your password has been reset and you are now '.
			'logged-in.'), '</p>',
			'<ul class="redirect-links">';

		if (count($this->app->cart->checkout->getAvailableEntries()) > 0)
			echo '<li><a href="checkout">', Store::_('Proceed to Checkout'),
				'</a></li>';

		echo '<li><a href="account">', Store::_('View your Account'),
			'</a></li>',
			'</ul>',
			'</div>';

		$this->layout->endCapture();
	}

	// }}}
}

?>
