<?php

require_once 'Store/pages/StoreAccountPage.php';
require_once 'Store/StoreUI.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAccountChangePasswordPage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-change-password.xml';

	// }}}
	// {{{ private properties

	private $ui;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$confirm = $this->ui->getWidget('confirm_password');
		$confirm->password_widget = $this->ui->getWidget('password');

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage())
				$this->validate();

			if (!$form->hasMessage()) {
				$password = $this->ui->getWidget('password')->value;
				$this->app->session->account->password = md5($password);
				$this->app->session->account->save();

				$message = new SwatMessage('Account password has been updated.',
					SwatMessage::NOTIFICATION);
				$this->app->messages->add($message);

				$this->app->relocate('account');
			}
		}
	}

	// }}}
	// {{{ private function validate()

	private function validate()
	{
		$old_password = $this->ui->getWidget('old_password');
		$value = md5($old_password->value);

		if ($value != $this->app->session->account->password) {
			$message = new SwatMessage('Your password is incorrect.',
				SwatMessage::ERROR);

			$message->content_type = 'text/xml';
			$old_password->addMessage($message);
		}
		
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->navbar->createEntry('New Password');
		$this->layout->data->title = 'Choose a New Password';

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
}

?>
