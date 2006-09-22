<?php

require_once 'Site/SiteMultipartMailMessage.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * Email that is sent to account holders when they request a new password
 *
 * Sites must subclass this class and set site-specific properties. See the
 * {@link StoreResetPasswordMailMessage::init()} method for details.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
abstract class StoreResetPasswordMailMessage extends SiteMultipartMailMessage
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
		$password_link)
	{
		parent::__construct($app);

		$this->password_link = $password_link;
		$this->account = $account;

		$this->init();

		$required_properties = array(
			'application_title',
			'smtp_server',
			'from_address',
			'from_name',
			'subject',
		);

		foreach ($required_properties as $property) {
			if (!isset($this->$property))
				throw new StoreException(sprintf(
					'%s property must be set in init() method',
					$property));
		}
	}

	// }}}
	// {{{ protected function init()

	/**
	 * Initializes properties of this mail message
	 *
	 * Subclasses must extends this method to set site-specific properties
	 * on this mail message.
	 *
	 * Site-specific properties that must be set are:
	 * - {@link StoreResetPasswordMailMessage::$application_title},
	 * - {@link SiteMultipartMailMessage::$smtp_server},
	 * - {@link SiteMultipartMailMessage::$from_address},
	 * - {@link SiteMultipartMailMessage::$from_name} and
	 * - {@link SiteMultipartMailMessage::$subject}
	 */
	protected function init()
	{
		$this->to_address = $this->account->email;
		$this->to_name = $this->account->fullname;
		$this->text_body = $this->getTextBody();
		$this->html_body = $this->getHtmlBody();
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
		$body = Store::_(
			Store::_('This email is in response to your recent request for a '.
			'new password for your %1$s account. Your password has not yet '.
			'been changed. Please click on the following link and follow the '.
			'outlined steps to change your account password.')."\n\n".

			"%2\$s\n\n".

			Store::_('Clicking on this link will take you to a page that '.
			'requires you to enter in and confirm a new password. Once you '.
			'have chosen and confirmed your new password you will be taken to '.
			'your account page.')."\n\n".

			Store::_('Why did I get this email?')."\n".

			Store::_('When a customer forgets their password the only way '.
			'for us to verify their identity is to send an email to the '.
			'address listed in their account. By clicking on the link above '.
			'you are verifying that you requested a new password for your '.
			'account.')."\n\n".

			Store::_('I did not request a new password:')."\n".

			Store::_('If you did not request a new password from %1$s then '.
			'someone may have accidentally entered your email when '.
			'requesting a new password. Have no fear! Your account '.
			'information is safe. Simply ignore this email and continue '.
			'using your existing password.');

		return sprintf($body, $this->application_title, $this->password_link);
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
		$body =
			'<p>This email is in response to your recent request for a new '.
			'password for your %1$s account. Your password has not yet been '.
			'changed. Please click on the following link and follow the '.
			'outlined steps to change your account password.</p>'.
			'<a href="%2$s">%2$s</a></p>'.
			'<p>Clicking on this link will take you to a page that requires '.
			'you to enter in and confirm a new password. Once you have chosen '.
			'and confirmed your new password you will be taken to your '.
			'account page.</p>'.
			'<p>Why did I get this email?<br />'.
			'When a customer forgets their password the only way for us to '.
			'verify their identity is to send an email to the address listed '.
			'in their account. By clicking on the link above you are '.
			'verifying that you requested a new password for your'.
			'account.</p>'.
			'<p>I did not request a new password:<br />'.
			'If you did not request a new password from %1$s then someone '.
			'may have accidentally entered your email when requesting a new '.
			'password. Have no fear! Your account information is safe. '.
			'Simply ignore this email and continue using your existing '.
			'password.</p>';

		return sprintf($body, $this->application_title, $this->password_link);
	}

	// }}}
}

?>
