<?php

require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/dataobjects/StoreEmailListSubscriber.php';
require_once 'Store/StoreUI.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreEmailListSubscribePage extends StoreArticlePage
{
	// {{{ protected properties

	protected $ui;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML('Store/pages/email-list-subscribe.xml');

		$form = $this->ui->getWidget('subscribe_form');
		$form->action = $this->source;
		$form->action.= '#message_display';

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('subscribe_form');

		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage()) {
				$this->saveData();
				$this->app->relocate($this->source.'/thankyou');
			}
		}
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData()
	{
		$subscriber = new StoreEmailListSubscriber();
		$subscriber->setDatabase($this->app->db);
		$subscriber->email = $this->ui->getWidget('email')->value;
		$subscriber->locale = $this->app->getLocale();
		$subscriber->save();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->startCapture('content', true);
		$this->ui->getWidget('subscribe_form')->display();
		$this->layout->endCapture();
	}

	// }}}
}

?>
