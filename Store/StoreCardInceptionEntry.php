<?php

/**
 * A widget for entry of payment card inception dates.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCardInceptionEntry extends SwatDateEntry
{
    // {{{ public function __construct()

    /**
     * Creates a new card inception date entry widget.
     *
     * The valid range is set from Jaunuary 1, 1992 up to and including the
     * current month.
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

        // inception date for cards is no less than 1992
        $this->valid_range_start = new SwatDate('1992-01-01T00:00:00');

        // is valid up to and including the present month
        $this->valid_range_end = new SwatDate();
        $this->valid_range_end->addMonths(1);
        $this->valid_range_end->setDay(1);
        $this->valid_range_end->setTime(0, 0, 0);
    }

    // }}}
}
