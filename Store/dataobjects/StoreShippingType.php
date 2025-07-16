<?php

/**
 * A shiping type data object.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int                      $id
 * @property ?string                  $shortname
 * @property ?string                  $title
 * @property ?string                  $note
 * @property ?int                     $displayorder
 * @property StoreShippingRateWrapper $shipping_rates
 */
class StoreShippingType extends SwatDBDataObject
{
    /**
     * Unique identifier of this shipping type.
     *
     * @var int
     */
    public $id;

    /**
     * Non-visible string indentifier.
     *
     * This is something like 'express' or 'ground'.
     *
     * @var string
     */
    public $shortname;

    /**
     * User visible title for this shipping type.
     *
     * @var string
     */
    public $title;

    /**
     * User visible note for this shipping type.
     *
     * @var string
     */
    public $note;

    /**
     * Order of display.
     *
     * @var int
     */
    public $displayorder;

    /**
     * The region to use when loading shipping rates.
     *
     * @var StoreRegion
     *
     * @see StoreShippingType::setRegion()
     */
    protected $region;

    /**
     * Sets the region to use when loading shipping rates.
     *
     * @param StoreRegion $region the region to use
     */
    public function setRegion(StoreRegion $region)
    {
        $this->region = $region;
    }

    /**
     * Loads a shipping type by its shortname.
     *
     * @param string $shortname the shortname of the shipping type to load
     */
    public function loadByShortname($shortname)
    {
        $this->checkDB();

        $sql = sprintf(
            'select id from ShippingType
			where ShippingType.shortname = %s',
            $this->db->quote($shortname, 'text')
        );

        $id = SwatDB::queryOne($this->db, $sql);

        if ($id === null) {
            return false;
        }

        return $this->load($id);
    }

    public function calculateShippingRate(
        $item_total,
        ?StoreRegion $region = null
    ) {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                '$region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        $this->checkDB();

        // get applicable rate based on price threshold
        $sql = 'select amount, percentage
			from ShippingRate
			where threshold <= %s and shipping_type = %s and region = %s
			order by amount desc, percentage';

        $sql = sprintf(
            $sql,
            $this->db->quote($item_total, 'float'),
            $this->db->quote($this->id, 'integer'),
            $this->db->quote($region->id, 'integer')
        );

        $this->db->setLimit(1);

        $rate = SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreShippingRateWrapper::class)
        )->getFirst();

        $total = null;

        if ($rate !== null) {
            $percentage = 0;
            $amount = 0;

            if ($rate->amount === null) {
                $percentage += $rate->percentage;
            } else {
                $amount += $rate->amount;
            }

            if ($percentage > 0) {
                $total += round($item_total * $percentage, 2);
            }

            if ($amount > 0) {
                $total += $amount;
            }
        }

        return $total;
    }

    /**
     * Whether this shipping type has a shipping rate with a fuel surcharge.
     */
    public function hasFuelSurcharge()
    {
        $surcharge = false;

        foreach ($this->shipping_rates as $rate) {
            if ($rate->fuel_surcharge_amount > 0
                || $rate->fuel_surcharge_percentage > 0) {
                $surcharge = true;
                break;
            }
        }

        return $surcharge;
    }

    protected function init()
    {
        $this->table = 'ShippingType';
        $this->id_field = 'integer:id';
    }

    // loader methods

    public function loadShippingRates(?StoreRegion $region = null)
    {
        if ($region === null) {
            $region = $this->region;
        }

        if ($region === null) {
            throw new StoreException(
                '$region must be specified unless setRegion() is called ' .
                'beforehand.'
            );
        }

        $sql = 'select * from ShippingRate
			where shipping_type = %s and region = %s
			order by threshold, id';

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer'),
            $this->db->quote($region->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreShippingRateWrapper::class)
        );
    }

    // display methods

    /**
     * Displays this shipping type.
     */
    public function display()
    {
        echo SwatString::minimizeEntities($this->title);

        if (mb_strlen($this->note) > 0) {
            printf(
                '<br /><span class="swat-note">%s</span>',
                $this->note
            );
        }
    }

    /**
     * Displays this shipping type as text.
     */
    public function displayAsText()
    {
        echo $this->title;

        if (mb_strlen($this->note) > 0) {
            echo ' - ' . $this->note;
        }
    }
}
