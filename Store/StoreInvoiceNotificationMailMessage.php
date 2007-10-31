<?php

require_once 'Site/SiteMultipartMailMessage.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * Email that is sent to account holders to notify them of an invoice.
 * If they don't have a password, a link will inform them of how to choose one.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreInvoiceNotificationMailMessage extends SiteMultipartMailMessage
{
	// {{{ protected properties

	/**
	 * The account this reset password mail message is intended for
	 *
	 * @var StoreInvoice
	 */
	protected $invoice;

	/**
	 * The URL of the application page for logging into an account
	 *
	 * @var string
	 */
	protected $account_link;

	/**
	 * The URL of the application page that performs that password reset
	 * action (optional)
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
	 * Creates a new invoice password email
	 *
	 * @param SiteApplication $app the site application this email belongs
	 *        to
	 * @param StoreInvoice $account the account to create the email for.
	 * @param string $application_title The title of the application -
	 *        displayed in the email as the site name.
	 * @param string $account_link the link to account login
	 * @param string $password_link the link to create a new password
	 */
	public function __construct(SiteApplication $app, StoreInvoice $invoice,
		$application_title, $account_link, $password_link = null)
	{
		parent::__construct($app);

		$this->password_link = $password_link;
		$this->account_link = $account_link;
		$this->invoice = $invoice;
		$this->application_title = $application_title;
	}

	// }}}
	// {{{ public function send()

	/**
	 * Sends this mail message
	 */
	public function send()
	{
		if ($this->invoice->account->email === null)
			throw new StoreException('Account requires an email address to '.
				'send notification. Make sure email is loaded on the account '.
				'object.');

		if ($this->invoice->account->getFullName() === null)
			throw new StoreException('Account requires a fullname to send '.
				'notification. Make sure getFullName() is returning a name.');

		$this->to_address = $this->invoice->account->email;
		$this->to_name = $this->invoice->account->getFullName();
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
			"%s\n\n%s",
			$this->account_link,
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
			'<p>%s</p><p>%s</p>',
			sprintf('<a href="%1$s">%1$s</a>', $this->account_link),
			sprintf('<a href="%1$s">%1$s</a>', $this->password_link));
	}

	// }}}
	// {{{ protected function getFormattedBody()

	protected function getFormattedBody($format_string, $account_link, $password_link = null)
	{
		$text = sprintf(Store::_('This email is to notify you that a new invoice '.
			'is ready for you from %s.'), $this->application_title);

		if ($this->password_link === null)
			$link_text = sprintf(Store::_('You can review and purchase the invoice '.
				'by logging in to your account at: %s'),
				$account_link);
		else
			$link_text = sprintf(Store::_('You can review and purchase the invoice '.
				'by logging in to your account. Because this is a new account, you will '.
				'first be asked to create a password: %s'),
				$password_link);

		return sprintf($format_string, $text, $link_text);
	}

	// }}}
}

?>
