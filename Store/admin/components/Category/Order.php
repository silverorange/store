<?php

/**
 * Order page for Categories.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryOrder extends AdminDBOrder
{
    private $parent;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->parent = SiteApplication::initVar('parent');
    }

    // process phase

    protected function saveDBData()
    {
        SwatDB::exec(
            $this->app->db,
            'alter table Category
			disable trigger CategoryVisibleProductCountByRegionTrigger'
        );

        $this->saveIndexes();

        SwatDB::exec(
            $this->app->db,
            'alter table Category
			enable trigger CategoryVisibleProductCountByRegionTrigger'
        );

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function saveIndex($id, $index)
    {
        SwatDB::updateColumn(
            $this->app->db,
            'Category',
            'integer:displayorder',
            $index,
            'integer:id',
            [$id]
        );
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();
        $form = $this->ui->getWidget('order_form');
        $form->addHiddenField('parent', $this->parent);
    }

    protected function loadData()
    {
        $where_clause = sprintf(
            'parent %s %s',
            SwatDB::equalityOperator($this->parent),
            $this->app->db->quote($this->parent, 'integer')
        );

        $order_widget = $this->ui->getWidget('order');
        $order_widget->addOptionsByArray(SwatDB::getOptionArray(
            $this->app->db,
            'Category',
            'title',
            'id',
            'displayorder, title',
            $where_clause
        ));

        $sql = sprintf(
            'select sum(displayorder) from Category where parent %s %s',
            SwatDB::equalityOperator($this->parent, true),
            $this->app->db->quote($this->parent, 'integer')
        );

        $sum = $this->app->db->queryOne($sql, 'integer');
        $options_list = $this->ui->getWidget('options');
        $options_list->value = ($sum == 0) ? 'auto' : 'custom';
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $order_entry = $this->navbar->popEntry();

        if ($this->parent !== null) {
            $navbar_rs = SwatDB::executeStoredProc(
                $this->app->db,
                'getCategoryNavbar',
                [$this->parent]
            );

            foreach ($navbar_rs as $row) {
                $this->navbar->addEntry(new SwatNavBarEntry(
                    $row->title,
                    'Category/Index?id=' . $row->id
                ));
            }
        }

        $this->navbar->addEntry($order_entry);
    }
}
