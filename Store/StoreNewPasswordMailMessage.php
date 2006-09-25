<?php

require_once 'Site/SiteMultipartMailMessage.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * Email that is sent to account holders when they are given a new password
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreNewPasswordMailMessage extends SiteMultipartMailMessage
{
	// {{{ protected properties

	/**
	 * The account this new password mail message is intended for
	 *
	 * @var StoreAccount
	 */
	protected $account;

	/**
	 * The new password assigned to the account
	 *
	 * @var string
	 */
	protected $new_password;

	/**
	 * The title of the application sending the reset password mail
	 *
	 * This title is visible inside the mail message bodytext.
	 *
	 * @var string
	 */
	protected $application_title;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new password email
	 *
	 * @param StoreAccount $account the account to create the email for.
	 * @param string $new_password the new password assigned to the account.
	 */
	public function __construct(SiteApplication $app, StoreAccount $account,
		$new_password, $application_title)
	{
		parent::__construct($app);

		$this->new_password = $new_password;
		$this->account = $account;
		$this->application_title = $application_title;
	}

	// }}}
	// {{{ public function send()

	/**
	 * Sends this mail message
	 */
	public function send()
	{
		if ($this->account->email === null)
			throw new StoreException('Account requires an email address to '.
				'generate new password. Make sure email is loaded on the '.
				'account object.');

		if ($this->account->fullname === null)
			throw new StoreException('Account requires a fullname to '.
				'generate new password. Make sure fullname is loaded on the '.
				'account object.');

		$this->to_address = $this->account->email;
		$this->to_name = $this->account->fullname;
		$this->text_body = $this->getTextBody();
		$this->html_body = $this->getHtmlBody();

		parent::send();
	}

	// }}}
	// {{{ protected function getTextBody()

	/** 
	 * Gets the plain-text content of this mail message
	 *
	 * @return string the plain-text content of this mail message.
	 */
	protected function getTextBody()
	{	
		return $this->getFormattedBody(
			"%s\n\n%s\n\n%s",
			$this->new_password);
	}

	// }}}
	// {{{ protected function getHtmlBody()

	/** 
	 * Gets the HTML content of this mail message
	 *
	 * @return string the HTML content of this mail message.
	 */
	protected function getHtmlBody()
	{
		return $this->getFormattedBody(
			'<p>%s</p><p>%s</p><p>%s</p>',
			sprintf('<strong>%s</strong>', $this->new_password));
	}

	// }}}
	// {{{ protected function getFormattedBody()

	protected function getFormattedBody($format_string, $formatted_password)
	{
		return sprintf($format_string,
			sprintf(Store::_('This email is in response to your recent '.
			'request for a new password for your %s account. Your new '.
			'password is:'), $this->application_title),
			
			$formatted_password,

			Store::_('After logging into your account, you can set a new '.
			'password by clicking the "Change Login Password" on your '.
			'account page.'));
	}

	// }}}
}

?>
