<?php

/**
 * Index page for Locales.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreLocaleIndex extends AdminIndex
{
    // init phase
    // {{{ protected function initInternal()

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML(__DIR__ . '/index.xml');
    }

    // }}}

    // process phase
    // {{{ protected function processActions()

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('Locale/Delete');
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
        $sql = 'select Locale.id, Region.title as region_title
				from Locale
				inner join Region on Locale.region = Region.id
				order by %s';

        $sql = sprintf($sql, $this->getOrderByClause($view, 'id'));

        return SwatDB::query($this->app->db, $sql);
    }

    // }}}
}
