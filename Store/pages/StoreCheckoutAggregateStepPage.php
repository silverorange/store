<?php

require_once 'Store/pages/StoreCheckoutStepPage.php';

/**
 * Base class for a step page of checkout that is composed of other pages.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCheckoutAggregateStepPage extends StoreCheckoutStepPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);

		foreach ($this->instantiateEmbeddedEditPages() as $page)
			$this->registerEmbeddedEditPage($page);
	}

	// }}}
	// {{{ abstract protected function instantiateEmbeddedEditPages()

	abstract protected function instantiateEmbeddedEditPages();

	// }}}

	// init phase
	// {{{ protected function loadUI()

	protected function loadUI()
	{
		parent::loadUI();

		$pages = $this->getEmbeddedEditPages();
		foreach ($pages as $page) {
			$container = $this->getContainer($page);
			$this->ui->loadFromXML($page->getXml(), $container);
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
		parent::process();

		$form = $this->ui->getWidget('form');
		if ($form->isProcessed()) {
			if ($form->hasMessage()) {
				$message = new SwatMessage(Store::_('There is a problem with '.
					'the information submitted.'), SwatMessage::ERROR);

				$message->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->ui->getWidget('message_display')->add($message);
			} else {
				$this->updateProgress();
				$this->relocate();
			}
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate('checkout/confirmation');
	}

	// }}}
}

?>
