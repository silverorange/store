<?php

/**
 * A custom table view for the cart which allows for a column for product
 * images.
 *
 * This class is meant to me used in conjunction with a {@see
 * StoreCartImageTableViewGroup}.
 *
 * @see StoreCartImageTableViewGroup
 *
 * @copyright 2004-2016 silverorange
 */
class StoreCartImageTableView extends SwatTableView
{
    public function getVisibleColumnCount()
    {
        return parent::getVisibleColumnCount() + 1;
    }

    public function getXhtmlColspan()
    {
        return parent::getXhtmlColspan() + 1;
    }

    /**
     * Displays the column headers for this table-view.
     *
     * Each column is asked to display its own header.
     * Rows in the header are outputted inside a <thead> HTML tag.
     */
    protected function displayHeader()
    {
        echo '<thead>';
        echo '<tr>';

        echo '<th>&nbsp;</th>';

        foreach ($this->columns as $column) {
            $column->displayHeaderCell();
        }

        echo '</tr>';
        echo '</thead>';
    }
}
