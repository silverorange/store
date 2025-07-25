<?php

/**
 * A sale with a percentage discount.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreItem
 */
class StoreSaleDiscount extends SwatDBDataObject
{
    /**
     * Unique identifier for this sale.
     *
     * @var int
     */
    public $id;

    /**
     * A short, textual identifier for this sale.
     *
     * @var string
     */
    public $shortname;

    /**
     * Discount percentage.
     *
     * @var ?float
     */
    public $discount_percentage;

    /**
     * A title for describing this sale.
     *
     * @var string
     */
    public $title;

    /**
     * Start date.
     *
     * @var ?SwatDate
     */
    public $start_date;

    /**
     * End date.
     *
     * @var ?SwatDate
     */
    public $end_date;

    /**
     * Loads a sale discount by its shortname.
     *
     * @param string $shortname the shortname of the sale discount to load
     */
    public function loadFromShortname($shortname)
    {
        $this->checkDB();
        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where shortname = %s',
                $this->table,
                $this->db->quote($shortname, 'text')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    /**
     * Checks if this sale is currently active.
     *
     * @param SwatDate $date optional. Date on which to check if the discount is
     *                       active. If no date is specified, we check to see
     *                       if the discount is currently active.
     *
     * @return bool true if this sale is active and false if it is not
     */
    public function isActive(?SwatDate $date = null)
    {
        if ($date === null) {
            $date = new SwatDate();
        }

        $date->toUTC();

        return
            ($this->start_date === null
                || SwatDate::compare($date, $this->start_date) >= 0)
            && ($this->end_date === null
                || SwatDate::compare($date, $this->end_date) <= 0);
    }

    protected function init()
    {
        $this->table = 'SaleDiscount';
        $this->id_field = 'integer:id';
        $this->registerDateProperty('start_date');
        $this->registerDateProperty('end_date');
    }
}
