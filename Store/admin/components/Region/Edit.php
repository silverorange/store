<?php

/**
 * Edit page for Regions.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreRegionEdit extends AdminDBEdit
{
    protected $fields;
    protected $region;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->initRegion();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML(__DIR__ . '/edit.xml');

        $countries = SwatDB::getOptionArray(
            $this->app->db,
            'Country',
            'title',
            'text:id',
            'title'
        );

        $region_billing_country_list =
            $this->ui->getWidget('region_billing_country');

        $region_billing_country_list->addOptionsByArray($countries);

        $region_shipping_country_list =
            $this->ui->getWidget('region_shipping_country');

        $region_shipping_country_list->addOptionsByArray($countries);

        $this->fields = ['title'];
    }

    protected function initRegion()
    {
        $class_name = SwatDBClassMap::get(StoreRegion::class);
        $this->region = new $class_name();
        $this->region->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->region->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(
                        Admin::_('Region with an id "%s" not found.'),
                        $this->id
                    )
                );
            }
        }
    }

    // process phase

    protected function saveDBData(): void
    {
        $values = $this->ui->getValues(['title']);
        $this->region->title = $values['title'];
        $this->region->save();

        $region_billing_country_list =
            $this->ui->getWidget('region_billing_country');

        SwatDB::updateBinding(
            $this->app->db,
            'RegionBillingCountryBinding',
            'region',
            $this->region->id,
            'text:country',
            $region_billing_country_list->values,
            'Country',
            'text:id'
        );

        $region_shipping_country_list =
            $this->ui->getWidget('region_shipping_country');

        SwatDB::updateBinding(
            $this->app->db,
            'RegionShippingCountryBinding',
            'region',
            $this->region->id,
            'text:country',
            $region_shipping_country_list->values,
            'Country',
            'text:id'
        );

        $message = new SwatMessage(
            sprintf(Store::_('“%s” has been saved.'), $values['title'])
        );

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // build phase

    protected function loadDBData()
    {
        $this->ui->setValues($this->region->getAttributes());

        $region_billing_country_list =
            $this->ui->getWidget('region_billing_country');

        $region_billing_country_list->values = SwatDB::queryColumn(
            $this->app->db,
            'RegionBillingCountryBinding',
            'text:country',
            'region',
            $this->id
        );

        $region_shipping_country_list =
            $this->ui->getWidget('region_shipping_country');

        $region_shipping_country_list->values = SwatDB::queryColumn(
            $this->app->db,
            'RegionShippingCountryBinding',
            'text:country',
            'region',
            $this->id
        );
    }
}
