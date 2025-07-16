<?php

/**
 * Edit page for item minimum quantity groups.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemMinimumQuantityGroupEdit extends AdminDBEdit
{
    protected $item_group;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML(__DIR__ . '/edit.xml');
        $this->initItemMinimumQuantityGroup();

        if ($this->id === null) {
            $this->ui->getWidget('shortname_field')->visible = false;
        }
    }

    protected function initItemMinimumQuantityGroup()
    {
        $class_name = SwatDBClassMap::get(StoreItemMinimumQuantityGroup::class);
        $this->item_group = new $class_name();
        $this->item_group->setDatabase($this->app->db);

        if ($this->id !== null) {
            if (!$this->item_group->load($this->id)) {
                throw new AdminNotFoundException(
                    sprintf(
                        Store::_('Item group with an id "%s" not found'),
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
        $title = $this->ui->getWidget('title');

        if ($this->id === null && $shortname->value === null) {
            $new_shortname = $this->generateShortname($title->value);
            $shortname->value = $new_shortname;
        } elseif (!$this->validateShortname($shortname)) {
            $message = new SwatMessage(
                Store::_(
                    'Shortname already exists and must be unique.'
                ),
                'error'
            );

            $shortname->addMessage($message);
        }
    }

    protected function validateShortname($shortname)
    {
        $valid = true;

        $class_name = SwatDBClassMap::get(StoreItemMinimumQuantityGroup::class);
        $item_group = new $class_name();
        $item_group->setDatabase($this->app->db);

        if ($item_group->loadByShortname($shortname)) {
            if ($item_group->id !== $this->item_group->id) {
                $valid = false;
            }
        }

        return $valid;
    }

    protected function saveDBData(): void
    {
        $this->updateItemMinimumQuantityGroup();
        $this->item_group->save();

        $message = new SwatMessage(
            sprintf(
                Store::_('“%s” has been saved.'),
                $this->item_group->title
            )
        );

        $this->app->messages->add($message);
    }

    protected function updateItemMinimumQuantityGroup()
    {
        $values = $this->ui->getValues([
            'title', 'shortname', 'minimum_quantity', 'description',
            'part_unit', 'part_unit_plural']);

        $this->item_group->title = $values['title'];
        $this->item_group->description = $values['description'];
        $this->item_group->shortname = $values['shortname'];
        $this->item_group->minimum_quantity = $values['minimum_quantity'];
        $this->item_group->part_unit = $values['part_unit'];
        $this->item_group->part_unit_plural = $values['part_unit_plural'];
    }

    // build phase

    protected function loadDBData()
    {
        $this->ui->setValues($this->item_group->getAttributes());
    }
}
