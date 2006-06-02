<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Base class for a step page of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutStepPage extends StoreCheckoutPage
{
	// {{{ private properties

	private $embedded_edit_page_classes = array();

	// }}}
	// {{{ public function registerEmbeddedEditPage()

	public function registerEmbeddedEditPage($class)
	{
		$this->embedded_edit_page_classes[] = $class;
	}

	// }}}

	// init phase
	// {{{ protected function initCheckoutFormUI()

	protected function initCheckoutFormUI()
	{
		foreach ($this->embedded_edit_page_classes as $class)
			call_user_func(array($class, 'initCommon'),	$this->app, $this->ui);

		parent::initCheckoutFormUI();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		foreach ($this->embedded_edit_page_classes as $class)
			call_user_func(array($class, 'preProcessCommon'),
				$this->app, $this->ui);

		$this->ui->process();

		foreach ($this->embedded_edit_page_classes as $class)
			call_user_func(array($class, 'processCommon'),
				$this->app, $this->ui);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		foreach ($this->embedded_edit_page_classes as $class)
			call_user_func(array($class, 'buildCommon'),
				$this->app, $this->ui);

		parent::build();
	}

	// }}}
}

?>
