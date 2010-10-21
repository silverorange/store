<?php

require_once 'Store/pages/StoreCheckoutFinalPage.php';
require_once 'Store/StoreAdWordsTracker.php';

/**
 * Page displayed when an order is processed successfully on the checkout
 *
 * @package   Store
 * @copyright 2006-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutThankYouPage extends StoreCheckoutFinalPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-thank-you.xml';
	}

	// }}}
	// {{{ protected function displayFinalNote()

	protected function displayFinalNote(StoreOrder $order)
	{
		echo '<div id="checkout_thank_you">';
		$header_tag = new SwatHtmlTag('h3');
		$header_tag->setContent(Store::_('Your order has been placed.'));
		$paragraph_tag = new SwatHtmlTag('p');
		$paragraph_tag->setContent(Store::_(
			'Thank you for your order. You will receive an email '.
			'confirmation which will include the following detailed '.
			'order receipt for your records. If you wish, you can print '.
			'a copy of this page for reference.'));

		$header_tag->display();
		$paragraph_tag->display();
		echo "</div>";
	}

	// }}}
	// {{{ protected function buildConversionTracking()

	protected function buildConversionTracking(StoreOrder $order)
	{
		if ($this->app->config->adwords->conversion_id !== null) {
			$footer = $this->ui->getWidget('footer');
			if ($footer instanceof SwatContentBlock) {
				$tracker = new StoreAdWordsTracker($order,
					$this->app->config->adwords->conversion_id);

				$footer->content.= $tracker->getInlineXHtml();
			} else {
				// log an exception (but don't exit), so that we know ad
				// conversion tracking isn't working correctly.
				$e = new SiteException('Ad conversion tracking not working '.
					'as footer content block not found.');

				$e->process(false);
			}
		}
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		if ($this->ui->hasWidget('checkout_progress')) {
			$checkout_progress = $this->ui->getWidget('checkout_progress');
			$checkout_progress->current_step = 3;
		}

		if (property_exists($this->layout, 'analytics_tracked_order')) {
			$this->layout->analytics_tracked_order = $this->getOrder();
		}
	}

	// }}}
}

?>
