<?php

/**
 * Delete confirmation page for sale discounts.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSaleDiscountDelete extends AdminDBDelete
{
    // process phase

    protected function processDBData(): void
    {
        parent::processDBData();

        $item_list = $this->getItemList('text');

        SwatDB::exec($this->app->db, sprintf(
            'update ItemRegionBinding set sale_discount_price = null
			where item in (select id from Item where sale_discount in (%s))',
            $item_list
        ));

        $sql = sprintf(
            'delete from SaleDiscount where id in (%s)',
            $item_list
        );

        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(
            sprintf(
                Store::ngettext(
                    'One sale discount has been deleted.',
                    '%s sale discounts have been deleted.',
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

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('integer');

        $dep = new AdminListDependency();
        $dep->setTitle(Store::_('sale discount'), Store::_('sale discounts'));
        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'SaleDiscount',
            'integer:id',
            null,
            'text:title',
            'id',
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
}
