<?php

/**
 * Delete confirmation page for Attributes.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeDelete extends AdminDBDelete
{
    // process phase

    protected function processDBData(): void
    {
        parent::processDBData();

        $item_list = $this->getItemList('integer');

        $sql = sprintf('delete from Attribute where id in (%s)', $item_list);

        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(sprintf(Store::ngettext(
            'One attribute has been deleted.',
            '%s attributes have been deleted.',
            $num
        ), SwatString::numberFormat($num)), 'notice');

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('integer');

        $dep = new AdminListDependency();
        $dep->setTitle(Store::_('attribute'), Store::_('attributes'));
        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'Attribute',
            'integer:id',
            null,
            'text:title',
            'title',
            'id in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        // dependent products
        $attribute_dependency = new AdminSummaryDependency();
        $attribute_dependency->setTitle(
            Store::_('product'),
            Store::_('products')
        );

        $attribute_dependency->summaries =
            AdminSummaryDependency::querySummaries(
                $this->app->db,
                'ProductAttributeBinding',
                'integer:product',
                'integer:attribute',
                'attribute in (' . $item_list . ')',
                AdminDependency::DELETE
            );

        $dep->addDependency($attribute_dependency);

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $dep->getMessage();
        $message->content_type = 'text/xml';

        if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0) {
            $this->switchToCancelButton();
        }
    }
}
