<?php

require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/StoreUI.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAccountResetPasswordPage extends StoreArticlePage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-reset-password.xml';

	// }}}
	// {{{ private properties

	private $ui;
	private $password_tag = null;
	private $account_id;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$tag = null)
	{
		parent::__construct($app, $layout);

		if ($tag === null) {
			if ($this->app->session->isLoggedIn())
				$this->app->relocate('account/changepassword');
			else
				$this->app->relocate('account/forgotpassword');
		}


		$this->password_tag = $tag;
	}

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

		$confirm = $this->ui->getWidget('confirm_password');
		$confirm->password_widget = $this->ui->getWidget('password');;

		$this->account_id = SwatDB::queryOne($this->app->db, sprintf('
			select id from Account where password_tag = %s',
			$this->app->db->quote($this->password_tag, 'text')));

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		if ($this->account_id === null)
			return;

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage()) {
				$this->app->session->loginById($this->account_id);

				$password = $this->ui->getWidget('password')->value;
				$this->app->session->account->password = md5($password);
				$this->app->session->account->password_tag = null;
				$this->app->session->account->save();

				$this->app->messages->add(
					new SwatMessage('Account password has been updated.'));

				$this->app->relocate('account');
			}
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-account-reset-password-page.css',
			Store::PACKAGE_ID));

		if ($this->account_id === null) {
			$text = '<p>Please verify that the link is exactly the same as '.
				'the one emailed to you.</p>'.
				'<ul><li>If you requested an email more than once, only the most '.
				'recent link will work.</li>'.
				'<li>If you have lost the link sent in the email, you may '.
				'<a href="account/forgotpassword">have the email sent again</a>.'.
				'</li></ul>';

			$msg = new SwatMessage('Link Incorrect',
				SwatMessage::ERROR);

			$msg->secondary_content = $text;
			$msg->content_type = 'text/xml';
			$this->ui->getWidget('message_display')->add($msg);

			$this->ui->getWidget('field_container')->visible = false;

			$this->layout->clear('content');
		}

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
}

?>
