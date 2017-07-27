<?php

/**
 * Shipping address edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutShippingAddressPage extends StoreCheckoutAddressPage
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
		return __DIR__.'/checkout-shipping-address.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();

		$this->initForm();

		// default country flydown to the country of the current locale
		$country_flydown = $this->ui->getWidget('shipping_address_country');
		$country_flydown->value = $this->app->getCountry();
	}

	// }}}
	// {{{ protected function initForm()

	protected function initForm()
	{
		$country_sql = sprintf(
			'select id, title from Country
			where id in (
				select country from RegionShippingCountryBinding
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
				select provstate from RegionShippingProvStateBinding
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

		$country_flydown = $this->ui->getWidget('shipping_address_country');
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

		$provstate_flydown = $this->ui->getWidget('shipping_address_provstate');
		$provstate_flydown->data = $data;
		$provstate_flydown->setCountryFlydown($country_flydown);
	}

	// }}}

	// process phase
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
		$address_list = $this->ui->getWidget('shipping_address_list');
		$address_list->process();

		if ($address_list->value === null || $address_list->value === 'new') {
			if ($this->ui->getWidget('form')->isSubmitted())
				$this->setupPostalCode();
		} else {
			$container = $this->ui->getWidget('shipping_address_form');
			$controls = $container->getDescendants('SwatInputControl');
			foreach ($controls as $control) {
				$control->required = false;
			}
		}
	}

	// }}}
	// {{{ protected function getCountry()

	protected function getCountry()
	{
		if (!($this->country instanceof StoreCountry)) {
			$country_widget = $this->ui->getWidget('shipping_address_country');
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
	// {{{ protected function setupPostalCode()

	protected function setupPostalCode()
	{
		// set provsate and country on postal code entry
		$country     = $this->getCountry();
		$postal_code = $this->ui->getWidget('shipping_address_postalcode');
		$provstate   = $this->ui->getWidget('shipping_address_provstate');

		if ($country->id === null) {
			return;
		}

		$provstate->process();

		if ($provstate->provstate_id !== null) {
			$sql = sprintf('select abbreviation from ProvState where id = %s',
			$this->app->db->quote($provstate->provstate_id));

			$provstate_abbreviation = SwatDB::queryOne($this->app->db, $sql);
			$postal_code->country = $country->id;
			$postal_code->provstate = $provstate_abbreviation;
		}

		if (!$country->has_postal_code) {
			$postal_code->required = false;
		}
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$address = $this->getAddress();

		if ($this->verified_address !== null)
			$address->copyFrom($this->verified_address);

		$this->app->session->order->shipping_address = $address;
	}

	// }}}
	// {{{ protected function validateAddress()

	protected function validateAddress()
	{
		$this->validateAddressCountry();
		$this->validateAddressProvState();
		parent::validateAddress();
	}

	// }}}
	// {{{ protected function shouldVerifyAddress()

	protected function shouldVerifyAddress()
	{
		$verify = parent::shouldVerifyAddress();

		$address = $this->getAddress();
		if ($address === $this->app->session->order->billing_address)
			$verify = false;

		return $verify;
	}

	// }}}
	// {{{ protected function validateAddressCountry()

	protected function validateAddressCountry()
	{
		$address = $this->getAddress();

		$shipping_country_ids = array();
		foreach ($this->app->getRegion()->shipping_countries as $country)
			$shipping_country_ids[] = $country->id;

		if (!in_array($address->getInternalValue('country'),
			$shipping_country_ids)) {
			$field = $this->ui->getWidget('shipping_address_list_field');
			$field->addMessage(new SwatMessage(sprintf(Store::_('Orders can '.
				'not be shipped to %s. Please select a different shipping '.
				'address or enter a new shipping address.'),
				$address->country->title)));
		}
	}

	// }}}
	// {{{ protected function validateAddressProvState()

	protected function validateAddressProvState()
	{
		$address = $this->getAddress();
		$shipping_provstate = $address->getInternalValue('provstate');

		/*
		 * If provstate is null, it means it's either not required, or
		 * provstate_other is set. In either case, we don't need to check
		 * against valid provstates.
		 */
		if ($shipping_provstate === null) {
			return;
		}

		$shipping_provstate_ids = array();
		foreach ($this->app->getRegion()->shipping_provstates as $provstate) {
			$shipping_provstate_ids[] = $provstate->id;
		}

		if (!in_array($shipping_provstate, $shipping_provstate_ids)) {
			$field = $this->ui->getWidget('shipping_address_list_field');
			$field->addMessage(
				new SwatMessage(
					sprintf(
						Store::_(
							'Orders can not be shipped to %s. Please select '.
							'a different shipping address or enter a new '.
							'shipping address.'
						),
						$address->provstate->title
					)
				)
			);
		}
	}

	// }}}
	// {{{ protected function getAddress()

	protected function getAddress()
	{
		if ($this->address instanceof StoreOrderAddress)
			return $this->address;

		$address_list = $this->ui->getWidget('shipping_address_list');
		$class_name = SwatDBClassMap::get('StoreOrderAddress');
		$address = new $class_name();

		if ($address_list->value === null || $address_list->value === 'new') {
			$address->fullname =
				$this->ui->getWidget('shipping_address_fullname')->value;

			$address->company =
				$this->ui->getWidget('shipping_address_company')->value;

			$address->line1 =
				$this->ui->getWidget('shipping_address_line1')->value;

			$address->line2 =
				$this->ui->getWidget('shipping_address_line2')->value;

			$address->city =
				$this->ui->getWidget('shipping_address_city')->value;

			$provstate = $this->ui->getWidget('shipping_address_provstate');
			$address->provstate = $provstate->provstate_id;
			$address->provstate_other = $provstate->provstate_other;

			$address->postal_code =
				$this->ui->getWidget('shipping_address_postalcode')->value;

			$address->country =
				$this->ui->getWidget('shipping_address_country')->value;

			$address->phone =
				$this->ui->getWidget('shipping_address_phone')->value;

		} elseif ($address_list->value === 'billing') {
			$address = $this->app->session->order->billing_address;
		} else {
			$address_id = intval($address_list->value);

			/* If we are already using the selected address for billing, then
			 * use the existing OrderAddress, else copy into the new one.
			 */
			$other_address = $this->app->session->order->billing_address;
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
		$fields = array(
			'fullname'    => 'shipping_address_fullname',
			'line1'       => 'shipping_address_line1',
			'city'        => 'shipping_address_city',
			'provstate'   => 'shipping_address_provstate',
			'phone'       => 'shipping_address_phone',
		);

		if ($this->getCountry()->has_postal_code) {
			$fields['postal_code'] = 'shipping_address_postalcode';
		}

		return $fields;
	}

	// }}}

	// build phase
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;

		if (!$order->shipping_address instanceof StoreOrderAddress) {
			$default_address = $this->getDefaultShippingAddress();
			if ($default_address instanceof StoreAddress) {
				$this->ui->getWidget('shipping_address_list')->value =
					$default_address->id;
			}
		} else {
			if ($order->shipping_address->getAccountAddressId() === null &&
				$order->shipping_address !== $order->billing_address) {

				$this->ui->getWidget('shipping_address_fullname')->value =
					$order->shipping_address->fullname;

				$this->ui->getWidget('shipping_address_company')->value =
					$order->shipping_address->company;

				$this->ui->getWidget('shipping_address_line1')->value =
					$order->shipping_address->line1;

				$this->ui->getWidget('shipping_address_line2')->value =
					$order->shipping_address->line2;

				$this->ui->getWidget('shipping_address_city')->value =
					$order->shipping_address->city;

				$provstate = $this->ui->getWidget('shipping_address_provstate');
				$provstate->provstate_id =
					$order->shipping_address->getInternalValue('provstate');

				$provstate->provstate_other =
					$order->shipping_address->provstate_other;

				$this->ui->getWidget('shipping_address_postalcode')->value =
					$order->shipping_address->postal_code;

				$this->ui->getWidget('shipping_address_country')->value =
					$order->shipping_address->getInternalValue('country');

				$this->ui->getWidget('shipping_address_phone')->value =
					$order->shipping_address->phone;

				$this->ui->getWidget('shipping_address_list')->value = 'new';

			} else {
				// compare references since these are not saved yet
				if ($order->billing_address === $order->shipping_address) {
					$this->ui->getWidget('shipping_address_list')->value =
						'billing';

				} else {
					$this->ui->getWidget('shipping_address_list')->value =
						$order->shipping_address->getAccountAddressId();
				}
			}
		}
	}

	// }}}
	// {{{ protected function getDefaultShippingAddress()

	protected function getDefaultShippingAddress()
	{
		$address = null;

		if ($this->app->session->isLoggedIn()) {
			$default_address =
				$this->app->session->account->getDefaultShippingAddress();

			if ($default_address !== null) {
				// only default to addresses that actually appear in the list
				$address_list = $this->ui->getWidget('shipping_address_list');
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
		$address_list = $this->ui->getWidget('shipping_address_list');

		$span = '<span class="add-new">%s</span>';

		$address_list->addOption(
			'billing',
			sprintf($span, Store::_('Ship to Billing Address')),
			'text/xml'
		);

		if ($this->app->session->isLoggedIn()) {
			$this->buildAccountShippingAddresses($address_list);

			$address_list->addOption(
				'new',
				sprintf($span, Store::_('Add a New Address')),
				'text/xml'
			);
		} else {
			$address_list->addOption(
				'new',
				sprintf($span, Store::_('Ship to a Different Address')),
				'text/xml'
			);
		}
	}

	// }}}
	// {{{ protected function buildAccountShippingAddresses()

	protected function buildAccountShippingAddresses(
		SwatOptionControl $address_list
	) {
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
		SwatContentBlock $content_block
	) {
		// TODO: pull parts of this up from Veseys
	}

	// }}}
	// {{{ protected function getAccountAddresses()

	protected function getAccountAddresses()
	{
		$shipping_country_ids =
			$this->app->getRegion()->shipping_countries->getIndexes();

		$shipping_provstate_ids =
			$this->app->getRegion()->shipping_provstates->getIndexes();

		// efficiently load country and provstate on account addresses
		$addresses = $this->app->session->account->addresses;

		$country_sql = sprintf(
			'select * from Country where id in (%%s) and id in (%s)',
			$this->app->db->datatype->implodeArray(
				$shipping_country_ids,
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
				$shipping_provstate_ids,
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

			if (in_array($country_id, $shipping_country_ids) &&
				($provstate_id === null ||
					in_array($provstate_id, $shipping_provstate_ids))) {

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
		$id = 'checkout_shipping_address';
		return sprintf(
			'var %s_obj = new StoreCheckoutShippingAddressPage(%s);',
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
			'packages/store/javascript/store-checkout-shipping-address-page.js'
		);
	}

	// }}}
}

?>
