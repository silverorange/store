<?php

/**
 * Delete confirmation page for Product Collections.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductProductCollectionDelete extends AdminDBDelete
{
    private $id;
    private $category_id;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setCategory($category_id)
    {
        $this->category_id = $category_id;
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->id = SiteApplication::initVar(
            'id',
            null,
            SiteApplication::VAR_POST
        );

        $this->category_id = SiteApplication::initVar(
            'category',
            null,
            SiteApplication::VAR_POST
        );

        $yes_button = $this->ui->getWidget('yes_button');
        $yes_button->title = Store::_('Remove from Collection');
    }

    // process phase

    protected function processDBData(): void
    {
        parent::processDBData();

        $sql = 'delete from ProductCollectionBinding
			where source_product = %s and member_product in (%s)';

        $item_list = $this->getItemList('integer');
        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->id, 'integer'),
            $item_list
        );

        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(sprintf(
            Store::ngettext(
                'One product has been removed from the collection.',
                '%s products have been removed from the collection.',
                $num
            ),
            SwatString::numberFormat($num)
        ), 'notice');

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    protected function relocate()
    {
        // don't use the AdminDBDelete relocate as its too smart for its own
        // good, and takes us back to the index page
        AdminConfirmation::relocate();
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $form = $this->ui->getWidget('confirmation_form');
        $form->addHiddenField('id', $this->id);
        $form->addHiddenField('category', $this->category_id);

        $item_list = $this->getItemList('integer');

        $dep = new StoreProductCollectionDependency();
        $dep->product_title = SwatDB::queryOneFromTable(
            $this->app->db,
            'Product',
            'text:title',
            'id',
            $this->id
        );

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

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $dep->getMessage();
        $message->content_type = 'text/xml';

        if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0) {
            $this->switchToCancelButton();
        }
    }

    protected function buildNavBar()
    {
        // Take "Delete" off the navbar
        $this->navbar->popEntry();

        if ($this->category_id !== null) {
            // Take the "Product Search" off the navbar & show the category tree
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

        $product_title = SwatDB::queryOneFromTable(
            $this->app->db,
            'Product',
            'text:title',
            'id',
            $this->id
        );

        if ($this->category_id === null) {
            $link = sprintf('Product/Details?id=%s', $this->id);
        } else {
            $link = sprintf(
                'Product/Details?id=%s&category=%s',
                $this->id,
                $this->category_id
            );
        }

        $this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
        $this->navbar->addEntry(new SwatNavBarEntry(
            Store::_('Remove from Collection')
        ));

        $this->title = $product_title;
    }
}
