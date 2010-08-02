<?php

require_once 'Store/pages/StoreCheckoutAddressPage.php';
require_once 'Swat/SwatYUI.php';

/**
 * Billing address edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutBillingAddressPage extends StoreCheckoutAddressPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-billing-address.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();

		// default country flydown to the country of the current locale
		$country_flydown = $this->ui->getWidget('billing_address_country');
		$country_flydown->value = $this->app->getCountry();
	}

	// }}}

	// process phase
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
		$address_list = $this->ui->getWidget('billing_address_list');
		$address_list->process();

		if ($address_list->value === null || $address_list->value === 'new') {
			if ($this->ui->getWidget('form')->isSubmitted())
				$this->setupPostalCode();
		} else {
			$container = $this->ui->getWidget('billing_address_form');
			$controls = $container->getDescendants('SwatInputControl');
			foreach ($controls as $control)
				$control->required = false;
		}
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$address = $this->getAddress();

		if ($this->verified_address !== null)
			$address->copyFrom($this->verified_address);

		/* If we are currently shipping to the billing address,
		 * change the shipping address too.
		 */
		if ($this->app->session->order->shipping_address ===
			$this->app->session->order->billing_address)
				$this->app->session->order->shipping_address = $address;

		$this->app->session->order->billing_address = $address;
	}

	// }}}
	// {{{ protected function setupPostalCode()

	protected function setupPostalCode()
	{
		// set provsate and country on postal code entry
		$postal_code = $this->ui->getWidget('billing_address_postalcode');
		$country = $this->ui->getWidget('billing_address_country');
		$provstate = $this->ui->getWidget('billing_address_provstate');

		$country->process();
		$provstate->country = $country->value;
		$provstate->setDatabase($this->app->db);
		$provstate->process();

		if ($provstate->value === 'other') {
			$provstate_other =
				$this->ui->getWidget('billing_address_provstate_other');

			$provstate_other->required = true;
			$provstate->value = null;
		}

		if ($provstate->value !== null) {
			$sql = sprintf('select abbreviation from ProvState where id = %s',
			$this->app->db->quote($provstate->value));

			$provstate_abbreviation = SwatDB::queryOne($this->app->db, $sql);
			$postal_code->country = $country->value;
			$postal_code->provstate = $provstate_abbreviation;
		}
	}

	// }}}
	// {{{ protected function getAddress()

	protected function getAddress()
	{
		if ($this->address instanceof StoreOrderAddress)
			return $this->address;

		$address_list = $this->ui->getWidget('billing_address_list');
		$class_name = SwatDBClassMap::get('StoreOrderAddress');
		$address = new $class_name();

		if ($address_list->value === null || $address_list->value === 'new') {
			$address->fullname =
				$this->ui->getWidget('billing_address_fullname')->value;

			$address->company =
				$this->ui->getWidget('billing_address_company')->value;

			$address->line1 =
				$this->ui->getWidget('billing_address_line1')->value;

			$address->line2 =
				$this->ui->getWidget('billing_address_line2')->value;

			$address->city =
				$this->ui->getWidget('billing_address_city')->value;

			$address->provstate =
				$this->ui->getWidget('billing_address_provstate')->value;

			$address->provstate_other =
				$this->ui->getWidget('billing_address_provstate_other')->value;

			$address->postal_code =
				$this->ui->getWidget('billing_address_postalcode')->value;

			$address->country =
				$this->ui->getWidget('billing_address_country')->value;

			$address->phone =
				$this->ui->getWidget('billing_address_phone')->value;

		} else {
			$address_id = intval($address_list->value);

			/* If we are already using the selected address for shipping, then
			 * use the existing OrderAddress, else copy into the new one.
			 */
			$other_address = $this->app->session->order->shipping_address;
			if ($other_address !== null &&
				$other_address->getAccountAddressId() == $address_id) {
					$address = $other_address;
			} else {
				$account_address =
					$this->app->session->account->addresses->getByIndex(
					$address_id);

				if (!($account_address instanceof StoreAccountAddress))
					throw new StoreException('Account address not found. '.
						"Address with id ‘{$address_id}’ not found.");

				$address->copyFrom($account_address);
			}
		}

		$this->address = $address;

		return $this->address;
	}

	// }}}
	// {{{ protected function getRequiredAddressFields()

	protected function getRequiredAddressFields(StoreOrderAddress $address)
	{
		return array(
			'fullname'    => 'shipping_address_fullname',
			'line1'       => 'shipping_address_line1',
			'city'        => 'shipping_address_city',
			'provstate'   => 'shipping_address_provstate',
			'postal_code' => 'shipping_address_postalcode',
			'phone'       => 'shipping_address_phone',
		);
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
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;

		if ($order->billing_address === null) {
			$this->ui->getWidget('billing_address_fullname')->value =
				$this->app->session->account->fullname;

			$this->ui->getWidget('billing_address_phone')->value =
				$this->app->session->account->phone;

			$this->ui->getWidget('billing_address_company')->value =
				$this->app->session->account->company;

			$default_address = $this->getDefaultBillingAddress();
			if ($default_address !== null) {
				$this->ui->getWidget('billing_address_list')->value =
					$default_address->id;
			}
		} else {
			if ($order->billing_address->getAccountAddressId() === null) {

				$this->ui->getWidget('billing_address_fullname')->value =
					$order->billing_address->fullname;

				$this->ui->getWidget('billing_address_company')->value =
					$order->billing_address->company;

				$this->ui->getWidget('billing_address_line1')->value =
					$order->billing_address->line1;

				$this->ui->getWidget('billing_address_line2')->value =
					$order->billing_address->line2;

				$this->ui->getWidget('billing_address_city')->value =
					$order->billing_address->city;

				$this->ui->getWidget('billing_address_provstate')->value =
					$order->billing_address->getInternalValue('provstate');

				$this->ui->getWidget('billing_address_provstate_other')->value =
					$order->billing_address->provstate_other;

				$this->ui->getWidget('billing_address_postalcode')->value =
					$order->billing_address->postal_code;

				$this->ui->getWidget('billing_address_country')->value =
					$order->billing_address->getInternalValue('country');

				$this->ui->getWidget('billing_address_phone')->value =
					$order->billing_address->phone;
			} else {
				$this->ui->getWidget('billing_address_list')->value =
					$order->billing_address->getAccountAddressId();
			}
		}
	}

	// }}}
	// {{{ protected function getDefaultBillingAddress()

	protected function getDefaultBillingAddress()
	{
		$address = null;

		if ($this->app->session->isLoggedIn()) {
			$default_address =
				$this->app->session->account->getDefaultBillingAddress();

			if ($default_address !== null) {
				// only default to addresses that actually appear in the list
				$address_list = $this->ui->getWidget('billing_address_list');
				$options =
					$address_list->getOptionsByValue($default_address->id);

				if (count($options) > 0)
					$address = $default_address;
			}
		}

		return $address;
	}

	// }}}
	// {{{ protected function buildList()

	protected function buildList()
	{
		$address_list = $this->ui->getWidget('billing_address_list');
		$address_list_container =
			$this->ui->getWidget('billing_address_list_container');

		$address_list->addOption('new', sprintf(
			'<span class="add-new">%s</span>', Store::_('Add a New Address')),
			'text/xml');

		$content_block =
			$this->ui->getWidget('account_billing_address_region_message');

		if ($this->app->session->isLoggedIn()) {
			$this->buildAccountBillingAddresses($address_list);
			$this->buildAccountBillingAddressRegionMessage($content_block);
		}

		$address_list->visible           = (count($address_list->options) > 1);
		$address_list_container->visible = (count($address_list->options) > 1);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$provstate_where = sprintf('country in (
					select country from RegionBillingCountryBinding
					where region = %1$s)
				and id in (
					select provstate from RegionBillingProvStateBinding
					where region = %1$s)',
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$provstate_flydown = $this->ui->getWidget('billing_address_provstate');
		$provstate_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'ProvState', 'title', 'id', 'title',
			$provstate_where));

		$provstate_other =
			$this->ui->getWidget('billing_address_provstate_other');

		if ($provstate_other->visible) {
			$provstate_flydown->addDivider();
			$option = new SwatOption('other', Store::_('Other…'));
			$provstate_flydown->addOption($option);
		}

		$country_where = sprintf('id in (
				select country from RegionBillingCountryBinding
				where region = %s)
			and visible = %s',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$country_flydown = $this->ui->getWidget('billing_address_country');
		$country_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'Country', 'title', 'id', 'title', $country_where));
	}

	// }}}
	// {{{ protected function buildAccountBillingAddresses()

	protected function buildAccountBillingAddresses(
		SwatOptionControl $address_list)
	{
		$billing_country_ids = array();
		foreach ($this->app->getRegion()->billing_countries as $country)
			$billing_country_ids[] = $country->id;

		foreach ($this->app->getRegion()->billing_provstates as $provstate)
			$billing_provstate_ids[] = $provstate->id;

		foreach ($this->app->session->account->addresses as $address) {
			if (in_array($address->getInternalValue('country'),
					$billing_country_ids) &&
				in_array($address->getInternalValue('provstate'),
					$billing_provstate_ids)) {

				ob_start();
				$address->displayCondensed();
				$condensed_address = ob_get_clean();

				$address_list->addOption($address->id, $condensed_address,
					'text/xml');
			}
		}
	}

	// }}}
	// {{{ protected function buildAccountBillingAddressRegionMessage()

	protected function buildAccountBillingAddressRegionMessage(
		SwatContentBlock $content_block)
	{
		// TODO: pull parts of this up from Veseys
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$provstate = $this->ui->getWidget('billing_address_provstate');
		$provstate_other_index = count($provstate->options);
		$id = 'checkout_billing_address';
		return sprintf(
			"var %s_obj = new StoreCheckoutBillingAddressPage('%s', %s);",
			$id, $id, $provstate_other_index);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-checkout-billing-address-page.js',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
