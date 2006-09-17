<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Confirmation page of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutThankYouPage extends StoreCheckoutPage
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->resetProgress();
	}

	// }}}
	// {{{ protected function checkCart()

	protected function checkCart()
	{
		// do nothing - cart should be empty now
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout/confirmation');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildInternal();

		$this->layout->startCapture('content');
		$this->display();
		$this->layout->endCapture();

		// clear the order and logout
		unset($this->app->session->order);
		$this->app->session->logout();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-thank-you-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
	}

	// }}}
	// {{{ abstract protected function display()

	abstract protected function display();

	// }}}
}

?>
