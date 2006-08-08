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
	// {{{ protected static function getOptionalValue()

	protected static function getOptionalStringValue($ui, $id)
	{
		$widget = $ui->getWidget($id);
		$value = trim($widget->value);

		if (strlen($value) === 0)
			$value = null;

		return $value;
	}

	// }}}

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

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('form');

		if ($form->isSubmitted()) {
			/* Call using $this instead of self:: since we want run the
			 * code in the subclass.
			 */
			$this->preProcessCommon($this->app, $this->ui);
		}

		$this->ui->process();

		if ($form->isProcessed()) {
			// Call using $this instead of self:: since we want run the
			// code in the subclass.
			$this->processCommon($this->app, $this->ui);

			if (!$form->hasMessage()) {
				$this->updateProgress();
				$this->app->relocate('checkout/confirmation');
			}
		}
	}

	// }}}
	// {{{ public static function preProcessCommon()

	public static function preProcessCommon($app, $ui)
	{
	}

	// }}}
	// {{{ public static function processCommon()

	public static function processCommon($app, $ui)
	{
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		// Call using $this instead of self:: since we want run the
		// code in the subclass.
		$this->buildCommon($this->app, $this->ui);

		parent::build();

		// Call using $this instead of self:: since we want run the
		// code in the subclass.
		$this->postBuildCommon($this->app, $this->ui);
	}

	// }}}
	// {{{ public static function buildCommon()

	public static function buildCommon($app, $ui)
	{
	}

	// }}}
	// {{{ public static function postBuildCommon()

	public static function postBuildCommon($app, $ui)
	{
	}

	// }}}
}

?>
