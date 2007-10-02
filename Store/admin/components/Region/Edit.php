<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * Edit page for Regions
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreRegionEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;
	protected $region;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initRegion();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		$countries = SwatDB::getOptionArray($this->app->db, 'Country', 'title',
			'text:id', 'title');

		$region_billing_country_list =
			$this->ui->getWidget('region_billing_country');

		$region_billing_country_list->addOptionsByArray($countries);

		$region_shipping_country_list =
			$this->ui->getWidget('region_shipping_country');

		$region_shipping_country_list->addOptionsByArray($countries);

		$this->fields = array('title');
	}

	// }}}
	// {{{ protected function initRegion()

	protected function initRegion()
	{
		$this->region = new StoreRegion();
		$this->region->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->region->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(Admin::_('Region with an id "%s" not found.'),
						$this->id));
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('title'));
		$this->region->title = $values['title'];
		$this->region->save();

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

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $values['title']));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->region));

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
