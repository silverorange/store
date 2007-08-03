<?php

require_once 'Store/pages/StoreCheckoutUIPage.php';

/**
 * Base class for a step page of checkout
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
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
		foreach ($this->embedded_edit_pages as $page)
			$page->buildCommon();

		parent::build();

		foreach ($this->embedded_edit_pages as $page)
			$page->postBuildCommon();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		foreach ($this->embedded_edit_pages as $page)
			$page->finalize();
	}

	// }}}
}

?>
