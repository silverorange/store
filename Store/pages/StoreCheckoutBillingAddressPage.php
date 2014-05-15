<?php

require_once 'Store/pages/StoreCheckoutAddressPage.php';
require_once 'Swat/SwatYUI.php';

/**
 * Billing address edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2014 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutBillingAddressPage extends StoreCheckoutAddressPage
{
	// {{{ protected properties

	/**
	 * @var StoreCountry
	 */
	protected $country;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/pages/checkout-billing-address.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();

		$this->initForm();

		// default country flydown to the country of the current locale
		$country_flydown = $this->ui->getWidget('billing_address_country');
		$country_flydown->value = $this->app->getCountry();
	}

	// }}}
	// {{{ protected function initForm()

	protected function initForm()
	{
		$country_sql = sprintf(
			'select id, title from Country
			where id in (
				select country from RegionBillingCountryBinding
				where region = %s)
			and visible = %s
			order by title',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->app->db->quote(true, 'boolean')
		);

		$countries = SwatDB::query(
			$this->app->db,
			$country_sql,
			SwatDBClassMap::get('StoreCountryWrapper')
		);

		$provstate_where_clause = sprintf(
			'id in (
				select provstate from RegionBillingProvStateBinding
				where region = %s)',
			$this->app->db->quote($this->app->getRegion()->id, 'integer')
		);

		$provstates = $countries->loadAllSubRecordsets(
			'provstates',
			SwatDBClassMap::get('StoreProvStateWrapper'),
			'ProvState',
			'text:country',
			$provstate_where_clause,
			'title'
		);

		$country_flydown = $this->ui->getWidget('billing_address_country');
		$country_flydown->serialize_values = false;
		foreach ($countries as $country) {
			$country_flydown->addOption($country->id, $country->title);
		}

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

		$provstate_flydown = $this->ui->getWidget('billing_address_provstate');
		$provstate_flydown->data = $data;
		$provstate_flydown->setCountryFlydown($country_flydown);
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
	// {{{ protected function getCountry()

	protected function getCountry()
	{
		if (!($this->country instanceof StoreCountry)) {
			$country_widget = $this->ui->getWidget('billing_address_country');
			$country_widget->process();
			$country_id = $country_widget->value;

			$class_name = SwatDBClassMap::get('StoreCountry');
			$this->country = new $class_name();
			$this->country->setDatabase($this->app->db);
			$this->country->load($country_id);
		}

		return $this->country;
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$address = $this->getAddress();

		if ($this->verified_address !== null) {
			$address->copyFrom($this->verified_address);
		}

		// If we are currently shipping to the billing address,
		// change the shipping address too.
		$billing_address = $this->app->session->order->billing_address;
		$shipping_address = $this->app->session->order->shipping_address;
		if ($billing_address instanceof StoreOrderAddress &&
			$shipping_address instanceof StoreOrderAddress &&
			$shipping_address === $billing_address) {
				$this->app->session->order->shipping_address = $address;
		}

		$this->app->session->order->billing_address = $address;
	}

	// }}}
	// {{{ protected function setupPostalCode()

	protected function setupPostalCode()
	{
		// set provsate and country on postal code entry
		$country     = $this->getCountry();
		$postal_code = $this->ui->getWidget('billing_address_postalcode');
		$provstate   = $this->ui->getWidget('billing_address_provstate');

		if ($country->id === null) {
			return;
		}

		$provstate->process();

		if ($provstate->provstate_id !== null) {
			$sql = sprintf(
				'select abbreviation from ProvState where id = %s',
				$this->app->db->quote($provstate->provstate_id)
			);

			$provstate_abbreviation = SwatDB::queryOne($this->app->db, $sql);
			$postal_code->country = $country->id;
			$postal_code->provstate = $provstate_abbreviation;
		}

		if (!$country->has_postal_code) {
			$postal_code->required = false;
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

			$provstate = $this->ui->getWidget('billing_address_provstate');
			$address->provstate = $provstate->provstate_id;
			$address->provstate_other = $provstate->provstate_other;

			$address->postal_code =
				$this->ui->getWidget('billing_address_postalcode')->value;

			$address->country =
				$this->ui->getWidget('billing_address_country')->value;

			$address->phone =
				$this->ui->getWidget('billing_address_phone')->value;

		} else {
			$address_id = intval($address_list->value);

			// If we are already using the selected address for shipping, then
			// use the existing OrderAddress. Otherwise copy into the new one.
			$other_address = $this->app->session->order->shipping_address;
			if ($other_address instanceof StoreOrderAddress &&
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
		$fields = array(
			'fullname'  => 'billing_address_fullname',
			'line1'     => 'billing_address_line1',
			'city'      => 'billing_address_city',
			'provstate' => 'billing_address_provstate',
			'phone'     => 'billing_address_phone',
		);

		if ($this->getCountry()->has_postal_code) {
			$fields['postal_code'] = 'billing_address_postalcode';
		}

		return $fields;
	}

	// }}}

	// build phase
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;

		if ($order->billing_address === null) {

			if ($this->app->session->isLoggedIn()) {
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

				$provstate = $this->ui->getWidget('billing_address_provstate');
				$provstate->provstate_id =
					$order->billing_address->getInternalValue('provstate');

				$provstate->provstate_other =
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

		$content_block =
			$this->ui->getWidget('account_billing_address_region_message');

		if ($this->app->session->isLoggedIn()) {
			$this->buildAccountBillingAddresses($address_list);
			$this->buildAccountBillingAddressRegionMessage($content_block);
		}

		$address_list->addOption(
			'new',
			sprintf(
				'<span class="add-new">%s</span>',
				Store::_('Add a New Address')
			),
			'text/xml'
		);

		$address_list->visible           = (count($address_list->options) > 1);
		$address_list_container->visible = (count($address_list->options) > 1);
	}

	// }}}
	// {{{ protected function buildAccountBillingAddresses()

	protected function buildAccountBillingAddresses(
		SwatOptionControl $address_list)
	{
		foreach ($this->getAccountAddresses() as $address) {
			ob_start();
			$address->displayCondensed();
			$condensed_address = ob_get_clean();

			$address_list->addOption(
				$address->id,
				$condensed_address,
				'text/xml'
			);
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
	// {{{ protected function getAccountAddresses()

	protected function getAccountAddresses()
	{
		$billing_country_ids =
			$this->app->getRegion()->billing_countries->getIndexes();

		$billing_provstate_ids =
			$this->app->getRegion()->billing_provstates->getIndexes();

		// efficiently load country and provstate on account addresses
		$addresses = $this->app->session->account->addresses;

		$country_sql = sprintf(
			'select * from Country where id in (%%s) and id in (%s)',
			$this->app->db->datatype->implodeArray(
				$billing_country_ids,
				'text'
			)
		);

		$addresses->loadAllSubDataObjects(
			'country',
			$this->app->db,
			$country_sql,
			SwatDBClassMap::get('StoreCountryWrapper'),
			'text'
		);

		$provstate_sql = sprintf(
			'select * from ProvState where id in (%%s) and id in (%s)',
			$this->app->db->datatype->implodeArray(
				$billing_provstate_ids,
				'integer'
			)
		);

		$addresses->loadAllSubDataObjects(
			'provstate',
			$this->app->db,
			$provstate_sql,
			SwatDBClassMap::get('StoreProvStateWrapper')
		);

		$wrapper = SwatDBClassMap::get('StoreAccountAddressWrapper');
		$out_addresses = new $wrapper();

		// filter account addresses by country and provstate region binding
		foreach ($addresses as $address) {

			// still using internal values here because countries and provstates
			// provstate not in the region binding have not been efficiently
			// loaded
			$country_id   = $address->getInternalValue('country');
			$provstate_id = $address->getInternalValue('provstate');

			if (in_array($country_id, $billing_country_ids) &&
				($provstate_id === null ||
					in_array($provstate_id, $billing_provstate_ids))) {

				$out_addresses->add($address);
			}

		}

		$out_addresses->setDatabase($this->app->db);

		return $out_addresses;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$id = 'checkout_billing_address';
		return sprintf(
			'var %s_obj = new StoreCheckoutBillingAddressPage(%s);',
			$id,
			SwatString::quoteJavaScriptString($id)
		);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/store/javascript/store-checkout-billing-address-page.js'
		);
	}

	// }}}
}

?>
