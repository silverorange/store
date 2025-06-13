<?php

/**
 * Edit page for Countries.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCountryEdit extends AdminDBEdit
{
    protected $fields;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());

        if ($this->id === null) {
            $this->fields = ['title', 'id', 'boolean:visible'];
        } else {
            $this->fields = ['title', 'boolean:visible'];
            $this->ui->getWidget('id_edit')->required = false;
            $this->ui->getWidget('id_edit')->visible = false;
            $this->ui->getWidget('id_non_edit')->visible = true;
            $this->ui->getWidget('id_non_edit')->content = $this->id;
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/edit.xml';
    }

    // process phase

    protected function saveDBData(): void
    {
        $values = $this->getUIValues();

        if ($this->id === null) {
            SwatDB::insertRow(
                $this->app->db,
                'Country',
                $this->fields,
                $values
            );
        } else {
            SwatDB::updateRow(
                $this->app->db,
                'Country',
                $this->fields,
                $values,
                'text:id',
                $this->id
            );
        }

        $message = new SwatMessage(
            sprintf(Store::_('“%s” has been saved.'), $values['title'])
        );

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function validate(): void
    {
        // validate country id
        if ($this->id === null) {
            $id = $this->ui->getWidget('id_edit')->getState();
            $sql = sprintf(
                'select count(id) from Country where id = %s',
                $this->app->db->quote($id, 'text')
            );

            $count = SwatDB::queryOne($this->app->db, $sql);

            if ($count > 0) {
                $message = new SwatMessage(
                    Store::_('<strong>Country Code</strong> already exists. ' .
                    'Country code must be unique for each country.'),
                    'error'
                );

                $message->content_type = 'text/xml';
                $this->ui->getWidget('id_edit')->addMessage($message);
            }
        }
    }

    protected function getUIValues()
    {
        if ($this->id === null) {
            $values['id'] = $this->ui->getWidget('id_edit')->value;
        }

        $values['title'] = $this->ui->getWidget('title')->value;
        $values['visible'] = $this->ui->getWidget('visible')->value;

        return $values;
    }

    // build phase

    protected function loadDBData()
    {
        $row = SwatDB::queryRowFromTable(
            $this->app->db,
            'Country',
            $this->fields,
            'text:id',
            $this->id
        );

        if ($row === null) {
            throw new AdminNotFoundException(
                sprintf(
                    Store::_('Country with id ‘%s’ not found.'),
                    $this->id
                )
            );
        }

        $this->ui->setValues(get_object_vars($row));
    }
}
