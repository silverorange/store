<?php

/**
 * A widget for entry of payment card expiry dates.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCardExpiryEntry extends SwatDateEntry
{
    // {{{ public function __construct()

    /**
     * Creates a new card expiry date entry widget.
     *
     * The valid range is set from the current month up to 20 years in the
     * future.
     *
     * @param string $id
     *
     * @see SwatWidget::__construct()
     */
    public function __construct($id = null)
    {
        parent::__construct($id);

        $this->show_month_number = true;
        $this->display_parts = self::MONTH | self::YEAR;

        // do not allow dates in the past by default
        $this->setValidRange(0, 20);

        // set start date fields to first day of the current month
        $today = new SwatDate();
        $this->valid_range_start->setMonth($today->getMonth());
        $this->valid_range_start->setDay(1);
        $this->valid_range_start->setTime(0, 0, 0);
    }

    // }}}
}
