<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Front page of checkout
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutFrontPage extends StoreCheckoutPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $invoice_id = null;

	// }}}
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-front.xml';
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'invoice_id' => array(0, 0),
		);
	}

	// }}}
	// {{{ protected function getNextSource()

	protected function getNextSource()
	{
		return 'checkout/first';
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		// skip the checkout front page if logged in
		if ($this->app->session->isLoggedIn()) {
			$this->initDataObjects();
			$this->resetProgress();
			$this->updateProgress();

			// find the invoice if we have an invoice id
			$this->app->session->order->invoice = null;
			if ($this->invoice_id !== null) {
				$account = $this->app->session->account;
				$invoice = $account->invoices->getByIndex($this->invoice_id);

				if ($invoice != null) {
					$this->app->session->order->invoice = $invoice;
				}
			}

			$this->relocate();
		}

		if (intval($this->getArgument('invoice_id')) != 0) {
			$this->invoice_id = $invoice_id;
		}

		parent::init();
	}

	// }}}
	// {{{ protected function loadUI()

	// subclassed to avoid loading xml from a form that doesn't exist
	protected function loadUI()
	{
		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->getUiXml());
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		foreach ($this->ui->getRoot()->getDescendants('SwatForm') as $form) {
			$form->action = $this->source;
		}
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$new_form   = $this->ui->getWidget('new_form');
		$login_form = $this->ui->getWidget('login_form');

		if ($new_form->isProcessed()) {
			$this->processNewForm($new_form);
		}

		if ($login_form->isProcessed()) {
			$this->processLoginForm($login_form);
		}
	}

	// }}}
	// {{{ protected function processNewForm()

	protected function processNewForm($form)
	{
		$this->initDataObjects();

		$order   = $this->app->session->order;
		$account = $this->app->session->account;

		$email = $this->ui->getWidget('new_email_address');

		if ($email->value != '') {
			$order->email = $email->value;
			$account->email = $email->value;
		}

		$this->resetProgress();
		$this->updateProgress();
		$this->relocate();
	}

	// }}}
	// {{{ protected function processLoginForm()

	protected function processLoginForm($form)
	{
		if (!$form->hasMessage()) {
			$email = $this->ui->getWidget('login_email_address')->value;
			$password = $this->ui->getWidget('login_password')->value;

			if ($this->app->session->login($email, $password)) {
				$this->initDataObjects();
				$this->resetProgress();
				$this->updateProgress();
				$this->relocate();
			} else {
				$message = new SwatMessage(Store::_('Login Incorrect'),
					'warning');

				$tips = array(
					Store::_('Please check the spelling on your email '.
						'address or password'),
					sprintf(Store::_('Password is case-sensitive. Make sure '.
                        'your %sCaps Lock%s key is off'), '<kbd>', '</kbd>'),
				);
				$message->secondary_content =
					vsprintf('<ul><li>%s</li><li>%s</li></ul>', $tips);

				$message->content_type = 'text/xml';

				$this->ui->getWidget('message_display')->add($message);
			}
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate($this->getNextSource());
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		foreach ($this->app->messages->getAll() as $message) {
			$this->ui->getWidget('message_display')->add($message);
		}

		$this->buildForgotPasswordLink();
	}

	// }}}
	// {{{ protected function buildForgotPasswordLink()

	protected function buildForgotPasswordLink()
	{
		$block = $this->ui->getWidget('forgot_password');

		$block->content = $this->getForgotPasswordLink();
		$block->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function getForgotPasswordLink()

	protected function getForgotPasswordLink()
	{
		$email = $this->ui->getWidget('login_email_address');
		$link = sprintf(Store::_(' %sForgot your password?%s'),
			'<a href="account/forgotpassword%s">', '</a>');

		if ((!$email->hasMessage()) && ($email->value != '')) {
			$link_value = sprintf('?email=%s', urlencode($email->value));
		} else {
			$link_value = null;
		}

		return sprintf($link, $link_value);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-front-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
