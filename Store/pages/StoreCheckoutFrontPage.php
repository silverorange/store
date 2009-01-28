<?php

require_once 'Store/pages/StoreCheckoutPage.php';

/**
 * Front page of checkout
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
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

	// init phase
	// {{{ public function init()

	public function init()
	{
		// skip the checkout front page if logged in
		if ($this->app->session->isLoggedIn()) {
			$this->app->session->checkout_with_account = true;
			$this->initDataObjects();
			$this->resetProgress();
			$this->updateProgress();

			// find the invoice if we have an invoice id
			$this->app->session->order->invoice = null;
			if ($this->invoice_id !== null) {
				$account = $this->app->session->account;
				$invoice = $account->invoices->getByIndex($this->invoice_id);
				if ($invoice != null)
					$this->app->session->order->invoice = $invoice;
			}

			$this->app->relocate('checkout/first');
		}

		if (intval($this->getArgument('invoice_id')) != 0)
			$this->invoice_id = $invoice_id;

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
		foreach ($this->ui->getRoot()->getDescendants('SwatForm') as $form)
			$form->action = $this->source;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$create_account_form = $this->ui->getWidget('create_account_form');
		$just_place_form     = $this->ui->getWidget('just_place_form');
		$login_form          = $this->ui->getWidget('login_form');

		if ($create_account_form->isProcessed())
			$this->processCreateAccount($create_account_form);

		if ($just_place_form->isProcessed())
			$this->processJustPlace($just_place_form);

		if ($login_form->isProcessed())
			$this->processLogin($login_form);
	}

	// }}}
	// {{{ protected function processForm()

	protected function processForm($form, $checkout_with_account)
	{
		$this->initDataObjects();
		$this->resetProgress();
		$this->updateProgress();
		$this->app->session->checkout_with_account = $checkout_with_account;
		$this->app->relocate('checkout/first');
	}

	// }}}
	// {{{ protected function processCreateAccount()

	protected function processCreateAccount($create_account_form)
	{
		$this->processForm($create_account_form, true);
	}

	// }}}
	// {{{ protected function processJustPlace()

	protected function processJustPlace($just_place_form)
	{
		$this->processForm($just_place_form, false);
	}

	// }}}
	// {{{ protected function processLogin()

	protected function processLogin($login_form)
	{
		if (!$login_form->hasMessage()) {
			$email = $this->ui->getWidget('email_address')->value;
			$password = $this->ui->getWidget('password')->value;

			if ($this->app->session->login($email, $password)) {
				$this->processForm($login_form, true);
			} else {
				$message = new SwatMessage(Store::_('Login Incorrect'),
					SwatMessage::WARNING);

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


	/// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		foreach ($this->app->messages->getAll() as $message) {
			$this->ui->getWidget('message_display')->add($message);
		}
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
