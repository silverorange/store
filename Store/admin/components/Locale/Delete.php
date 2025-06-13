<?php

/**
 * Delete confirmation page for Locales.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreLocaleDelete extends AdminDBDelete
{
    // process phase
    // {{{ protected function processDBData()

    protected function processDBData(): void
    {
        parent::processDBData();
        $item_list = $this->getItemList('text');

        $sql = $this->getProcessSQL();
        $num = SwatDB::exec($this->app->db, sprintf($sql, $item_list));

        $message = new SwatMessage(sprintf(Store::ngettext(
            'One locale has been deleted.',
            '%s locales have been deleted.',
            $num
        ), SwatString::numberFormat($num)), 'notice');

        $this->app->messages->add($message);

        if (isset($this->app->memcache)) {
            $this->app->memcache->flushNs('product');
        }
    }

    // }}}
    // {{{ protected function getProcessSQL()

    protected function getProcessSQL()
    {
        return 'delete from Locale where id in (%s)
			and id not in (select locale from Orders)';
    }

    // }}}

    // build phase
    // {{{ protected function buildInternal()

    protected function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('text');
        $dep = new AdminListDependency();
        $dep->setTitle(Store::_('locale'), Store::_('locales'));
        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'Locale',
            'text:id',
            null,
            'text:id',
            'id',
            'id in (' . $item_list . ')',
            AdminDependency::DELETE
        );

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
        // dependent orders
        $dep_orders = new AdminSummaryDependency();
        $dep_orders->setTitle(Store::_('order'), Store::_('orders'));
        $dep_orders->summaries = AdminSummaryDependency::querySummaries(
            $this->app->db,
            'Orders',
            'integer:id',
            'text:locale',
            'locale in (' . $item_list . ')',
            AdminDependency::NODELETE
        );

        $dep->addDependency($dep_orders);
    }

    // }}}
}
