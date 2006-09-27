<?php

require_once 'Store/pages/StoreCheckoutUIPage.php';

/**
 * Base class for edit pages in the checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutEditPage extends StoreCheckoutUIPage
{
	// {{{ protected function getOptionalStringValue()

	protected function getOptionalStringValue($id)
	{
		$widget = $this->ui->getWidget($id);
		$value = trim($widget->value);

		if (strlen($value) === 0)
			$value = null;

		return $value;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initCommon();
	}

	// }}}
	// {{{ protected function loadUI()

	protected function loadUI()
	{
		$page_xml = $this->ui_xml;
		$this->ui_xml = dirname(__FILE__).'/checkout-edit.xml';
		parent::loadUI();
		$this->ui_xml = $page_xml;

		$container = $this->ui->getWidget('container');
		$this->ui->loadFromXML($this->ui_xml, $container);
	}

	// }}}
	// {{{ public function initCommon()

	public function initCommon()
	{
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout/first');
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('form');

		if ($form->isSubmitted())
			$this->preProcessCommon();

		$this->ui->process();

		if ($form->isProcessed()) {
			$this->processCommon();

			if (!$form->hasMessage()) {
				$this->updateProgress();
				$this->app->relocate('checkout/confirmation');
			}
		}
	}

	// }}}
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
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
	// {{{ public function buildCommon()

	public function buildCommon()
	{
	}

	// }}}
	// {{{ public function postBuildCommon()

	public function postBuildCommon()
	{
	}

	// }}}
}

?>
