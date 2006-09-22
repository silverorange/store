<?php

require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/pages/StoreAccountPage.php';
require_once 'Store/StoreUI.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Text/Password.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAccountForgotPasswordPage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-forgot-password.xml';

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

		$form = $this->ui->getWidget('password_form');
		$form->action = $this->source;

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('password_form');

		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage())
				$this->generatePassword();

			if (!$form->hasMessage())
				$this->app->relocate('account/forgotpassword/sent');
		}
	}

	// }}}
	// {{{ private function generatePassword()

	private function generatePassword()
	{
		$email = $this->ui->getWidget('email')->value;

		$sql = 'select id from Account where lower(email) = lower(%s)';

		$id = SwatDB::queryOne($this->app->db,
			sprintf($sql, $this->app->db->quote($email, 'text')));

		if ($id === null) {
			$msg = new SwatMessage(Store::_(
				'There is no Veseys.com account with this email address.'),
				SwatMessage::ERROR);

			$msg->secondary_content = sprintf(Store::_(
				'Make sure you entered it correctly, or '.
				'%screate a New Account%s.'),
				'<a href="account/edit">', '</a>');

			$msg->content_type = 'text/xml';
			$this->ui->getWidget('email')->addMessage($msg);
		} else {
			StoreAccount::generatePassword($this->app, $id,
				$this->app->getBaseHref());
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$email = $this->app->initVar('email');
		if ($email !== null)
			$this->ui->getWidget('email')->value = $email;

		$this->layout->startCapture('content', true);
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
}

?>
