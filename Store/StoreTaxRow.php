<?php

/**
 * Displays taxes in a special row at the bottom of a table view.
 *
 * @copyright 2006-2016 silverorange
 */
class StoreTaxRow extends StoreTotalRow
{
    public function display()
    {
        // taxes are never free
        if ($this->value === null || $this->value <= 0) {
            $this->visible = false;
        }

        parent::display();
    }
}
