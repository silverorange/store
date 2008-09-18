<?php

require_once 'Store/pages/StoreCheckoutEditPage.php';

/**
 * Basic information edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
class StoreCheckoutBasicInfoPage extends StoreCheckoutEditPage
{
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);
		$this->ui_xml = 'Store/pages/checkout-basic-info.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		$confirm_password = $this->ui->getWidget('confirm_password');
		$confirm_password->password_widget = $this->ui->getWidget('password');

		$confirm_email = $this->ui->getWidget('confirm_email');
		$confirm_email->email_widget = $this->ui->getWidget('email');
	}

	// }}}

	//process phase
	// {{{ public function validateCommon()

	public function validateCommon()
	{
		if ($this->app->session->checkout_with_account)
			$this->validateAccount();
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
		$this->saveDataToSession();

		if ($this->app->session->checkout_with_account)
			if ($this->app->session->isLoggedIn())
				$this->app->session->account->save();
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$order = $this->app->session->order;

		$order->email = $this->getOptionalStringValue('email');
		$order->company = $this->getOptionalStringValue('company');
		$order->phone = $this->getOptionalStringValue('phone');
		$order->comments = $this->getOptionalStringValue('comments');

		if ($this->app->session->checkout_with_account) {
			$account = $this->app->session->account;
			$account->fullname = $this->getOptionalStringValue('fullname');
			$account->email = $order->email;
			$account->phone = $order->phone;
			$account->company = $order->company;

			// only set password on new accounts
			if (!$this->app->session->isLoggedIn()) {
				$new_password = $this->ui->getWidget('password')->value;

				// don't change pass if it was left blank
				if ($new_password !== null)
					$account->setPassword($new_password);
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
				SwatMessage::ERROR);

			$message->secondary_content = sprintf(Store::_('Please %slog in to your '.
				'account%s.'),
				sprintf('<a href="checkout">',
				$email_address), '</a>');

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

		$instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->instance->getInstance() : null;

		$class_name = SwatDBClassMap::get('SiteAccount');
		$account = new $class_name();
		$account->setDatabase($this->app->db);
		$found = $account->loadWithEmail($email, $instance);

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
		if ($this->app->session->isLoggedIn() ||
			!$this->app->session->checkout_with_account) {

			$this->ui->getWidget('password_field')->visible = false;
			$this->ui->getWidget('confirm_password_field')->visible = false;
		}

		if (!$this->app->session->checkout_with_account)
			$this->ui->getWidget('fullname_field')->visible = false;

		if (!$this->ui->getWidget('form')->isProcessed())
			$this->loadDataFromSession();
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		if ($this->app->session->checkout_with_account) {
			$account = $this->app->session->account;

			$this->ui->getWidget('fullname')->value = $account->fullname;
			$this->ui->getWidget('email')->value = $account->email;
			$this->ui->getWidget('confirm_email')->value = $account->email;
			$this->ui->getWidget('phone')->value = $account->phone;
			$this->ui->getWidget('company')->value = $account->company;
		}

		$order = $this->app->session->order;

		if ($order->email !== null) {
			$this->ui->getWidget('email')->value = $order->email;
			$this->ui->getWidget('confirm_email')->value = $order->email;
		}

		if ($order->company !== null)
			$this->ui->getWidget('company')->value = $order->company;

		if ($order->phone !== null)
			$this->ui->getWidget('phone')->value = $order->phone;

		$this->ui->getWidget('comments')->value = $order->comments;
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
