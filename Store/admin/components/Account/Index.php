<?php

require_once 'Site/admin/components/Account/Index.php';

/**
 * Index page for Accounts
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
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
		return 'select Account.id, Account.fullname, Account.email
			where %s
			order by %s';
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$accounts = parent::getTableModel($view);

		$order_count = $this->getOrderCount($accounts);

		$store = new SwatTableStore();
		foreach ($accounts as $account) {
			$ds = new SwatDetailsStore($account);
			$ds->order_count = $order_count[$ds->id];
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getOrderCount()

	private function getOrderCount(SwatTableStore $accounts)
	{
		$count_array = array();

		if (count($accounts) > 0) {
			// default to zero, some accounts aren't in AccountOrderCountView
			foreach ($accounts as $account) {
				$count_array[$account->id] = 0;
			}

			$account_ids = implode(', ', array_keys($count_array));
			$sql = sprintf('select account, order_count from
				AccountOrderCountView where account in (%s)', $account_ids);

			$counts = SwatDB::query($this->app->db, $sql);
			foreach ($counts as $count)
				$count_array[$count->account] = $count->order_count;
		}

		return $count_array;
	}

	// }}}
}

?>
