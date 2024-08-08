<?php

/**
 * Page displayed when automatic payment on the checkout fails
 *
 * @package   Store
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutPaymentFailedPage extends StoreCheckoutFinalPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/checkout-payment-failed.xml';
	}

	// }}}
	// {{{ protected function buildOrderHeader()

	protected function buildOrderHeader(StoreOrder $order)
	{
		// don't display order receipt header
	}

	// }}}
	// {{{ protected function displayFinalNote()

	protected function displayFinalNote(StoreOrder $order)
	{
		$header_tag = new SwatHtmlTag('h3');
		$header_tag->setContent(Store::_(
			'There was a problem paying for your order.'));

		$paragraph_tag = new SwatHtmlTag('p');
		$paragraph_tag->setContent(sprintf(Store::_(
			'Everything appears to be in order but we were unable to '.
			'process your payment. No funds were removed from your card. '.
			'%sContact us%s to complete your order. %sYour order reference '.
			'number is %s%s.'),
			'<a href="'.$this->getContactSource().'">', '</a>',
			'<strong>', $order->id, '</strong>'),
			'text/xml');

		$header_tag->display();
		$paragraph_tag->display();
	}

	// }}}
}

?>
