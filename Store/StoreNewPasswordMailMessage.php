<?php

require_once 'Site/SiteMultipartMailMessage.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * Email that is sent to account holders when they are given a new password
 *
 * Sites must subclass this class and set site-specific properties. See the
 * {@link StoreNewPasswordMailMessage::init()} method for details.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
abstract class StoreNewPasswordMailMessage extends SiteMultipartMailMessage
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

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new password email
	 *
	 * @param StoreAccount $account the account to create the email for.
	 * @param string $new_password the new password assigned to the account.
	 */
	public function __construct(SiteApplication $app, StoreAccount $account,
		$new_password)
	{
		parent::__construct($app);

		$this->new_password = $new_password;
		$this->account = $account;

		$this->init();

		$required_properties = array(
			'smtp_server',
			'from_address',
			'from_name',
			'subject'
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
		$body =
			"This email is in response to your recent request for a new ".
			"password for your account. Your new password is:\n\n".
			"%s\n\n".
			"After logging into your account, you can set a new password by ".
			"clicking the \"Change Login Password\" on your account page.";

		return sprintf($body, $this->new_password);
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
			'password for your account. Your new password is:</p>'.
			'<p><strong>%s</strong></p>'.
			'<p>After logging into your account, you can set a new password '.
			'by clicking the "Change Login Password" on your account '.
			'page.</p>';

		return sprintf($body, $this->new_password);
	}

	// }}}
}

?>
