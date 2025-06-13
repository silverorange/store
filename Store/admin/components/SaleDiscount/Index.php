<?php

/**
 * Index page for SaleDiscounts.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSaleDiscountIndex extends AdminIndex
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
                $this->app->replacePage('SaleDiscount/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // }}}

    // build phase
    // {{{ protected function buildInternal()

    protected function buildInternal()
    {
        parent::buildInternal();

        // set the default time zone
        $start_column =
            $this->ui->getWidget('index_view')->getColumn('start_date');

        $end_column =
            $this->ui->getWidget('index_view')->getColumn('end_date');

        $start_renderer = $start_column->getRendererByPosition();
        $start_renderer->display_time_zone = $this->app->default_time_zone;

        $end_renderer = $end_column->getRendererByPosition();
        $end_renderer->display_time_zone = $this->app->default_time_zone;
    }

    // }}}
    // {{{ protected function getTableModel()

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = 'select SaleDiscount.*
				from SaleDiscount
				order by %s';

        $sql = sprintf(
            $sql,
            $this->getOrderByClause($view, 'start_date, end_date, title')
        );

        return SwatDB::query($this->app->db, $sql);
    }

    // }}}
}
