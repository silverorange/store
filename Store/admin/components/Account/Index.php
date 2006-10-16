<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';
require_once '../../include/VeseysNumberCellRenderer.php';

/**
 * Index page for Accounts
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */

class AccountIndex extends AdminSearch
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/search.xml');
		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
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
		
		// veseys_number
		$veseys_numberClause = new AdminSearchClause('veseys_number');
		$veseys_numberClause->table = 'Account';
		$veseys_numberClause->value = 
			$this->ui->getWidget('search_veseys_number')->value;
		$veseys_numberClause->operator = AdminSearchClause::OP_CONTAINS;
		$where.= $veseys_numberClause->getClause($this->app->db);

		return $where;
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$pager = $this->ui->getWidget('pager');

		$sql = 'select Account.id, Account.fullname, Account.email, 
					Account.veseys_number, AccountOrderCountView.order_count
				from Account
					left outer join AccountOrderCountView on 
						Account.id = AccountOrderCountView.account
				where %s
				order by %s';

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
}

?>
