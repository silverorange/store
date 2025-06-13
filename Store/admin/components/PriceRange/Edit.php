<?php

/**
 * Edit page for PriceRanges.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceRangeEdit extends AdminDBEdit
{
    // @var StorePriceRange
    protected $price_range;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->initPriceRange();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());
    }

    protected function initPriceRange()
    {
        $class_name = SwatDBClassMap::get('StorePriceRange');
        $this->price_range = new $class_name();
        $this->price_range->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->price_range->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(Admin::_('Price range with an id "%s"' .
                        ' not found'), $this->id)
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
        $start_price = floor($this->ui->getWidget('start_price')->value);
        $end_price = floor($this->ui->getWidget('end_price')->value);
        if ($start_price > $end_price && $end_price > 0) {
            $this->ui->getWidget('end_price')->addMessage(new SwatMessage(
                Store::_('End Price must be greater than start price.'),
                'error'
            ));
        }
    }

    protected function saveDBData(): void
    {
        $this->updatePriceRange();
        $this->price_range->save();

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('price_ranges');
        }

        $message = new SwatMessage(sprintf(
            Store::_('“%s” has been saved.'),
            $this->price_range->getTitle()
        ));

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function updatePriceRange()
    {
        $values = $this->ui->getValues([
            'start_price',
            'end_price',
            'original_price',
        ]);

        $this->price_range->start_price = ($values['start_price'] === null) ?
            null : floor($values['start_price']);

        $this->price_range->end_price = ($values['end_price'] === null) ?
            null : floor($values['end_price']);

        $this->price_range->original_price = $values['original_price'];
    }

    // build phase

    protected function loadDBData()
    {
        $this->ui->setValues($this->price_range->getAttributes());
    }
}
