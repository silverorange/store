<?php

/**
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property ?StoreRegion  $region
 * @property ?SiteInstance $instance
 */
class StoreFeature extends SwatDBDataObject
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var ?string
     */
    public $shortname;

    /**
     * @var ?string
     */
    public $title;

    /**
     * @var ?string
     */
    public $description;

    /**
     * @var ?string
     */
    public $link;

    /**
     * @var ?SwatDate
     */
    public $start_date;

    /**
     * @var ?SwatDate
     */
    public $end_date;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var int
     */
    public $display_slot;

    /**
     * @var int
     */
    public $priority;

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

    protected function init()
    {
        $this->table = 'Feature';
        $this->id_field = 'integer:id';

        $this->registerDateProperty('start_date');
        $this->registerDateProperty('end_date');
        $this->registerInternalProperty(
            'region',
            SwatDBClassMap::get(StoreRegion::class)
        );

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );
    }

    protected function getSerializableSubDataObjects()
    {
        return array_merge(
            parent::getSerializableSubDataObjects(),
            ['region', 'instance']
        );
    }
}
