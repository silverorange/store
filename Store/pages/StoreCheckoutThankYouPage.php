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
	// {{{ protected function displayFinalNote()

	protected function displayFinalNote()
	{
		$header_tag = new SwatHtmlTag('h3');
		$header_tag->setContent(Store::_('Your Order has been Placed'));
		$paragraph_tag = new SwatHtmlTag('p');
		$paragraph_tag->setContent(Store::_(
			'Thank you for your order. You will receive an email '.
			'confirmation which will include the following detailed '.
			'order receipt for your records. If you wish, you can print '.
			'a copy of this page for reference.'));

		$header_tag->display();
		$paragraph_tag->display();
	}

	// }}}
}

?>
