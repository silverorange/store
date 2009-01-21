<?php

require_once 'Swat/SwatYUI.php';
require_once 'Swat/SwatUI.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'Store/dataobjects/StoreAccountAddress.php';

/**
 * Page for adding and editing addresses stored on accounts
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 */
class StoreAccountAddressEditPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var StoreAccountAddress
	 */
	protected $address;

	// }}}
	// {{{ private properties

	/**
	 * @var integer
	 */
	private $id;

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
		if (!$this->app->session->isLoggedIn())
			$this->app->relocate('account/login');

		parent::initInternal();

		$this->id = intval($this->getArgument('id'));
		$this->initAddress();
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
				$this->id);

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

		parent::process();
	}

	// }}}
	// {{{ protected function updateAddress()

	protected function updateAddress(SwatForm $form)
	{
		$this->assignUiValuesToObject($this->address, array(
			'fullname',
			'company',
			'phone',
			'line1',
			'line2',
			'city',
			'provstate',
			'provstate_other',
			'postal_code',
			'country',
		));

		if ($this->address->provstate === 'other')
			$this->address->provstate = null;
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		$this->updateAddress($form);

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
	// {{{ private function setupPostalCode()

	private function setupPostalCode()
	{
		// set provsate and country on postal code entry
		$postal_code = $this->ui->getWidget('postal_code');
		$country     = $this->ui->getWidget('country');
		$provstate   = $this->ui->getWidget('provstate');

		$country->process();
		$provstate->country = $country->value;
		$provstate->setDatabase($this->app->db);
		$provstate->process();

		if ($provstate->value === 'other') {
			$this->ui->getWidget('provstate_other')->required = true;
		} elseif ($provstate->value !== null) {
			$sql = sprintf('select abbreviation from ProvState where id = %s',
				$this->app->db->quote($provstate->value, 'text'));

			$provstate_abbreviation = SwatDB::queryOne($this->app->db, $sql);
			$postal_code->country   = $country->value;
			$postal_code->provstate = $provstate_abbreviation;
		}
	}

	// }}}
	// {{{ private function addMessage()

	private function addMessage($text)
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

		$provstate_flydown = $this->ui->getWidget('provstate');
		$provstate_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'ProvState', 'title', 'id', 'title'));

		$provstate_other = $this->ui->getWidget('provstate_other');
		if ($provstate_other->visible) {
			$provstate_flydown->addDivider();
			$option = new SwatOption('other', 'Other…');
			$provstate_flydown->addOption($option);
		}

		$country_flydown = $this->ui->getWidget('country');
		$country_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'Country', 'title', 'id', 'title',
			sprintf('visible = %s', $this->app->db->quote(true, 'boolean'))));

		$this->layout->startCapture('content');
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
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
		$this->assignObjectValuesToUi($this->address, array(
			'fullname',
			'company',
			'phone',
			'line1',
			'line2',
			'city',
			'provstate',
			'provstate_other',
			'postal_code',
			'country',
		));

		if ($this->ui->getWidget('provstate_other')->visible &&
			$this->address->provstate === null) {
			$this->ui->getWidget('provstate')->value = 'other';
		}
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$provstate = $this->ui->getWidget('provstate');
		$provstate_other_index = count($provstate->options);
		$id = 'account_address';
		return sprintf("var %s_obj = new StoreAccountAddressPage('%s', %s);",
			$id, $id, $provstate_other_index);
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
		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-account-address-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
