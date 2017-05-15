<?php

/**
 * Page for adding and editing addresses stored on accounts
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreAccountAddressEditPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var StoreAccountAddress
	 */
	protected $address;

	/**
	 * @var boolean
	 */
	protected $show_invalid_message = true;

	/**
	 * @var boolean
	 */
	protected $verified_address;

	/**
	 * Button for address verification
	 *
	 * @var SwatButton
	 */
	protected $confirm_yes_button;

	/**
	 * Button for address verification
	 *
	 * @var SwatButton
	 */
	protected $confirm_no_button;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/pages/account-address-edit.xml';
	}

	// }}}
	// {{{ protected function isNew()

	protected function isNew(SwatForm $form)
	{
		return (!$this->id);
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'id' => array(0, 0),
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		// redirect to login page if not logged in
		if (!$this->app->session->isLoggedIn()) {
			$uri = sprintf(
				'%s?relocate=%s',
				$this->app->config->uri->account_login,
				$this->source
			);

			$this->app->relocate($uri);
		}

		parent::initInternal();

		$this->id = intval($this->getArgument('id'));
		$this->initAddress();
		$this->initCountryAndProvstate();

		$form = $this->ui->getWidget('edit_form');

		$this->confirm_yes_button = new SwatButton('confirm_yes_button');
		$this->confirm_yes_button->parent = $form;

		$this->confirm_no_button = new SwatButton('confirm_no_button');
		$this->confirm_no_button->parent = $form;
	}

	// }}}
	// {{{ protected function initCountryAndProvstate()

	protected function initCountryAndProvstate()
	{
		$country_sql = sprintf(
			'select id, title from Country
			where visible = %s
			order by title',
			$this->app->db->quote(true, 'boolean')
		);

		$countries = SwatDB::query(
			$this->app->db,
			$country_sql,
			SwatDBClassMap::get('StoreCountryWrapper')
		);

		$provstates = $countries->loadAllSubRecordsets(
			'provstates',
			SwatDBClassMap::get('StoreProvStateWrapper'),
			'ProvState',
			'text:country',
			null,
			'title'
		);

		$country_flydown = $this->ui->getWidget('country');
		$country_flydown->serialize_values = false;
		foreach ($countries as $country) {
			$country_flydown->addOption($country->id, $country->title);
		}

		// default country flydown to the country of the current locale
		$country_flydown->value = $this->app->getCountry();

		$data = array();
		foreach ($countries as $country) {

			$data[$country->id] = array(
				'title'        => $country->title,
				'select_title' => $country->getRegionSelectTitle(),
				'field_title'  => sprintf(
					Store::_('%s:'),
					$country->getRegionTitle()
				),
				'required'     => $country->getRegionRequired(),
				'visible'      => $country->getRegionVisible(),
			);

			if (count($country->provstates) === 0) {
				$data[$country->id]['provstates'] = null;
			} else {
				$data[$country->id]['provstates'] = array();
				foreach ($country->provstates as $provstate) {
					$data[$country->id]['provstates'][] = array(
						'id'    => $provstate->id,
						'title' => $provstate->title,
					);
				}
			}
		}

		$provstate_flydown = $this->ui->getWidget('provstate');
		$provstate_flydown->data = $data;
		$provstate_flydown->setCountryFlydown($country_flydown);
	}

	// }}}
	// {{{ protected function initAddress()

	protected function initAddress()
	{
		$form = $this->ui->getWidget('edit_form');

		if ($this->isNew($form)) {
			$class   = SwatDBClassMap::get('StoreAccountAddress');
			$address = new $class();
			$address->setDatabase($this->app->db);
		} else {
			$address = $this->app->session->account->addresses->getByIndex(
				$this->id
			);

			if ($address === null) {
				throw new SiteNotFoundException(
					sprintf('An address with an id of ‘%d’ does not exist.',
					$this->id));
			}
		}

		$this->address = $address;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		$form = $this->ui->getWidget('edit_form');

		if ($form->isSubmitted())
			$this->setupPostalCode();

		$this->confirm_yes_button->process();
		$this->confirm_no_button->process();

		if ($this->confirm_yes_button->hasBeenClicked())
			$this->verified_address = $form->getHiddenField('verified_address');

		parent::process();
	}

	// }}}
	// {{{ protected function validate()

	protected function validate(SwatForm $form)
	{
		if (!$this->confirm_no_button->hasBeenClicked() &&
			!$this->confirm_yes_button->hasBeenClicked() &&
			$this->isValid($form) &&
			StoreAddress::isVerificationAvailable($this->app)) {
				$this->verifyAddress($form);
		}
	}

	// }}}
	// {{{ protected function verifyAddress()

	protected function verifyAddress(SwatForm $form)
	{
		$entered_address = clone $this->address;
		$entered_address->setDatabase($this->app->db);
		$this->updateAddress($form, $entered_address);
		$verified_address = clone $entered_address;
		$valid = $verified_address->verify($this->app);
		$equal = $verified_address->mostlyEqual($entered_address);

		if ($valid && $equal) {
			$this->verified_address = $verified_address;
			return;
		}

		$message = new SwatMessage('', 'notification');
		$message->secondary_content = '<p>'.Store::_(
			'To ensure effective delivery, we have compared your address to '.
			'our postal address database for formatting and style. Please '.
			'review the recommendations below:').'</p>';

		if ($valid) {
			$form->addHiddenField('verified_address', $verified_address);

			$message->primary_content = Store::_('Is this your address?');
			$this->confirm_yes_button->title = Store::_(
				'Yes, this is my address'
			);
			$this->confirm_yes_button->classes[] = 'address-verification-yes';
			$this->confirm_no_button->title = Store::_(
				'No, use my address as entered below'
			);
			$this->confirm_no_button->classes[] = 'address-verification-no';

			ob_start();
			echo '<p class="account-address-verified">';
			$verified_address->display();
			echo '</p>';
			$this->confirm_yes_button->display();
			$this->confirm_no_button->display();
			$message->secondary_content.= ob_get_clean();
		} else {
			$message->primary_content = Store::_('Address not found');
			$this->confirm_no_button->title = Store::_(
				'Yes, use my address as entered below'
			);

			ob_start();
			$this->confirm_no_button->display();
			$message->secondary_content.= ob_get_clean();
		}

		$message->content_type = 'text/xml';
		$form->addMessage($message);
		$this->ui->getWidget('message_display')->add($message);
		$this->show_invalid_message = false;
	}

	// }}}
	// {{{ protected function getInvalidMessage()

	protected function getInvalidMessage(SwatForm $form)
	{
		$message = null;

		if ($this->show_invalid_message)
			$message = parent::getInvalidMessage($form);

		return $message;
	}

	// }}}
	// {{{ protected function updateAddress()

	protected function updateAddress(SwatForm $form, StoreAddress $address)
	{
		$this->assignUiValuesToObject(
			$address,
			array(
				'fullname',
				'company',
				'country',
				'line1',
				'line2',
				'city',
				'postal_code',
				'phone',
			)
		);

		$provstate = $this->ui->getWidget('provstate');
		$address->provstate = $provstate->provstate_id;
		$address->provstate_other = $provstate->provstate_other;
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		if ($this->verified_address !== null)
			$this->address->copyFrom($this->verified_address);
		else
			$this->updateAddress($form, $this->address);

		if ($this->isNew($form)) {
			$this->address->account    = $this->app->session->account;
			$this->address->createdate = new SwatDate();
			$this->address->createdate->toUTC();
			$this->address->save();

			$this->addMessage(Store::_('One address has been added.'));

		} elseif ($this->address->isModified()) {
			$this->address->save();
			$this->addMessage(Store::_('One address has been updated.'));
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate('account');
	}

	// }}}
	// {{{ protected function setupPostalCode()

	protected function setupPostalCode()
	{
		// set provsate and country on postal code entry
		$country_widget = $this->ui->getWidget('country');
		$postal_code = $this->ui->getWidget('postal_code');
		$provstate   = $this->ui->getWidget('provstate');

		$country_widget->process();
		$country_id = $country_widget->value;

		$class_name = SwatDBClassMap::get('StoreCountry');
		$country = new $class_name();
		$country->setDatabase($this->app->db);
		if (!$country->load($country_id)) {
			return;
		}

		$provstate->process();

		if ($provstate->provstate_id !== null) {
			$sql = sprintf(
				'select abbreviation from ProvState where id = %s',
				$this->app->db->quote($provstate->provstate_id)
			);

			$provstate_abbreviation = SwatDB::queryOne($this->app->db, $sql);
			$postal_code->country = $country_id;
			$postal_code->provstate = $provstate_abbreviation;
		}

		if (!$country->has_postal_code) {
			$postal_code->required = false;
		}
	}

	// }}}
	// {{{ protected function addMessage()

	protected function addMessage($text)
	{
		ob_start();
		$this->address->displayCondensed();
		$address_condensed = ob_get_clean();

		$message = new SwatMessage($text, SwatMessage::NOTIFICATION);
		$message->secondary_content = $address_condensed;
		$message->content_type = 'text/xml';
		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_form');
		if (!$this->isNew($form)) {
			$this->ui->getWidget('submit_button')->title =
				Store::_('Update Address');
		} elseif (!$form->isProcessed()) {
			$this->setDefaultValues($this->app->session->account);
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if (!property_exists($this->layout, 'navbar'))
			return;

		parent::buildNavBar();

		$form = $this->ui->getWidget('edit_form');
		if ($this->isNew($form)) {
			$this->layout->navbar->createEntry(Store::_('Add a New Address'));
		} else {
			$this->layout->navbar->createEntry(
				Store::_('Edit an Existing Address'));
		}
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		parent::buildTitle();

		$form = $this->ui->getWidget('edit_form');
		if ($this->isNew($form)) {
			$this->layout->data->title = Store::_('Add a New Address');
		} else {
			$this->layout->data->title = Store::_('Edit an Existing Address');
		}
	}

	// }}}
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
		$this->assignObjectValuesToUi(
			$this->address,
			array(
				'fullname',
				'company',
				'country',
				'line1',
				'line2',
				'city',
				'postal_code',
				'phone',
			)
		);

		$provstate = $this->ui->getWidget('provstate');
		$provstate->provstate_id =
			$this->address->getInternalValue('provstate');

		$provstate->provstate_other = $this->address->provstate_other;
	}

	// }}}
	// {{{ protected function setDefaultValues()

	/**
	 * Sets default values of this address based on values from the account
	 *
	 * @param StoreAccount $account the account to set default values from.
	 */
	protected function setDefaultValues(StoreAccount $account)
	{
		$this->ui->getWidget('fullname')->value = $account->fullname;
		$this->ui->getWidget('company')->value  = $account->company;
		$this->ui->getWidget('phone')->value    = $account->phone;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('account-address-edit');

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(
			'packages/store/styles/store-account-address-edit-page.css'
		);

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet()
		);
	}

	// }}}
}

?>
