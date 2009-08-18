<?php

require_once 'Site/dataobjects/SiteComment.php';
require_once 'Site/SiteMultipartMailMessage.php';

/**
 * Freeform feedback about the website
 *
 * This class is extended from SiteComment. The following fields from
 * SiteComment are not exposed in the UI by default:
 *
 * - link
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedback extends SiteComment
{
	// {{{ public properties

	/**
	 * HTTP referrer that customer used to reach the site
	 *
	 * For example, this could contain a search referrer.
	 *
	 * @var string
	 */
	public $http_referrer;

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		if ($this->fullname !== null) {
			$title = $this->fullname;
		} elseif ($this->email !== null) {
			$title = $this->email;
		} else {
			$title = Store::_('anonymous');
		}

		return $title;
	}

	// }}}
	// {{{ public function sendEmail()

	public function sendEmail(StoreApplication $app)
	{
		$message = $this->getMessage($app);

		try {
			$message->send();
		} catch (SiteMailException $e) {
			$e->process(false);
		}

	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'Feedback';
	}

	// }}}
	// {{{ protected function getMessage()

	protected function getMessage(StoreApplication $app)
	{
		$message = new SiteMultipartMailMessage($app);

		$message->smtp_server      = $app->config->email->smtp_server;
		$message->from_address     = $app->config->email->website_address;
		$message->from_name        = $this->getFromName();
		$message->reply_to_address = $this->email;
		$message->to_address       = $app->config->email->feedback_address;

		$message->subject   = $this->getSubject();
		$message->text_body = $this->getTextBody();
		$message->text_body.= $this->browserInfo();

		return $message;
	}

	// }}}
	// {{{ protected function getFromAddress()

	protected function getFromName()
	{
		$user_address = $this->getTitle();
		$user_address = str_replace('@', ' at ', $user_address);
		$user_address = str_replace('.', ' dot ', $user_address);
		return $user_address;
	}

	// }}}
	// {{{ protected function getSubject()

	protected function getSubject()
	{
		return Store::_('Customer Feedback');
	}

	// }}}
	// {{{ protected function getTextBody()

	protected function getTextBody()
	{
		$text_body = sprintf(Store::_('Email From: %s'),
			$this->getTitle())."\n\n";

		$text_body.= $this->bodytext;

		return $text_body;
	}

	// }}}
	// {{{ protected function browserInfo()

	protected function browserInfo()
	{
		$info = "\n\n-------------------------\n";
		$info.= Store::_('User Information')."\n";

		if (isset($_SERVER['HTTP_USER_AGENT']))
			$info.= $_SERVER['HTTP_USER_AGENT'];
		else
			$info.= Store::_('Not available');

		return $info;
	}

	// }}}
}

?>
