<?php

require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/pages/StoreAccountPage.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/StoreUI.php';

/**
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountEditPage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-edit.xml';

	protected $ui;

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

		$confirm_password = $this->ui->getWidget('confirm_password');
		$confirm_password->password_widget = $this->ui->getWidget('password');;

		$confirm_email = $this->ui->getWidget('confirm_email');
		$confirm_email->email_widget = $this->ui->getWidget('email');;

		$this->ui->init();
	}

	// }}}
	// {{{ private function findAccount()

	private function findAccount()
	{
		if ($this->app->session->isLoggedIn()) {
			return $this->app->session->account;
		} else  {
			$class = SwatDBClassMap::get('StoreAccount');
			return new $class();
		}
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		if ($this->app->session->isLoggedIn()) {
			$this->ui->getWidget('password')->required = false;
			$this->ui->getWidget('confirm_password')->required = false;
		}

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			$this->validateEmail();

			if (!$form->hasMessage()) {
				$account = $this->findAccount();

				$this->updateNewsletterSubscriber($account);
				$this->updateAccount($account);

				if (!$this->app->session->isLoggedIn()) {

					$account->createdate = new SwatDate();
					$account->createdate->toUTC();

					$account->setDatabase($this->app->db);
					$account->save();

					$this->app->session->loginById($account->id);

					$message = new SwatMessage(
						Store::_('New account has been created.'));

				} elseif ($this->app->session->account->isModified()) {
					$message = new SwatMessage(
						Store::_('Account details have been updated.'));

					$this->app->messages->add($message);

					$this->app->session->account->save();
				}

				$this->app->relocate('account');
			}
		}
	}

	// }}}
	// {{{ protected function updateAccount()

	protected function updateAccount(StoreAccount $account)
	{
		if (!$this->app->session->isLoggedIn()) {
			$account->setPassword(
				$this->ui->getWidget('password')->value);
		}

		$account->fullname = $this->ui->getWidget('fullname')->value;
		$account->email = $this->ui->getWidget('email')->value;
		$account->phone = $this->ui->getWidget('phone')->value;
	}

	// }}}
	// {{{ private function updateNewsletterSubscriber()

	private function updateNewsletterSubscriber(StoreAccount $account)
	{
		$new_email = $this->ui->getWidget('email')->value;

		if (!$this->app->session->isLoggedIn() ||
			$new_email === $account->email)
			return;

		$sql = 'update NewsletterSubscriber set
			email = %s where email = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($new_email, 'text'),
			$this->app->db->quote($account->email, 'text'));

		SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ private function validateEmail()

	private function validateEmail()
	{
		$email = $this->ui->getWidget('email');
		if ($email->hasMessage())
			return;

		$account_id = ($this->app->session->isLoggedIn()) ?
			$this->app->session->account->id : null;

		$query = SwatDB::query($this->app->db, sprintf('select email
			from Account where lower(email) = lower(%s) and id %s %s',
			$this->app->db->quote($email->value, 'text'),
			SwatDB::equalityOperator($account_id, true),
			$this->app->db->quote($account_id, 'integer')));

		if (count($query) > 0) {
			$email_link = sprintf('<a href="account/forgotpassword?email=%s">',
				$email->value);

			$message = new SwatMessage(
				Store::_('An account already exists with this email address.'),
				SwatMessage::ERROR);

			$message->secondary_content =
				sprintf(Store::_('You can %srequest a new password%s to log '.
					'into the existing account.'), $email_link, '</a>');

			$message->content_type = 'text/xml';
			$email->addMessage($message);
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildInternal();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		if ($this->app->session->isLoggedIn()) {
			$this->layout->navbar->createEntry(
				Store::_('Edit Account Details'));

			$this->layout->data->title = Store::_('Edit Account Details');
			$this->ui->getWidget('submit_button')->title =
				Store::_('Update Account Details');

			$this->ui->getWidget('password_container')->visible = false;
		} else {
			$this->layout->navbar->createEntry(
				Store::_('Create a New Account'));

			$this->layout->data->title = Store::_('Create a New account');
		}

		if ($this->app->session->isLoggedIn() && !$form->isProcessed()) {
			$account = $this->findAccount();
			$this->setWidgetValues($account);
		}
	}

	// }}}
	// {{{ protected function setWidgetValues()

	protected function setWidgetValues(StoreAccount $account)
	{
		$this->ui->getWidget('fullname')->value = $account->fullname;
		$this->ui->getWidget('email')->value = $account->email;
		$this->ui->getWidget('confirm_email')->value = $account->email;
		$this->ui->getWidget('phone')->value = $account->phone;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-account-edit-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
