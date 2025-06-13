<?php

/**
 * Edit page for Shipping Types.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeEdit extends AdminDBEdit
{
    /**
     * @var VanBourgondienShippingType
     */
    protected $shipping_type;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->initShippingType();

        $this->ui->loadFromXML($this->getUiXml());
    }

    private function initShippingType()
    {
        $class_name = SwatDBClassMap::get('StoreShippingType');
        $this->shipping_type = new $class_name();
        $this->shipping_type->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->shipping_type->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(Store::_(
                        'Shipping Type with id ‘%s’ not found.'
                    ), $this->id)
                );
            }
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/edit.xml';
    }

    // process phase

    protected function updateShippingType()
    {
        $values = $this->ui->getValues([
            'title',
            'shortname',
            'note',
        ]);

        $this->shipping_type->title = $values['title'];
        $this->shipping_type->shortname = $values['shortname'];
        $this->shipping_type->note = $values['note'];
    }

    protected function saveDBData(): void
    {
        $this->updateShippingType();
        $this->shipping_type->save();

        $message = new SwatMessage(sprintf(
            Store::_('Shipping Type “%s” has been saved.'),
            $this->shipping_type->title
        ));

        $this->app->messages->add($message);
    }

    // build phase

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $final_entry = $this->navbar->popEntry();

        if ($this->id !== null) {
            $this->navbar->addEntry(new SwatNavBarEntry(
                Store::_('Details'),
                sprintf('ShippingType/Details?id=%s', $this->id)
            ));
        }

        $this->navbar->addEntry($final_entry);
    }

    protected function loadDBData()
    {
        $this->ui->setValues($this->shipping_type->getAttributes());
    }
}
