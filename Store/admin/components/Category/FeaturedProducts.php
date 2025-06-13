<?php

/**
 * Search page for Featured Products.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryFeaturedProducts extends AdminIndex
{
    // {{{ private properties

    private $parent;

    // }}}

    // init phase
    // {{{ protected function initInternal()

    protected function initInternal()
    {
        parent::initInternal();
        $this->ui->loadFromXML($this->getUiXml());
        $this->parent = SiteApplication::initVar('parent');
    }

    // }}}
    // {{{ protected function getUiXml()

    protected function getUiXml()
    {
        return __DIR__ . '/featured-products.xml';
    }

    // }}}

    // process phase
    // {{{ protected function processInternal()

    protected function processInternal()
    {
        parent::processInternal();
        $form = $this->ui->getWidget('index_form');

        if ($form->isProcessed()) {
            $view = $this->ui->getWidget('index_view');

            if (count($view->getSelection()) > 0) {
                $product_list = [];
                foreach ($view->getSelection() as $item) {
                    $product_list[] = $this->app->db->quote($item, 'integer');
                }

                $sql = sprintf(
                    'insert into CategoryFeaturedProductBinding
						(category, product)
					select %s, Product.id from Product
					where Product.id not in
						(select product from CategoryFeaturedProductBinding
							where category = %s)
						and Product.id in (%s)',
                    $this->app->db->quote($this->parent, 'integer'),
                    $this->app->db->quote($this->parent, 'integer'),
                    implode(',', $product_list)
                );

                $num = SwatDB::exec($this->app->db, $sql);

                $message = new SwatMessage(
                    sprintf(
                        Store::ngettext(
                            'One featured product has been updated.',
                            '%s featured products have been updated.',
                            $num
                        ),
                        SwatString::numberFormat($num)
                    ),
                    'notice'
                );

                $this->app->messages->add($message);

                if (isset($this->app->memcache)) {
                    $this->app->memcache->flushNs('product');
                }
            }

            $this->app->relocate('Category/Index?id=' . $this->parent);
        }
    }

    // }}}

    // build phase
    // {{{ protected function buildInternal()
    protected function buildInternal()
    {
        parent::buildInternal();

        $parent = $this->app->db->quote($this->parent, 'integer');

        $rs = SwatDB::executeStoredProc(
            $this->app->db,
            'getCategoryTree',
            [$parent]
        );

        $tree = SwatDB::getDataTree($rs, 'title', 'id', 'levelnum');

        $category_flydown = $this->ui->getWidget('category_flydown');
        $category_flydown->setTree($tree);
        $category_flydown->show_blank = false;

        $search_form = $this->ui->getWidget('search_form');
        $search_form->action = $this->source;
        $search_form->addHiddenField('parent', $this->parent);

        $index_form = $this->ui->getWidget('index_form');
        $index_form->action = $this->source;
        $index_form->addHiddenField('parent', $this->parent);
    }

    // }}}
    // {{{ protected function getTableModel()

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = 'select distinct Product.id, Product.title
				from Product
				inner join CategoryProductBinding on
					Product.id = CategoryProductBinding.product
				inner join getCategoryDescendants(%s) as
					category_descendants on
					category_descendants.descendant =
						CategoryProductBinding.category
				where category_descendants.category = %s
				order by %s';

        $category_flydown = $this->ui->getWidget('category_flydown');

        if ($category_flydown->value === null) {
            $category_flydown->value = $this->parent;
        }

        $sql = sprintf(
            $sql,
            $this->app->db->quote($category_flydown->value, 'integer'),
            $this->app->db->quote($category_flydown->value, 'integer'),
            $this->getOrderByClause(
                $view,
                'Product.title, Product.id'
            )
        );

        return SwatDB::query($this->app->db, $sql);
    }

    // }}}
    // {{{ protected function buildNavBar()

    protected function buildNavBar()
    {
        parent::buildNavBar();

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

        $this->navbar->addEntry(new SwatNavBarEntry(
            Store::_('Featured Products')
        ));
    }

    // }}}
}
