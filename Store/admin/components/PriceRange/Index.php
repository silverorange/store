<?php

/**
 * Index page for PriceRanges.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceRangeIndex extends AdminIndex
{
    // init phase
    // {{{ protected function initInternal()

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());
    }

    // }}}
    // {{{ protected function getUiXml()

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    // }}}

    // process phase
    // {{{ protected function processActions()

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('PriceRange/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // }}}

    // build phase
    // {{{ protected function getTableModel()

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = 'select * from PriceRange order by %s';

        $sql = sprintf(
            'select * from PriceRange order by %s',
            $this->getOrderByClause(
                $view,
                'PriceRange.start_price nulls first, PriceRange.end_price'
            )
        );

        $rs = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get('StorePriceRangeWrapper')
        );

        $store = new SwatTableStore();

        foreach ($rs as $row) {
            $ds = new SwatDetailsStore($row);
            $ds->title = $row->getTitle();
            $store->add($ds);
        }

        return $store;
    }

    // }}}
}
