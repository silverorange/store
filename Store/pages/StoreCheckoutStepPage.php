<?php

require_once 'Store/pages/StoreCheckoutUIPage.php';

/**
 * Base class for a step page of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutStepPage extends StoreCheckoutUIPage
{
	// {{{ private properties

	private $embedded_edit_pages = array();

	// }}}
	// {{{ public function registerEmbeddedEditPage()

	public function registerEmbeddedEditPage($page)
	{
		$this->embedded_edit_pages[] = $page;
	}

	// }}}
	// {{{ public function getEmbeddedEditPages()

	public function getEmbeddedEditPages()
	{
		return $this->embedded_edit_pages;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		foreach ($this->embedded_edit_pages as $page)
			$page->setUI($this->ui);

		foreach ($this->embedded_edit_pages as $page)
			$page->initCommon();
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
