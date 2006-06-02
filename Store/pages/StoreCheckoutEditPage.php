<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Base class for edit pages in the checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutEditPage extends StoreCheckoutPage
{
	// init phase
	// {{{ protected function initCheckoutFormUI()

	protected function initCheckoutFormUI()
	{
		// Call using $this instead of self:: since we want run the
		// code in the subclass.
		$this->initCommon($this->app, $this->ui);
		parent::initCheckoutFormUI();
	}

	// }}}
	// {{{ public static function initCommon()

	public static function initCommon($app, $ui)
	{
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		// Call using $this instead of self:: since we want run the
		// code in the subclass.
		$this->buildCommon($this->app, $this->ui);
	}

	// }}}
	// {{{ public static function buildCommon()

	public static function buildCommon($app, $ui)
	{
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->ui->process();

		// Call using $this instead of self:: since we want run the
		// code in the subclass.
		$this->processCommon($this->app, $this->ui);

		$form = $this->ui->getWidget('form');
		if ($form->isProcessed()) {
			if (!$form->hasMessage()) {
				$this->updateProgress();
				$this->app->relocate('checkout/confirmation');
			}
		}
	}

	// }}}
	// {{{ public static function processCommon()

	public static function processCommon($app, $ui)
	{
	}

	// }}}
}

?>
