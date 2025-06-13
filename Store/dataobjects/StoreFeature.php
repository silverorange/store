<?php

/**
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeature extends SwatDBDataObject
{
    // {{{ public properties

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $shortname;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $link;

    /**
     * @var SwatDate
     */
    public $start_date;

    /**
     * @var SwatDate
     */
    public $end_date;

    /**
     * not null default true,.
     *
     * @var bool
     */
    public $enabled;

    /**
     * not null,.
     *
     * @var int
     */
    public $display_slot;

    /**
     * not null,.
     *
     * @var int
     */
    public $priority;

    // }}}
    // {{{ public function isActive()

    /**
     * Checks if this feature is currently active.
     *
     * @param mixed|null $date
     *
     * @return bool true if this feature is active and false if it is not
     */
    public function isActive($date = null)
    {
        if ($date === null) {
            $date = new SwatDate();
        }

        $date->toUTC();

        return
            $this->enabled
            && ($this->start_date === null
                || SwatDate::compare($date, $this->start_date) >= 0)
            && ($this->end_date === null
                || SwatDate::compare($date, $this->end_date) <= 0);
    }

    // }}}
    // {{{ protected function init()

    protected function init()
    {
        $this->table = 'Feature';
        $this->id_field = 'integer:id';

        $this->registerDateProperty('start_date');
        $this->registerDateProperty('end_date');
        $this->registerInternalProperty(
            'region',
            SwatDBClassMap::get('StoreRegion')
        );

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get('SiteInstance')
        );
    }

    // }}}
    // {{{ protected function getSerializableSubDataObjects()

    protected function getSerializableSubDataObjects()
    {
        return array_merge(
            parent::getSerializableSubDataObjects(),
            ['region', 'instance']
        );
    }

    // }}}
}
