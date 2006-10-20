<?php

require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/StoreUI.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SiteMultipartMailMessage.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreContactPage extends StoreArticlePage
{
	// {{{ protected properties

	protected $ui;

	// }}}
	// {{{ protected function getSubjects()

	protected function getSubjects()
	{
		$subjects = array(
			'general'  => Store::_('General Question'),
			'order'    => Store::_('My Order'),
			'website'  => Store::_('Website'),
			'products' => Store::_('Products'),
			'privacy'  => Store::_('Privacy'),
		);

		return $subjects;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML('Store/pages/contact.xml');

		$form = $this->ui->getWidget('contact_form');
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

		$form = $this->ui->getWidget('contact_form');

		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage()) {
				$this->sendEmail();
				$this->app->relocate($this->source.'/thankyou');
			}
		}
	}

	// }}}
	// {{{ private function sendEmail()

	private function sendEmail()
	{
		$message = new SiteMultipartMailMessage($this->app);
		$message->smtp_server = $this->app->config->email->smtp_server;
		$message->from_address = $this->app->config->email->website_address;
		$message->reply_to_address = $this->ui->getWidget('email')->value;
		$message->to_address = $this->app->config->email->contact_address;

		$subject_index = $this->ui->getWidget('subject')->value;
		$subjects = $this->getSubjects();
		$message->subject = $subjects[$subject_index];

		$message->text_body = $this->ui->getWidget('message')->value;
		$message->text_body.= $this->browserInfo();

		try {
			$message->send();
		} catch (SiteMailException $e) {
			$e->process(false);
		}
	}

	// }}}
	// {{{ private function browserInfo()

	private function browserInfo()
	{
		$info = "\n\n-------------------------\n";
		$info.= "User Information\n";

		if (isset($_SERVER['HTTP_USER_AGENT']))
			$info.= $_SERVER['HTTP_USER_AGENT'];
		else
			$info.= 'Not available';

		return $info;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-contact-page.css',
			Store::PACKAGE_ID));

		$subject_flydown = $this->ui->getWidget('subject');
		$subject_flydown->addOptionsByArray($this->getSubjects());

		$this->layout->startCapture('content', true);
		$this->ui->getWidget('contact_form')->display();
		$this->layout->endCapture();
	}

	// }}}
}

?>
