<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Base class for edit pages in the checkout
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutEditPage extends StoreCheckoutPage
{
	// {{{ protected properties

	protected $is_embedded = true;

	// }}}
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
		$this->is_embedded = false;
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
		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->getBaseUiXml());

		$form = $this->ui->getWidget('form');
		$this->ui->loadFromXML('Store/pages/checkout-edit.xml', $form);

		$container = $this->ui->getWidget('container');
		$this->ui->loadFromXML($this->getUiXml(), $container);
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
		$form = $this->ui->getWidget('form');
		if ($form->isSubmitted())
			$this->preProcessCommon();

		parent::process();
		$this->processInternal();

		if ($form->isProcessed()) {
			$this->validateCommon();

			if (!$form->hasMessage()) {

				try {
					$this->processCommon();
				} catch (Exception $e) {
					if ($this->handleExceptionCommon($e)) {
						// log the exception
						if (!($e instanceof SwatException)) {
							$e = new SwatException($e);
						}
						$e->process(false);
					} else {
						// exception was not handled, rethrow
						throw $e;
					}
				}

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
	// {{{ public function handleExceptionCommon()

	/**
	 * By default, exceptions are thrown in Store.
	 *
	 * @param Exception $e
	 *
	 * @return boolean true if the exception was handled and false if it was
	 *                 not. Unhandled excepions are rethrown.
	 */
	public function handleExceptionCommon(Exception $e)
	{
		return false;
	}

	// }}}
	// {{{ protected function processInternal()

	/**
	 * Processes the data submitted by this checkout edit page
	 *
	 * Subclasses may add additional code here to update checkout objects by
	 * overriding and implementing this method.
	 *
	 * By default, no additional processing is performed.
	 */
	protected function processInternal()
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
