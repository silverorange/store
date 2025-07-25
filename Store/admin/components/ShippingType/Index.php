<?php

/**
 * Index page for Shipping Types.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeIndex extends AdminIndex
{
    // init phase

    protected function initInternal()
    {
        $this->ui->loadFromXML(__DIR__ . '/index.xml');
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('ShippingType/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }
    }

    // build phase

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = 'select * from ShippingType order by displayorder';

        return SwatDB::query($this->app->db, $sql);
    }
}
