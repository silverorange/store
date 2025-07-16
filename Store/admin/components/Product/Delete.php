<?php

/**
 * Delete confirmation page for products.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductDelete extends AdminDBDelete
{
    /**
     * The category we came from for the deleted product.
     *
     * Used for the custom relocate.
     *
     * @var int
     */
    private $category_id;

    public function setCategory($category_id)
    {
        $this->category_id = $category_id;
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->category_id = SiteApplication::initVar('category');
    }

    // process phase

    protected function processDBData(): void
    {
        parent::processDBData();

        $sql = 'delete from Product where id in (%s)';
        $item_list = $this->getItemList('integer');
        $sql = sprintf($sql, $item_list);

        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(
            sprintf(
                Store::ngettext(
                    'One product has been deleted.',
                    '%s products have been deleted.',
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

    protected function relocate()
    {
        if ($this->single_delete) {
            $form = $this->ui->getWidget('confirmation_form');

            if ($form->button->id == 'no_button') {
                // single delete that was cancelled, go back to details page
                parent::relocate();
            } else {
                if ($this->category_id === null) {
                    $this->app->relocate('Product');
                } else {
                    $this->app->relocate('Category/Index?id=' .
                            $this->category_id);
                }
            }
        } else {
            parent::relocate();
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $form = $this->ui->getWidget('confirmation_form');
        $form->addHiddenField('category', $this->category_id);

        $item_list = $this->getItemList('integer');

        $dep = new AdminListDependency();
        $dep->setTitle(Store::_('product'), Store::_('products'));
        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'Product',
            'integer:id',
            null,
            'text:title',
            'title',
            'id in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        $this->getDependentItems($dep, $item_list);

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $dep->getMessage();
        $message->content_type = 'text/xml';

        if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0) {
            $this->switchToCancelButton();
        }
    }

    protected function buildNavBar()
    {
        $last_entry = $this->navbar->popEntry();

        if ($this->category_id !== null) {
            $this->navbar->popEntry();
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
                $this->title = $entry->title;
                $this->navbar->addEntry(new SwatNavBarEntry(
                    $entry->title,
                    'Category/Index?id=' . $entry->id
                ));
            }
        }

        if ($this->single_delete) {
            $id = $this->getFirstItem();
            $product_title = SwatDB::queryOneFromTable(
                $this->app->db,
                'Product',
                'text:title',
                'id',
                $id
            );

            if ($this->category_id === null) {
                $link = sprintf('Product/Details?id=%s', $id);
            } else {
                $link = sprintf(
                    'Product/Details?id=%s&category=%s',
                    $id,
                    $this->category_id
                );
            }

            $this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
            $this->title = $product_title;
        }

        $this->navbar->addEntry($last_entry);
    }

    private function getDependentItems($dep, $item_list)
    {
        $dep_items = new StoreProductItemDependency();
        $dep_items->summaries = AdminSummaryDependency::querySummaries(
            $this->app->db,
            'Item',
            'integer:id',
            'integer:product',
            'product in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        $dep->addDependency($dep_items);
    }
}
