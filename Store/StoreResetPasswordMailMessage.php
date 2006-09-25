<?php

require_once 'Site/SiteMultipartMailMessage.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * Email that is sent to account holders when they request a new password
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreResetPasswordMailMessage extends SiteMultipartMailMessage
{
	// {{{ protected properties

	/**
	 * The account this reset password mail message is intended for
	 *
	 * @var StoreAccount
	 */
	protected $account;

	/**
	 * The URL of the application page that performs that password reset
	 * action
	 *
	 * @var string
	 */
	protected $password_link;

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
	 * Creates a new reset password email
	 *
	 * @param StoreAccount $account the account to create the email for.
	 * @param string $password_link the URL of the application page that
	 *                               performs the password reset.
	 */
	public function __construct(SiteApplication $app, StoreAccount $account,
		$password_link, $application_title)
	{
		parent::__construct($app);

		$this->password_link = $password_link;
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
				'reset password. Make sure email is loaded on the account '.
				'object.');

		if ($this->account->fullname === null)
			throw new StoreException('Account requires a fullname to reset '.
				'password. Make sure fullname is loaded on the account '.
				'object.');

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
			"%s\n\n%s\n\n%s\n\n%s\n%s\n\n%s\n%s",
			$this->password_link);
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
			'<p>%s</p><p>%s</p><p>%s</p><p>%s<br />%s</p><p>%s<br />%s</p>',
			sprintf('<a href="%1$s">%1$s</a>', $this->password_link));
	}

	// }}}
	// {{{ protected function getFormattedBody()

	protected function getFormattedBody($format_string, $formatted_link)
	{
		return sprintf($format_string,
			sprintf(Store::_('This email is in response to your recent '.
			'request for a new password for your %s account. Your password '.
			'has not yet been changed. Please click on the following link '.
			'and follow the outlined steps to change your account password.'),
				$this->application_title),

			$formatted_link,

			Store::_('Clicking on this link will take you to a page that '.
			'requires you to enter in and confirm a new password. Once you '.
			'have chosen and confirmed your new password you will be taken to '.
			'your account page.'),

			Store::_('Why did I get this email?'),

			Store::_('When a customer forgets their password the only way '.
			'for us to verify their identity is to send an email to the '.
			'address listed in their account. By clicking on the link above '.
			'you are verifying that you requested a new password for your '.
			'account.'),

			Store::_('I did not request a new password:'),

			sprintf(Store::_('If you did not request a new password from %s '.
			'then someone may have accidentally entered your email when '.
			'requesting a new password. Have no fear! Your account '.
			'information is safe. Simply ignore this email and continue '.
			'using your existing password.'),
				$this->application_title));
	}

	// }}}
}

?>
