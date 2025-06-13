<?php

/**
 * Edit page for Attributes.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeTypeEdit extends AdminDBEdit
{
    /**
     * @var StoreAttributeType
     */
    private $attribute_type;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->initAttributeType();

        $this->ui->loadFromXML(__DIR__ . '/edit.xml');
    }

    protected function initAttributeType()
    {
        $class_name = SwatDBClassMap::get('StoreAttributeType');
        $this->attribute_type = new $class_name();
        $this->attribute_type->setDatabase($this->app->db);

        if ($this->id != null) {
            if (!$this->attribute_type->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(
                        Store::_('Attribute Type with id ‘%s’ not found.'),
                        $this->id
                    )
                );
            }
        }
    }

    // process phase

    protected function validate(): void
    {
        $shortname = $this->ui->getWidget('shortname');

        $class_name = SwatDBClassMap::get('StoreAttributeType');
        $attribute_type = new $class_name();
        $attribute_type->setDatabase($this->app->db);

        if ($attribute_type->loadFromShortname($shortname->value)) {
            if ($attribute_type->id !== $this->attribute_type->id) {
                $message = new SwatMessage(
                    Store::_('Shortname already exists and must be unique.')
                );

                $shortname->addMessage($message);
            }
        }
    }

    protected function saveDBData(): void
    {
        $this->updateAttributeType();
        $this->attribute_type->save();

        $message = new SwatMessage(
            sprintf(
                Store::_('Attribute Type “%s” has been saved.'),
                $this->attribute_type->shortname
            )
        );

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function updateAttributeType()
    {
        $values = $this->ui->getValues([
            'shortname',
        ]);

        $this->attribute_type->shortname = $values['shortname'];
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $form = $this->ui->getWidget('edit_frame');
        $form->subtitle = $this->attribute_type->shortname;
    }

    protected function loadDBData()
    {
        $this->ui->setValues($this->attribute_type->getAttributes());
    }
}
