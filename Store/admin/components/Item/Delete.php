<?php

/**
 * Delete confirmation page for Items.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemDelete extends AdminDBDelete
{
    // process phase
    // {{{ protected function processDBData()

    protected function processDBData(): void
    {
        parent::processDBData();

        $item_list = $this->getItemList('integer');

        $sql = sprintf('delete from Item where id in (%s)', $item_list);

        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(sprintf(
            Store::ngettext(
                'One item has been deleted.',
                '%s items have been deleted.',
                $num
            ),
            SwatString::numberFormat($num)
        ), 'notice');

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // }}}
    // {{{ protected function relocate()

    protected function relocate()
    {
        // we don't want to relocate to the Item Index page that doesn't exist
        $form = $this->ui->getWidget('confirmation_form');
        $url = $form->getHiddenField(self::RELOCATE_URL_FIELD);
        $this->app->relocate($url);
    }

    // }}}

    // build phase
    // {{{ public function buildInternal()

    public function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('integer');

        $dep = new AdminListDependency();
        $dep->setTitle(Store::_('item'), Store::_('items'));
        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'Item',
            'integer:id',
            null,
            'text:sku',
            'id',
            'id in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        // dependent promotions
        $this->getDependencies($dep, $item_list);

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $dep->getMessage();
        $message->content_type = 'text/xml';

        if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0) {
            $this->switchToCancelButton();
        }
    }

    // }}}
    // {{{ protected function getDependencies()

    protected function getDependencies($dep, $item_list)
    {
        // dependent quantity discounts
        $dep_discounts = new AdminSummaryDependency();
        $dep_discounts->setTitle(
            Store::_('quantity discount'),
            Store::_('quantity discounts')
        );

        $dep_discounts->summaries = AdminSummaryDependency::querySummaries(
            $this->app->db,
            'QuantityDiscount',
            'integer:id',
            'integer:item',
            'item in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        $dep->addDependency($dep_discounts);
    }

    // }}}
}
