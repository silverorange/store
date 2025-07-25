<?php

/**
 * Index page for Attributes.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeIndex extends AdminIndex
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
                $this->app->replacePage('Attribute/Delete');
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
            'select Attribute.*
			from Attribute
			inner join AttributeType on
				Attribute.attribute_type = AttributeType.id
			order by attribute_type, %s',
            $this->getOrderByClause($view, 'displayorder, title')
        );

        $attributes = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(StoreAttributeWrapper::class)
        );

        $store = new SwatTableStore();
        foreach ($attributes as $attribute) {
            $ds = new SwatDetailsStore($attribute);
            $ds->order_sensitive =
                (count($attribute->attribute_type->attributes) > 0);

            $store->add($ds);
        }

        return $store;
    }
}
