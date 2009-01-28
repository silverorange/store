<?php

require_once 'Store/pages/StoreCheckoutEditPage.php';
require_once 'Swat/SwatYUI.php';

/**
 * Base address edit page of checkout
 *
 * @package   Store
 * @copyright 2009 silverorange
 */
abstract class StoreCheckoutAddressPage extends StoreCheckoutEditPage
{
	// {{{ protected properties

	/**
	 * @var StoreOrderAddress
	 *
	 * @see StoreCheckoutAddressPage::getAddress()
	 */
	protected $address;

	protected $verified_address;
	protected $button1;
	protected $button2;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$form = $this->ui->getWidget('form');
		$this->button1 = new SwatButton('button1');
		$this->button1->parent = $form;
		$this->button2 = new SwatButton('button2');
		$this->button2->parent = $form;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('form');
		$this->button1->process();
		$this->button2->process();

		if ($this->button1->hasBeenClicked())
			$this->verified_address = $form->getHiddenField('verified_address');

		if ($form->isProcessed() &&
			!$this->button2->hasBeenClicked() &&
			!$this->button1->hasBeenClicked() &&
			!$form->hasMessage())
				$this->verifyAddress($form);
	}

	// }}}
	// {{{ protected function verifyAddress()

	protected function verifyAddress(SwatForm $form)
	{
		$address = clone $this->getAddress();
		$address->setDatabase($this->app->db);
		$verified_address = clone $address;
		$valid = $verified_address->verify($this->app);
		$equal = $verified_address->mostlyEqual($address);

		if ($valid && $equal) {
			$this->verified_address = $verified_address;
			return;
		}

		$message = new SwatMessage('', 'notification');
		$message->secondary_content = '<p>'.Store::_(
			'To deliver to you more efficiently, we compared the address '.
			'you entered to information in a postal address database. The '.
			'database contains a record of all addresses that receive mail, '.
			'formatted in the preferred style.').'</p>';

		if ($valid) {
			$form->addHiddenField('verified_address', $verified_address);

			$message->primary_content = Store::_('Is this your address?');
			$this->button1->title = Store::_('Yes, this is my address');
			$this->button2->title = Store::_('No, use my address as entered below');

			ob_start();
			$verified_address->display();
			$this->button1->display();
			$this->button2->display();
			$message->secondary_content.= ob_get_clean();
		} else {
			$message->primary_content = Store::_('Address not found');
			$this->button2->title = Store::_('Yes, use my address as entered below');
			$message->secondary_content.= Store::_(
				'Please confirm the address below is correct.').
				'<br />';

			ob_start();
			$this->button2->display();
			$message->secondary_content.= ob_get_clean();
		}

		$message->content_type = 'text/xml';
		$form->addMessage($message);
		$this->ui->getWidget('message_display')->add($message);
		$this->show_invalid_message = false;
	}

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		$this->buildList();
		$this->buildForm();

		if (!$this->ui->getWidget('form')->isProcessed())
			$this->loadDataFromSession();
	}

	// }}}
	// {{{ public function postBuildCommon()

	public function postBuildCommon()
	{
		$this->layout->startCapture('content');
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		/*
		 * Set page to two-column layout when page is stand-alone even when
		 * there is no address list. The narrower layout of the form fields
		 * looks better even withour a select list on the left.
		 */
		$this->ui->getWidget('form')->classes[] = 'checkout-no-column';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-address-page.css',
			Store::PACKAGE_ID));

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$path = 'packages/store/javascript/';
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			$path.'store-checkout-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			$path.'store-checkout-address-page.js',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
