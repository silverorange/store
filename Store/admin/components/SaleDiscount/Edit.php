<?php

/**
 * Edit page for SaleDiscount.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSaleDiscountEdit extends AdminDBEdit
{
    protected $sale_discount;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());

        $this->initSaleDiscount();
    }

    protected function initSaleDiscount()
    {
        $class_name = SwatDBClassMap::get(StoreSaleDiscount::class);
        $this->sale_discount = new $class_name();
        $this->sale_discount->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->sale_discount->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(
                        Store::_('Sale discount with id “%s” not found.'),
                        $this->id
                    )
                );
            }
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/edit.xml';
    }

    // process phase

    protected function validate(): void
    {
        $shortname = $this->ui->getWidget('shortname')->value;

        if ($this->id === null && $shortname === null) {
            $shortname = $this->generateShortname(
                $this->ui->getWidget('title')->value,
                $this->id
            );

            $this->ui->getWidget('shortname')->value = $shortname;
        } elseif (!$this->validateShortname($shortname, $this->id)) {
            $message = new SwatMessage(
                Store::_('Shortname already exists and must be unique.'),
                'error'
            );

            $this->ui->getWidget('shortname')->addMessage($message);
        }
    }

    protected function validateShortname($shortname)
    {
        $sql = 'select shortname from SaleDiscount
				where shortname = %s and id %s %s';

        $sql = sprintf(
            $sql,
            $this->app->db->quote($shortname, 'text'),
            SwatDB::equalityOperator($this->id, true),
            $this->app->db->quote($this->id, 'integer')
        );

        $query = SwatDB::query($this->app->db, $sql);

        return count($query) == 0;
    }

    protected function saveDBData(): void
    {
        $this->updateSaleDiscount();
        $this->sale_discount->save();

        $message = new SwatMessage(
            sprintf(
                Store::_('“%s” has been saved.'),
                $this->sale_discount->title
            )
        );

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function updateSaleDiscount()
    {
        $values = $this->ui->getValues([
            'title',
            'discount_percentage',
            'start_date',
            'end_date',
            'shortname']);

        if ($values['start_date'] !== null) {
            $values['start_date']->setTZ($this->app->default_time_zone);
            $values['start_date']->toUTC();
        }

        if ($values['end_date'] !== null) {
            $values['end_date']->setTZ($this->app->default_time_zone);
            $values['end_date']->toUTC();
        }

        $this->sale_discount->title = $values['title'];
        $this->sale_discount->shortname = $values['shortname'];
        $this->sale_discount->start_date = $values['start_date'];
        $this->sale_discount->end_date = $values['end_date'];
        $this->sale_discount->discount_percentage =
            $values['discount_percentage'];
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        if ($this->id === null) {
            $this->ui->getWidget('shortname_field')->visible = false;
        }
    }

    protected function loadDBData()
    {
        $this->ui->setValues($this->sale_discount->getAttributes());

        $start_date = $this->ui->getWidget('start_date');
        $end_date = $this->ui->getWidget('end_date');

        if ($start_date->value !== null) {
            $start_date->value->convertTZ($this->app->default_time_zone);
        }

        if ($end_date->value !== null) {
            $end_date->value->convertTZ($this->app->default_time_zone);
        }
    }
}
