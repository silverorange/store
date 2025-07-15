<?php

/**
 * Order page for Items component.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemOrder extends AdminDBOrder
{
    protected $item_group_id;
    protected $product_id;
    protected $category_id;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->item_group_id = SiteApplication::initVar('item_group');
        $this->product_id = SiteApplication::initVar('product');
        $this->category_id = SiteApplication::initVar('category');
    }

    // process phase

    protected function saveDBData()
    {
        SwatDB::exec(
            $this->app->db,
            'alter table Item
			disable trigger VisibleProductTrigger'
        );

        $this->saveIndexes();

        SwatDB::exec(
            $this->app->db,
            'alter table Item
			enable trigger VisibleProductTrigger'
        );

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function saveIndex($id, $index)
    {
        SwatDB::updateColumn(
            $this->app->db,
            'Item',
            'integer:displayorder',
            $index,
            'integer:id',
            [$id]
        );
    }

    protected function getUpdatedMessage()
    {
        return new SwatMessage(Store::_('Item order updated.'));
    }

    // build phase

    protected function buildFrame()
    {
        parent::buildFrame();

        $frame = $this->ui->getWidget('order_frame');
        $frame->title = Store::_('Order Items');
    }

    protected function buildForm()
    {
        parent::buildForm();

        $form = $this->ui->getWidget('order_form');
        $form->addHiddenField('item_group', $this->item_group_id);
        $form->addHiddenField('product', $this->product_id);
        $form->addHiddenField('category', $this->category_id);
    }

    protected function loadData()
    {
        if ($this->item_group_id === null) {
            $where_clause = sprintf(
                'Item.product = %s',
                $this->app->db->quote($this->product_id, 'integer')
            );
        } elseif ($this->item_group_id == 0) {
            $where_clause = sprintf(
                'Item.item_group is null and Item.product = %s',
                $this->app->db->quote($this->product_id, 'integer')
            );
        } else {
            $where_clause = sprintf(
                'Item.item_group = %s',
                $this->app->db->quote($this->item_group_id, 'integer')
            );
        }

        $order_widget = $this->ui->getWidget('order');

        $sql = sprintf(
            'select id from Item where %s',
            $where_clause
        );

        $item_wrapper_class = SwatDBClassMap::get(StoreItemWrapper::class);
        $items = call_user_func(
            [$item_wrapper_class, 'loadSetFromDb'],
            $this->app->db,
            $sql
        );

        foreach ($items as $item) {
            $order_widget->addOption($item->id, $this->getItemDescription($item));
        }

        $sql = 'select sum(displayorder) from Item where ' . $where_clause;
        $sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
        $options_list = $this->ui->getWidget('options');
        $options_list->value = ($sum == 0) ? 'auto' : 'custom';
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $last_entry = $this->navbar->popEntry();
        $this->navbar->popEntry();

        if ($this->category_id === null) {
            $this->navbar->addEntry(new SwatNavBarEntry(
                Store::_('Product Search'),
                'Product'
            ));
        } else {
            $this->navbar->addEntry(new SwatNavBarEntry(
                Store::_('Product Categories'),
                'Category'
            ));

            $cat_navbar_rs = SwatDB::executeStoredProc(
                $this->app->db,
                'getCategoryNavbar',
                [$this->category_id]
            );

            foreach ($cat_navbar_rs as $entry) {
                $this->navbar->addEntry(new SwatNavBarEntry(
                    $entry->title,
                    'Category/Index?id=' . $entry->id
                ));
            }
        }

        if ($this->product_id === null) {
            $product_id = SwatDB::queryOneFromTable(
                $this->app->db,
                'ItemGroup',
                'integer:product',
                'id',
                $this->item_group_id
            );
        } else {
            $product_id = $this->product_id;
        }

        $product_title = SwatDB::queryOneFromTable(
            $this->app->db,
            'Product',
            'text:title',
            'id',
            $product_id
        );

        if ($this->category_id === null) {
            $link = sprintf('Product/Details?id=%s', $product_id);
        } else {
            $link = sprintf(
                'Product/Details?id=%s&category=%s',
                $product_id,
                $this->category_id
            );
        }

        $this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
        $this->navbar->addEntry($last_entry);
        $this->title = $product_title;
    }

    protected function getItemDescription(StoreItem $item)
    {
        $text = $item->sku;

        $description = implode(' - ', $item->getDescriptionArray());

        if ($description != '') {
            $text .= ': ' . $description;
        }

        return $text;
    }
}
