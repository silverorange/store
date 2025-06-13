<?php

/**
 * Edit page for ProvStates.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProvStateEdit extends AdminDBEdit
{
    protected $prov_state;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->initProvState();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());

        $country_flydown = $this->ui->getWidget('country');
        $country_flydown->show_blank = false;
        $country_flydown->addOptionsByArray(SwatDB::getOptionArray(
            $this->app->db,
            'Country',
            'text:title',
            'integer:id',
            'title'
        ));
    }

    protected function initProvState()
    {
        $class_name = SwatDBClassMap::get('StoreProvState');
        $this->prov_state = new $class_name();
        $this->prov_state->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->prov_state->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(Admin::_('Province/State with an id "%s"' .
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

    protected function saveDBData(): void
    {
        $this->updateProvState();
        $this->prov_state->save();

        $message = new SwatMessage(sprintf(
            Store::_('“%s” has been saved.'),
            $this->prov_state->title
        ));

        $this->app->messages->add($message);
    }

    protected function updateProvState()
    {
        $values = $this->ui->getValues([
            'title',
            'abbreviation',
            'country',
        ]);

        $this->prov_state->title = $values['title'];
        $this->prov_state->abbreviation = $values['abbreviation'];
        $this->prov_state->country = $values['country'];
    }

    // build phase

    protected function loadDBData()
    {
        $this->ui->setValues($this->prov_state->getAttributes());
        $this->ui->getWidget('country')->value = $this->prov_state->country->id;
    }
}
