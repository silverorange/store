<?php

/**
 * Index page for Countries.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCountryIndex extends AdminIndex
{
    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML(__DIR__ . '/index.xml');
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('Country/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // build phase

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = sprintf(
            'select id, title, visible from Country order by %s',
            $this->getOrderByClause($view, 'title')
        );

        return SwatDB::query($this->app->db, $sql);
    }
}
