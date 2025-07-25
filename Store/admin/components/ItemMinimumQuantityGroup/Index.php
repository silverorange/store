<?php

/**
 * Index page for item minimum quantity groups.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemMinimumQuantityGroupIndex extends AdminIndex
{
    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML(__DIR__ . '/index.xml');
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        $num = count($view->getSelection());
        $message = null;

        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('ItemMinimumQuantityGroup/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }

        if ($message !== null) {
            $this->app->messages->add($message);
        }
    }

    // build phase

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = sprintf(
            'select id, title, shortname
				from ItemMinimumQuantityGroup order by %s',
            $this->getOrderByClause($view, 'title')
        );

        return SwatDB::query($this->app->db, $sql);
    }
}
