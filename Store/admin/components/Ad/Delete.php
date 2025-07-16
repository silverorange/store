<?php

/**
 * Delete confirmation page for Ads.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdDelete extends SiteAdDelete
{
    // process phase

    protected function getDeleteSql()
    {
        $item_list = $this->getItemList('text');

        return sprintf(
            'delete from Ad where id in (%s) and
			id not in (select ad from Orders where ad is not null)',
            $item_list
        );
    }

    // build phase

    protected function getDependencies()
    {
        $dep = parent::getDependencies();

        $item_list = $this->getItemList('integer');

        $dep_orders = new AdminSummaryDependency();
        $dep_orders->setTitle(Store::_('order'), Store::_('orders'));
        $dep_orders->summaries = AdminSummaryDependency::querySummaries(
            $this->app->db,
            'Orders',
            'integer:id',
            'integer:ad',
            'ad in (' . $item_list . ')',
            AdminDependency::NODELETE
        );

        $dep->addDependency($dep_orders);

        return $dep;
    }
}
