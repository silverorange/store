<?php

require_once 'Store/pages/StoreCheckoutEditPage.php';
require_once 'YUI/YUI.php';

/**
 * Shipping address edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCheckoutShippingAddressPage extends StoreCheckoutEditPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->ui_xml = 'Store/pages/checkout-shipping-address.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		parent::initCommon();

		// default country flydown to the country of the current locale
		$country_flydown = $this->ui->getWidget('shipping_address_country');
		$country_flydown->value = $this->app->getCountry();
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
			foreach ($controls as $control)
				$control->required = false;
		}
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
		if ($this->ui->getWidget('form')->hasMessage())
			return;

		$this->saveDataToSession();
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$address_list = $this->ui->getWidget('shipping_address_list');

		if ($address_list->value === null || $address_list->value === 'new') {
			$order_address = new StoreOrderAddress();

			$order_address->fullname =
				$this->ui->getWidget('shipping_address_fullname')->value;

			$order_address->line1 = 
				$this->ui->getWidget('shipping_address_line1')->value;

			$order_address->line2 =
				$this->ui->getWidget('shipping_address_line2')->value;

			$order_address->city =
				$this->ui->getWidget('shipping_address_city')->value;

			$order_address->provstate =
				$this->ui->getWidget('shipping_address_provstate')->value;

			$order_address->provstate_other =
				$this->ui->getWidget('shipping_address_provstate_other')->value;

			$order_address->postal_code =
				$this->ui->getWidget('shipping_address_postalcode')->value;

			$order_address->country =
				$this->ui->getWidget('shipping_address_country')->value;

		} elseif ($address_list->value === 'billing') {
			$order_address = $this->app->session->order->billing_address;
		} else {
			$address_id = intval($address_list->value);

			$account_address = 
				$this->app->session->account->addresses->getByIndex(
				$address_id);

			if (!($account_address instanceof StoreAccountAddress))
				throw new StoreException('Account address not found.  '.
					"Address with id ‘$address_id’ not found.");

			$order_address = new StoreOrderAddress();
			$order_address->copyFrom($account_address);
		}

		$this->app->session->order->shipping_address = $order_address;
	}

	// }}}
	// {{{ protected function setupPostalCode()

	protected function setupPostalCode()
	{
		// set provsate and country on postal code entry
		$postal_code = $this->ui->getWidget('shipping_address_postalcode');
		$country = $this->ui->getWidget('shipping_address_country');
		$provstate = $this->ui->getWidget('shipping_address_provstate');

		$country->process();
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

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		$this->layout->addHtmlHeadEntry(
			new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-address-page.css',
			Store::PACKAGE_ID));

		$this->buildList();
		$this->buildForm();

		if (!$this->ui->getWidget('form')->isProcessed())
			$this->loadDataFromSession();
	}

	// }}}
	// {{{ public function postBuildCommon()

	public function postBuildCommon()
	{
		$address_list = $this->ui->getWidget('shipping_address_list');

		if ($address_list->visible) {
			$yui = new YUI(array('dom', 'event'));
			$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/store/javascript/store-checkout-page.js',
				Store::PACKAGE_ID));

			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/store/javascript/store-checkout-shipping-address.js',
				Store::PACKAGE_ID));

			$this->layout->startCapture('content');
			$this->displayJavaScript();
			$this->layout->endCapture();
		}
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;

		if ($order->shipping_address !== null) {
			if ($order->shipping_address->getAccountAddressId() === null &&
				$order->shipping_address !== $order->billing_address) {

				$this->ui->getWidget('shipping_address_fullname')->value =
					$order->shipping_address->fullname;

				$this->ui->getWidget('shipping_address_line1')->value =
					$order->shipping_address->line1;

				$this->ui->getWidget('shipping_address_line2')->value =
					$order->shipping_address->line2;

				$this->ui->getWidget('shipping_address_city')->value =
					$order->shipping_address->city;

				$this->ui->getWidget('shipping_address_provstate')->value =
					$order->shipping_address->getInternalValue('provstate');

				$this->ui->getWidget('shipping_address_provstate_other')->value =
					$order->shipping_address->provstate_other;

				$this->ui->getWidget('shipping_address_postalcode')->value =
					$order->shipping_address->postal_code;

				$this->ui->getWidget('shipping_address_country')->value =
					$order->shipping_address->getInternalValue('country');

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
	// {{{ protected function buildList()

	protected function buildList()
	{
		$address_list = $this->ui->getWidget('shipping_address_list');

		$span = '<span class="add-new">%s</span>';

		// TODO: it is possible to select a billing address that is not
		// shippable and then select "ship to billing address".
		if ($this->app->session->checkout_with_account) {
			$address_list->addOption('new', 
				sprintf($span, Store::_('Add a New Address')), 'text/xml');

			$address_list->addOption('billing',
				sprintf($span, Store::_('Ship to Billing Address')),
				'text/xml');

		} else {
			$address_list->addOption('billing',
				sprintf($span, Store::_('Ship to Billing Address')),
				'text/xml');

			$address_list->addOption('new',
				sprintf($span, Store::_('Ship to a Different Address')),
				'text/xml');
		}

		if ($this->app->session->isLoggedIn())
			$this->buildAccountShippingAddresses($address_list);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$provstate_flydown = $this->ui->getWidget('shipping_address_provstate');
		$provstate_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'ProvState', 'title', 'id', 'title',
				sprintf('country in (select country from
				RegionShippingCountryBinding where region = %s)',
				$this->app->db->quote($this->app->getRegion()->id, 'integer'))));

		$provstate_other = $this->ui->getWidget('shipping_address_provstate_other');
		if ($provstate_other->visible) {
			$provstate_flydown->addDivider();
			$option = new SwatOption('other', 'Other…');
			$provstate_flydown->addOption($option);
		}

		$country_flydown = $this->ui->getWidget('shipping_address_country');
		$country_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'Country', 'title', 'id', 'title',
				sprintf('id in (select country from RegionShippingCountryBinding
				where region = %s)',
				$this->app->db->quote($this->app->getRegion()->id, 'integer'))));
	}

	// }}}
	// {{{ protected function buildAccountShippingAddresses()

	protected function buildAccountShippingAddresses(
		SwatOptionControl $address_list)
	{
		$shipping_country_ids = array();
		foreach ($this->app->getRegion()->shipping_countries as $country)
			$shipping_country_ids[] = $country->id;

		foreach ($this->app->session->account->addresses as $address) {
			if (in_array($address->getInternalValue('country'),
				$shipping_country_ids)) {

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
	// {{{ protected function displayJavaScript()

	protected function displayJavaScript()
	{
		$id = 'checkout_shipping_address';
		echo '<script type="text/javascript">'."\n";
		printf("var %s_obj = new StoreCheckoutShippingAddress('%s');\n",
			$id, $id);

		echo '</script>';
	}

	// }}}
}

?>
