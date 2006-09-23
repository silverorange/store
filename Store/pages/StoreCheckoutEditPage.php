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
	// {{{ protected properties

	protected $ui_xml = null;

	// }}}
	// {{{ public function setUI()

	public function setUI($ui = null)
	{
		$this->ui = $ui;
	}

	// }}}
	// {{{ public function getXml()

	public function getXml()
	{
		return $this->ui_xml;
	}

	// }}}
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
	// {{{ protected function initCheckoutFormUI()

	protected function initCheckoutFormUI()
	{
		$this->initCommon();
		parent::initCheckoutFormUI();
	}

	// }}}
	// {{{ public function initCommon()

	public function initCommon()
	{
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
