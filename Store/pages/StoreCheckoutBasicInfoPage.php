<?php

require_once 'Store/pages/StoreCheckoutEditPage.php';

/**
 * Basic information edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutBasicInfoPage extends StoreCheckoutEditPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/pages/checkout-basic-info.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		$confirm_email = $this->ui->getWidget('confirm_email');
		$confirm_email->email_widget = $this->ui->getWidget('email');

		if ($this->app->session->isLoggedIn()) {
			$password_field = $this->ui->getWidget('password_field');
			$password_field->visible = false;
		} else {
			$fullname_field = $this->ui->getWidget('fullname_field');
			$fullname_field->visible = false;
		}

		$account = $this->app->session->account;
		if ($account instanceof SiteAccount && $account->password !== null)  {
			$this->ui->getWidget('password')->required = false;

			$this->ui->getWidget('password_field')->note = Store::_(
				'Leave the password field blank to leave the password '.
				'unchanged. '
				).$this->ui->getWidget('password_field')->note;
		}
	}

	// }}}

	//process phase
	// {{{ public function validateCommon()

	public function validateCommon()
	{
		if (($this->app->session->account->password != '') ||
			($this->ui->getWidget('password')->value != '')) {

			$this->validateAccount();
		}
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
		$this->saveDataToSession();

		if ($this->app->session->isLoggedIn()) {
			$this->app->session->account->save();
		}
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$order = $this->app->session->order;

		$order->email = $this->getOptionalStringValue('email');
		$order->company = $this->getOptionalStringValue('company');
		$order->phone = $this->getOptionalStringValue('phone');

		if ($this->ui->hasWidget('comments')) {
			$order->comments = $this->getOptionalStringValue('comments');
		}

		$account = $this->app->session->account;
		$account->fullname = $this->getOptionalStringValue('fullname');
		$account->email = $order->email;
		$account->phone = $order->phone;
		$account->company = $order->company;

		// only set password on new accounts
		if (!$this->app->session->isLoggedIn()) {
			$password = $this->ui->getWidget('password')->value;

			// don't change pass if it was left blank
			if ($password != '') {
				$crypt = $this->app->getModule('SiteCryptModule');

				$account->setPasswordHash($crypt->generateHash($password));
			}
		}
	}

	// }}}
	// {{{ protected function validateAccount()

	/**
	 * Verifies entered email address is not a duplicate of an existing account
	 */
	protected function validateAccount()
	{
		$email_entry = $this->ui->getWidget('email');
		$email_address = $email_entry->value;

		if (!$this->validEmailAddress($email_address)) {
			$message = new SwatMessage(
				Store::_('An account already exists with this email address.'),
				'error'
			);

			$message->secondary_content = sprintf(
				Store::_('Please %slog in to your account%s.'),
				sprintf(
					'<a href="%s">',
					$this->getCheckoutSource()
				),
				'</a>'
			);

			$message->content_type = 'text/xml';
			$email_entry->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validEmailAddress()

	protected function validEmailAddress($email)
	{
		// don't bother checking validity of the entered email if it is the same
		// as the account email. Allows fringe case where two accounts have the
		// same email (in theory this isn't possible).
		if ($this->app->session->isLoggedIn() &&
			$email == $this->app->session->account->email)
			return true;

		$class_name = SwatDBClassMap::get('StoreAccount');
		$account = new $class_name();
		$account->setDatabase($this->app->db);
		$found = $account->loadWithEmail($email, $this->app->getInstance());

		$account_id = ($this->app->session->isLoggedIn()) ?
			$this->app->session->account->id : null;

		if ($found && $account_id !== $account->id)
			return false;
		else
			return true;
	}

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		if (!$this->ui->getWidget('form')->isProcessed()) {
			$this->loadDataFromSession();
		}
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;
		$account = $this->app->session->account;
		$email = $this->app->session->checkout_email;

		$this->ui->getWidget('fullname')->value = $account->fullname;
		$this->ui->getWidget('phone')->value = $account->phone;
		$this->ui->getWidget('company')->value = $account->company;

		if ($account->email != '') {
			$this->ui->getWidget('email')->value = $account->email;
			$this->ui->getWidget('confirm_email')->value = $account->email;
		} else if ($order->email != '') {
			$this->ui->getWidget('email')->value = $order->email;
			$this->ui->getWidget('confirm_email')->value = $order->email;
		} else if ($email != '') {
			$this->ui->getWidget('email')->value = $email;
		}

		if ($order->company != '') {
			$this->ui->getWidget('company')->value = $order->company;
		}

		if ($order->phone != '') {
			$this->ui->getWidget('phone')->value = $order->phone;
		}

		if ($this->ui->hasWidget('comments')) {
			$this->ui->getWidget('comments')->value = $order->comments;
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-basic-info-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
