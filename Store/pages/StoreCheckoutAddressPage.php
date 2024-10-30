<?php

/**
 * Base address edit page of checkout
 *
 * @package   Store
 * @copyright 2009-2016 silverorange
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

	/*
	 * @var StoreGoogleAddressAutoComplete
	 */
	protected $auto_complete;

	protected bool $show_invalid_message = true;

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();

		$this->auto_complete = new StoreGoogleAddressAutoComplete();
		$this->auto_complete->setApplication($this->app);
	}

	// }}}
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
	// {{{ public function processCommon()

	public function processCommon()
	{
		if (!$this->ui->getWidget('form')->hasMessage())
			$this->validateAddress();

		// only save address in session if above validation didn't cause other
		// validation messages to be generated.
		if (!$this->ui->getWidget('form')->hasMessage())
			$this->saveDataToSession();
	}

	// }}}
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('form');
		$this->button1->process();
		$this->button2->process();

		if ($this->button1->hasBeenClicked())
			$this->verified_address = $form->getHiddenField('verified_address');
	}

	// }}}
	// {{{ protected function validateAddress()

	protected function validateAddress()
	{
		$this->validateAddressRequiredFields();

		if ($this->shouldVerifyAddress()) {
			$this->verifyAddress();
		}
	}

	// }}}
	// {{{ protected function validateAddressRequiredFields()

	protected function validateAddressRequiredFields()
	{
		$address = $this->getAddress();
		$required_fields = $this->getRequiredAddressFields($address);

		foreach ($required_fields as $field => $widget_id) {
			if (!isset($address->$field)) {
				$this->ui->getWidget($widget_id)->addMessage(new SwatMessage(
					Store::_('The %s field is required.'), 'error'));
			}
		}
	}

	// }}}
	// {{{ protected function getRequiredAddressFields()

	protected function getRequiredAddressFields(StoreOrderAddress $address)
	{
		return array();
	}

	// }}}
	// {{{ protected function shouldVerifyAddress()

	protected function shouldVerifyAddress()
	{
		$form = $this->ui->getWidget('form');

		$verify =
			!$this->is_embedded &&
			$form->isProcessed() &&
			!$this->button2->hasBeenClicked() &&
			!$this->button1->hasBeenClicked() &&
			!$form->hasMessage() &&
			StoreAddress::isVerificationAvailable($this->app);

		return $verify;
	}

	// }}}
	// {{{ protected function verifyAddress()

	protected function verifyAddress()
	{
		$form = $this->ui->getWidget('form');
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

		if ($valid) {
			$form->addHiddenField('verified_address', $verified_address);

			$message->primary_content = Store::_('Is this your address?');

			$message->secondary_content = '<p>'.Store::_(
				'To ensure effective delivery, we have compared your address '.
				'to our postal address database for formatting and style. '.
				'Please review the recommendations below:').'</p>';

			$this->button1->title = Store::_('Yes, this is my address');
			$this->button1->classes[] = 'address-verification-yes';
			$this->button2->title =
				Store::_('No, use my address as entered below');

			$this->button2->classes[] = 'address-verification-no';

			ob_start();
			echo '<p class="checkout-address-verified">';
			$verified_address->display();
			echo '</p>';
			$this->button1->display();
			$this->button2->display();
			$message->secondary_content.= ob_get_clean();
		} else {
			$message->primary_content = Store::_('Address not found');
			$this->button2->title =
				Store::_('Yes, use my address as entered below');

			$message->secondary_content = '<p>'.Store::_(
				'To ensure effective delivery, we have compared your address '.
				'to our postal address database for formatting and style. '.
				'The address you entered was not found.').'</p>';

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
	// {{{ abstract protected function getAddress()

	abstract protected function getAddress();

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		$this->buildList();
		$this->buildForm();

		if (!$this->ui->getWidget('form')->isProcessed()) {
			$this->loadDataFromSession();
		}
	}

	// }}}
	// {{{ public function postBuildCommon()

	public function postBuildCommon()
	{
		$this->layout->startCapture('content');
		$this->auto_complete->display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
	}

	// }}}
	// {{{ protected function buildList()

	protected function buildList()
	{
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
	}

	// }}}
	// {{{ abstract protected function getInlineJavaScript()

	abstract protected function getInlineJavaScript();

	// }}}
	// {{{ protected function saveDataToSession()

	abstract protected function saveDataToSession();

	// }}}
	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(
			'packages/store/styles/store-checkout-address-page.css'
		);

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
		$this->layout->addHtmlHeadEntry(
			'packages/store/javascript/store-checkout-page.js'
		);

		$this->layout->addHtmlHeadEntry(
			'packages/store/javascript/store-checkout-address-page.js'
		);

		$this->layout->addHtmlHeadEntrySet(
			$this->auto_complete->getHtmlHeadEntrySet()
		);
	}

	// }}}

}

?>
