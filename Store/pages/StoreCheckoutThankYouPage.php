<?php

require_once 'Store/pages/StoreCheckoutFinalPage.php';

/**
 * Page displayed when an order is processed successfully on the checkout
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutThankYouPage extends StoreCheckoutFinalPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->ui_xml = 'Store/pages/checkout-thank-you.xml';
	}

	// }}}
}

?>
