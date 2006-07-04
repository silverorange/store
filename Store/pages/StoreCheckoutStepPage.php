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

		$form = $this->ui->getWidget('form');
		if ($form->isProcessed()) {
			foreach ($this->embedded_edit_page_classes as $class)
				call_user_func(array($class, 'processCommon'),
					$this->app, $this->ui);
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildCommon();
		parent::build();
		$this->postBuildCommon();
	}

	// }}}
	// {{{ protected function buildCommon()

	protected function buildCommon()
	{
		foreach ($this->embedded_edit_page_classes as $class)
			call_user_func(array($class, 'buildCommon'),
				$this->app, $this->ui);
	}

	// }}}
	// {{{ protected function postBuildCommon()

	protected function postBuildCommon()
	{
		foreach ($this->embedded_edit_page_classes as $class)
			call_user_func(array($class, 'postBuildCommon'),
				$this->app, $this->ui);
	}

	// }}}
}

?>
