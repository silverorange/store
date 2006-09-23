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
	private $embedded_edit_pages = array();

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
		foreach ($this->embedded_edit_page_classes as $class) {
			$page = new $class($this->app, $this->layout);
			$page->setUI($this->ui);
			$this->embedded_edit_pages[] = $page;
		}

		foreach ($this->embedded_edit_pages as $page)
			$page->initCommon();

		parent::initCheckoutFormUI();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('form');

		if ($form->isSubmitted())
			foreach ($this->embedded_edit_pages as $page)
				$page->preProcessCommon();

		$this->ui->process();

		if ($form->isProcessed())
			foreach ($this->embedded_edit_pages as $page)
				$page->processCommon();
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
		foreach ($this->embedded_edit_pages as $page)
			$page->buildCommon();
	}

	// }}}
	// {{{ protected function postBuildCommon()

	protected function postBuildCommon()
	{
		foreach ($this->embedded_edit_pages as $page)
			$page->postBuildCommon();
	}

	// }}}
}

?>
