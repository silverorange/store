<?php

/**
 * Edit page for Locale.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreLocaleEdit extends AdminDBEdit
{
    protected $locale;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML(__DIR__ . '/edit.xml');

        $this->initLocale();

        $id_flydown = $this->ui->getWidget('region');
        $id_flydown->show_blank = false;
        $id_flydown->addOptionsByArray(SwatDB::getOptionArray(
            $this->app->db,
            'Region',
            'title',
            'id',
            'title'
        ));
    }

    protected function initLocale()
    {
        $class_name = SwatDBClassMap::get(StoreLocale::class);
        $this->locale = new $class_name();
        $this->locale->setDatabase($this->app->db);
    }

    // process phase

    protected function validate(): void
    {
        $localeid = $this->ui->getWidget('id');

        if (preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $localeid->value) !== 1) {
            $localeid->addMessage(
                new SwatMessage(
                    Store::_('Invalid locale identifier.'),
                    'error'
                )
            );
        }
    }

    protected function saveDBData(): void
    {
        $this->updateLocale();
        $this->locale->save();

        $message = new SwatMessage(
            sprintf(Store::_('“%s” has been saved.'), $this->locale->id)
        );

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function updateLocale()
    {
        $values = $this->ui->getValues(['id', 'region']);

        $this->locale->id = $values['id'];
        $this->locale->region = $values['region'];
    }

    // build phase

    protected function loadDBData()
    {
        $message = new SwatMessage('Locales can not be edited.', 'warning');

        $this->app->messages->add($message);
        $this->app->relocate('Locale');
    }
}
