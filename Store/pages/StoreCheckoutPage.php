<?php

require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/StoreUI.php';

/**
 * Base class for checkout pages
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutPage extends StoreArticlePage
{
	// init phase
	// {{{ protected properties

	protected $ui = null;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// relocate to cart if no session
		if (!$this->app->session->isActive())
			$this->app->relocate('cart');

		// initialize session variable to track checkout progress
		if (!$this->app->session->isDefined('checkout_progress'))
			$this->resetProgress();

		// enforce dependencies for progressing through the checkout
		foreach ($this->getProgressDependencies() as $dependency)
			if (!in_array($dependency, $this->app->session->checkout_progress))
				$this->app->relocate($dependency);
	}

	// }}}
	// {{{ protected function loadCheckoutFormUI()

	protected function loadCheckoutFormUI()
	{
		$this->ui = new StoreUI();
		$this->ui->loadFromXML('Store/pages/checkout.xml');
	}

	// }}}
	// {{{ protected function initCheckoutFormUI()

	protected function initCheckoutFormUI()
	{
		$form = $this->ui->getWidget('form');
		$form->action = $this->source;
		$this->ui->init();
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array();
	}

	// }}}

	// process phase
	// {{{ protected function updateProgress()

	protected function updateProgress()
	{
		if (!$this->app->session->isDefined('checkout_progress'))
			$this->app->session->checkout_progress = array();

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

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if ($this->ui !== null) {
			$this->layout->addHtmlHeadEntrySet(
				$this->ui->getRoot()->getHtmlHeadEntries());

			$this->layout->startCapture('content');
			$this->ui->display();
			$this->layout->endCapture();
		}
	}

	// }}}
}

?>
