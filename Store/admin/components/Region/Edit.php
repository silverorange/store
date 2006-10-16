<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';

/**
 * Edit page for Regions
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreRegionEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		$countries = SwatDB::getOptionArray($this->app->db, 'Country', 'title',
			'text:id', 'title');
	
		$region_billing_country_list =
			$this->ui->getWidget('region_billing_country');

		$region_billing_country_list->options = $countries;

		$region_shipping_country_list = 
			$this->ui->getWidget('region_shipping_country');

		$region_shipping_country_list->options = $countries;

		$this->fields = array('title');
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('title'));

		if ($this->id === null)
			$this->id = SwatDB::insertRow($this->app->db, 'Region',
				$this->fields, $values);
		else
			SwatDB::updateRow($this->app->db, 'Region', $this->fields, $values,
				'id', $this->id);

		$region_billing_country_list = 
			$this->ui->getWidget('region_billing_country');

		SwatDB::updateBinding($this->app->db, 'RegionBillingCountryBinding', 
			'region', $this->id, 'text:country',
			$region_billing_country_list->values, 'Country', 'text:id');

		$region_shipping_country_list = 
			$this->ui->getWidget('region_shipping_country');

		SwatDB::updateBinding($this->app->db, 'RegionShippingCountryBinding', 
			'region', $this->id, 'text:country',
			$region_shipping_country_list->values, 'Country', 'text:id');

		$msg = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($msg);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Region', 
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_('Region with id ‘%s’ not found.'), $this->id));

		$this->ui->setValues(get_object_vars($row));

		$region_billing_country_list = 
			$this->ui->getWidget('region_billing_country');

		$region_billing_country_list->values = SwatDB::queryColumn(
			$this->app->db, 'RegionBillingCountryBinding', 'text:country',
			'region', $this->id);

		$region_shipping_country_list = 
			$this->ui->getWidget('region_shipping_country');

		$region_shipping_country_list->values = SwatDB::queryColumn(
			$this->app->db, 'RegionShippingCountryBinding', 'text:country',
			'region', $this->id);
	}

	// }}}
}

?>
