<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Index page for Accounts
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountIndex extends AdminSearch
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

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->search_xml);
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db,
			sprintf('select count(id) from Account where %s',
				$this->getWhereClause()));

		$pager->process();
	}

	// }}}

	// build phase
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		/** 
		 * The only way an account fullname can be null is if we've cleared the
		 * data from it with the privacy scripts - we don't ever want to display
		 * these accounts in the search results
		 */
		$where = 'Account.fullname is not null';

		// fullname
		$clause = new AdminSearchClause('fullname');
		$clause->table = 'Account';
		$clause->value = $this->ui->getWidget('search_fullname')->value;
		$clause->operator = AdminSearchClause::OP_CONTAINS;
		$where.= $clause->getClause($this->app->db);

		// email
		$emailClause = new AdminSearchClause('email');
		$emailClause->table = 'Account';
		$emailClause->value = $this->ui->getWidget('search_email')->value;
		$emailClause->operator = AdminSearchClause::OP_CONTAINS;
		$where.= $emailClause->getClause($this->app->db);

		return $where;
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$pager = $this->ui->getWidget('pager');

		$sql = $this->getSQL();
		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view,
				'fullname, email'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		if ($store->getRowCount() != 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');

		return $store;
	}

	// }}}
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
