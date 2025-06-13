<?php

/**
 * Delete confirmation page for Countries.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCountryDelete extends AdminDBDelete
{
    // process phase

    protected function processDBData(): void
    {
        parent::processDBData();

        $item_list = $this->getItemList('text');

        $sql = sprintf(
            'delete from Country where id in (%s)
			and id not in (select country from AccountAddress)
			and id not in (select country from OrderAddress)',
            $item_list
        );

        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(sprintf(Store::ngettext(
            'One country has been deleted.',
            '%s countries have been deleted.',
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

        $item_list = $this->getItemList('text');

        $dep = new AdminListDependency();
        $dep->setTitle(Store::_('country'), Store::_('countries'));
        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'Country',
            'text:id',
            null,
            'text:title',
            'title',
            'id in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        // dependent order addresses
        $orders_billing_dependency = new AdminSummaryDependency();
        $orders_billing_dependency->setTitle(
            Store::_('order address'),
            Store::_('order addresses')
        );

        $orders_billing_dependency->summaries =
            AdminSummaryDependency::querySummaries(
                $this->app->db,
                'OrderAddress',
                'integer:id',
                'text:country',
                'country in (' . $item_list . ')',
                AdminDependency::NODELETE
            );

        $dep->addDependency($orders_billing_dependency);

        // dependent account addresses
        $addresses_dependency = new AdminSummaryDependency();
        $addresses_dependency->setTitle(
            Store::_('account address'),
            Store::_('account addresses')
        );

        $addresses_dependency->summaries =
            AdminSummaryDependency::querySummaries(
                $this->app->db,
                'AccountAddress',
                'integer:id',
                'text:country',
                'country in (' . $item_list . ')',
                AdminDependency::NODELETE
            );

        $dep->addDependency($addresses_dependency);

        $provstates_dependency = new AdminListDependency();
        $provstates_dependency->setTitle(
            Store::_('province or state'),
            Store::_('provinces or states')
        );

        $provstates_dependency->entries =
            AdminListDependency::queryEntries(
                $this->app->db,
                'ProvState',
                'integer:id',
                'text:country',
                'text:title',
                'title',
                'country in (' . $item_list . ')',
                AdminDependency::DELETE
            );

        $dep->addDependency($provstates_dependency);

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $dep->getMessage();
        $message->content_type = 'text/xml';

        if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0) {
            $this->switchToCancelButton();
        }
    }
}
