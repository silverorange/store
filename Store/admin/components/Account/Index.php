<?php

require_once 'Site/admin/components/Account/Index.php';

/**
 * Index page for Accounts
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountIndex extends SiteAccountIndex
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Account/index.xml';

	/**
	 * @var string
	 */
	protected $search_xml = 'Store/admin/components/Account/search.xml';

	// }}}

	// build phase
	// {{{ protected function getSQL()

	protected function getSQL()
	{
		return 'select Account.id, Account.fullname, Account.email,
				coalesce(AccountOrderCountView.order_count, 0) as order_count
			from Account
				left outer join AccountOrderCountView on 
					Account.id = AccountOrderCountView.account
			where %s
			order by %s';
	}

	// }}}
}

?>
