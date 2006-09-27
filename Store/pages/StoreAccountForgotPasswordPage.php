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
		$class_mapper = StoreClassMap::instance();
		$email = $this->ui->getWidget('email')->value;

		$account_sql = sprintf('select id, email, fullname from Account
			where lower(email) = lower(%s)',
			$this->app->db->quote($email, 'text'));

		$account = SwatDB::query($this->app->db, $account_sql,
			$class_mapper->resolveClass('StoreAccountWrapper'))->getFirst();

		if ($account === null) {
			$msg = new SwatMessage(Store::_(
				'There is no account with this email address.'),
				SwatMessage::ERROR);

			$msg->secondary_content = sprintf(Store::_(
				'Make sure you entered it correctly, or '.
				'%screate a New Account%s.'),
				'<a href="account/edit">', '</a>');

			$msg->content_type = 'text/xml';
			$this->ui->getWidget('email')->addMessage($msg);
		} else {
			$account->resetPassword($this->app, $this->app->getBaseHref());
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
