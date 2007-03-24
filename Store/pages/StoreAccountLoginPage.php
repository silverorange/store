<?php

require_once 'Store/StoreUI.php';
require_once 'Store/pages/StoreAccountPage.php';

/**
 * Page for logging into an account
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreAccountLoginPage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-login.xml';

	// }}}
	// {{{ private properties

	private $ui;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// go to detail page if already logged in
		if ($this->app->session->isLoggedIn())
			$this->app->relocate('account');

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);

		$form = $this->ui->getWidget('login_form');
		$form->action = $this->source;

		$form = $this->ui->getWidget('create_account_form');
		$form->action = 'account/edit';

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('login_form');

		$form->process();

		if ($form->isProcessed() && !$form->hasMessage()) {
			$email = $this->ui->getWidget('email_address')->value;
			$password = $this->ui->getWidget('password')->value;

			if ($this->app->session->login($email, $password)) {
				$this->app->cart->save();
				$this->app->relocate('account');
			} else {
				$message = new SwatMessage(Store::_('Login Incorrect'),
					SwatMessage::WARNING);

				$message->secondary_content = sprintf(
					'<ul><li>%s</li><li>%s</li></ul>',
					Store::_('Please check the spelling on your email '.
						'address or password.'),
					sprintf(Store::_('Password is case-sensitive. Make sure '.
						'your %sCaps Lock%s key is off.'),
						'<kbd>', '</kbd>'));

				$message->content_type = 'text/xml';
				$this->ui->getWidget('message_display')->add($message);
			}
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

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-account-login-page.css',
			Store::PACKAGE_ID));

		$this->layout->startCapture('content', true);
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
}

?>
