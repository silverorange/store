<?php

/**
 * Order page for Attributes.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeOrder extends AdminDBOrder
{
    private $parent;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->parent = SiteApplication::initVar('parent');
        $form = $this->ui->getWidget('order_form');
        $form->addHiddenField('parent', $this->parent);
    }

    // process phase

    protected function saveIndex($id, $index)
    {
        SwatDB::updateColumn(
            $this->app->db,
            'Attribute',
            'integer:displayorder',
            $index,
            'integer:id',
            [$id]
        );

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // build phase

    protected function buildInternal()
    {
        $frame = $this->ui->getWidget('order_frame');
        $frame->title = Store::_('Order Attributes');
        parent::buildInternal();
    }

    protected function loadData()
    {
        $where_clause = sprintf(
            'attribute_type = %s',
            $this->app->db->quote($this->parent, 'integer')
        );

        $order_widget = $this->ui->getWidget('order');
        $order_widget->addOptionsByArray(SwatDB::getOptionArray(
            $this->app->db,
            'Attribute',
            'title',
            'id',
            'displayorder, title',
            $where_clause
        ));

        $sql = 'select sum(displayorder) from Attribute where ' .
            $where_clause;

        $sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
        $options_list = $this->ui->getWidget('options');
        $options_list->value = ($sum == 0) ? 'auto' : 'custom';
    }
}
