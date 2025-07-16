<?php

/**
 * Index page for Accounts.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountIndex extends SiteAccountIndex
{
    // init phase

    protected function getSearchXml()
    {
        return __DIR__ . '/search.xml';
    }

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    // build phase

    protected function getSQL()
    {
        return 'select Account.id, Account.fullname,
				Account.email, Account.instance,
				coalesce(AccountOrderCountView.order_count, 0) as order_count
			from Account
			left outer join AccountOrderCountView on
				Account.id = AccountOrderCountView.account
			%s
			where %s
			order by %s';
    }

    protected function getDetailsStore(SiteAccount $account, $row)
    {
        $ds = parent::getDetailsStore($account, $row);
        $ds->order_count = $row->order_count;

        return $ds;
    }
}
