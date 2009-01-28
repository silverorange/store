<?php

require_once 'Store/pages/StoreCheckoutStepPage.php';

/**
 * Base class for a step page of checkout that is composed of other pages.
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutAggregateStepPage extends StoreCheckoutStepPage
{
	// {{{ private properties

	private $embedded_edit_pages = array();

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);

		foreach ($this->instantiateEmbeddedEditPages() as $page)
			$this->registerEmbeddedEditPage($page);

	}

	// }}}
	// {{{ public function registerEmbeddedEditPage()

	public function registerEmbeddedEditPage(SiteAbstractPage $page)
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
	// {{{ abstract protected function instantiateEmbeddedEditPages()

	abstract protected function instantiateEmbeddedEditPages();

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
	// {{{ protected function loadUI()

	protected function loadUI()
	{
		parent::loadUI();

		$pages = $this->getEmbeddedEditPages();
		foreach ($pages as $page) {
			$container = $this->getContainer($page);
			$this->ui->loadFromXML($page->getUiXml(), $container);
		}
	}

	// }}}
	// {{{ protected function getContainer()

	protected function getContainer($page)
	{
		$class = get_class($page);

		$matches = array();
		if (preg_match('/Checkout(.*)Page$/', $class, $matches) === 1) {
			$container_id = strtolower(
				preg_replace('/([A-Z])/u', '_\\1', $matches[1])).'_container';

			$container_id = substr($container_id, 1);
		} else {
			throw new StoreException(
				"Unable to guess container for page {$class}");
		}

		$container = $this->ui->getWidget($container_id);
		return $container;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		$form = $this->ui->getWidget('form');

		if ($form->isSubmitted()) {
			foreach ($this->embedded_edit_pages as $page)
				$page->preProcessCommon();
		}

		parent::process();

		if ($form->isProcessed()) {
			foreach ($this->embedded_edit_pages as $page)
				$page->validateCommon();

			if (!$form->hasMessage()) {
				foreach ($this->embedded_edit_pages as $page)
					$page->processCommon();
			}
		}

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
