<?php

require_once 'Store/pages/StoreCheckoutUIPage.php';

/**
 * Base class for edit pages in the checkout
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
abstract class StoreCheckoutEditPage extends StoreCheckoutUIPage
{
	// {{{ protected function getOptionalStringValue()

	protected function getOptionalStringValue($id)
	{
		$widget = $this->ui->getWidget($id);
		$value = trim($widget->value);

		if ($value == '')
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

		if ($this->ui->hasWidget('checkout_progress')) {
			$checkout_progress = $this->ui->getWidget('checkout_progress');
			$checkout_progress->current_step = 1;
		}
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
			$this->validateCommon();

			if (!$form->hasMessage()) {
				$this->processCommon();
				// check again here in case processing the form revealed
				// additional errors
				if (!$form->hasMessage()) {
					$this->updateProgress();
					$this->app->relocate('checkout/confirmation');
				}
			}
		}
	}

	// }}}
	// {{{ public function preProcessCommon()

	/**
	 * Sets up additional properties on this checkout edit page to allow
	 * proper processing of data
	 *
	 * This method is only called when the form on this edit page is submitted.
	 *
	 * Subclasses may connect dependent widgets and initialize additional
	 * widget processing properties by overriding and implementing this method.
	 * A subclass could, for example, set certain widgets as either required or
	 * not required in this method.
	 *
	 * By default, no additional processing setup is performed.
	 */
	public function preProcessCommon()
	{
	}

	// }}}
	// {{{ public function validateCommon()

	/**
	 * Validates the data submitted by this edit page
	 *
	 * Subclasses may add additional validation code here by overriding and
	 * implementing this method.
	 *
	 * By default, no additional validating is performed.
	 */
	public function validateCommon()
	{
	}

	// }}}
	// {{{ public function processCommon()

	/**
	 * Processes the data submitted by this checkout edit page
	 *
	 * Subclasses may add additional code here to update checkout objects by
	 * overriding and implementing this method.
	 *
	 * By default, no additional processing is performed.
	 */
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
