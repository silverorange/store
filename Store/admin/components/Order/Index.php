<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Index page for Orders
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderIndex extends AdminSearch
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Order/index.xml';

	/**
	 * @var string
	 */
	protected $search_xml = 'Store/admin/components/Order/search.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->search_xml);
		$this->ui->loadFromXML($this->ui_xml);
		$this->ui->getRoot()->addStyleSheet('styles/orders-index.css');
		
		$search_region = $this->ui->getWidget('search_region');
		$search_region->show_blank = true;
		$search_region->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title'));
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$this->ui->getWidget('pager')->process();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal() 

	protected function buildInternal() 
	{
		parent::buildInternal();

		// set default time zone
		$date_column =
			$this->ui->getWidget('index_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$where = '1 = 1';

		// Order #
		$clause = new AdminSearchClause('integer:id');
		$clause->table = 'Orders';
		$clause->value = $this->ui->getWidget('search_id')->value;
		$where.= $clause->getClause($this->app->db);

		// Order # Range gt
		$clause = new AdminSearchClause('integer:id');
		$clause->table = 'Orders';
		$clause->value = $this->ui->getWidget('search_id_gt')->value;
		$clause->operator = AdminSearchClause::OP_GT;
		$where.= $clause->getClause($this->app->db);

		// Order # Range lt
		$clause = new AdminSearchClause('integer:id');
		$clause->table = 'Orders';
		$clause->value = $this->ui->getWidget('search_id_lt')->value;
		$clause->operator = AdminSearchClause::OP_LT;
		$where.= $clause->getClause($this->app->db);

		// fullname, check accounts, and both order addresses
		$fullname = $this->ui->getWidget('search_fullname')->value;
		if (strlen(trim($fullname)) > 0) {
			$where.= ' and (';
			$clause = new AdminSearchClause('fullname');
			$clause->table = 'Account';
			$clause->value = $fullname;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, '');
	
			$clause = new AdminSearchClause('fullname');
			$clause->table = 'BillingAddress';
			$clause->value = $fullname;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'or');
	
			$clause = new AdminSearchClause('fullname');
			$clause->table = 'ShippingAddress';
			$clause->value = $fullname;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'or');
			$where.= ')';
		}

		// email, check accounts and order
		$email = $this->ui->getWidget('search_email')->value;
		if (strlen(trim($email)) > 0) {
			$where.= ' and (';
			$clause = new AdminSearchClause('email');
			$clause->table = 'Account';
			$clause->value = $email;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, '');
	
			$clause = new AdminSearchClause('email');
			$clause->table = 'Orders';
			$clause->value = $email;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db, 'or');
			$where.= ')';
		}

		// date range gt
		if ($this->ui->getWidget('search_createdate_gt')->value !== null) {
			// clone so the date displayed will stay the same
			$date_gt =
				clone $this->ui->getWidget('search_createdate_gt')->value;

			$date_gt->setTZ($this->app->default_time_zone);
			$date_gt->toUTC();

			$clause = new AdminSearchClause('date:createdate');
			$clause->table = 'Orders';
			$clause->value = $date_gt->getDate();
			$clause->operator = AdminSearchClause::OP_GT;
			$where.= $clause->getClause($this->app->db);
		}

		// date range lt
		if ($this->ui->getWidget('search_createdate_lt')->value !== null) {
			// clone so the date displayed will stay the same
			$date_lt =
				clone $this->ui->getWidget('search_createdate_lt')->value;

			$date_lt->setTZ($this->app->default_time_zone);
			$date_lt->toUTC();

			$clause = new AdminSearchClause('date:createdate');
			$clause->table = 'Orders';
			$clause->value = $date_lt->getDate();
			$clause->operator = AdminSearchClause::OP_LT;
			$where.= $clause->getClause($this->app->db);
		}

		// Region
		$clause = new AdminSearchClause('integer:id');
		$clause->table = 'Region';
		$clause->value = $this->ui->getWidget('search_region')->value;
		$where.= $clause->getClause($this->app->db);

		return $where;
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = 'select count(Orders.id) from Orders
					left outer join Account on Orders.account = Account.id
					inner join OrderAddress as BillingAddress
						on Orders.billing_address = BillingAddress.id
					inner join OrderAddress as ShippingAddress
						on Orders.shipping_address = ShippingAddress.id
					inner join Locale on Orders.locale = Locale.id
					inner join Region on Locale.region = Region.id
				where %s';

		$sql = sprintf($sql, $this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);
	
		$sql = 'select Orders.id, Orders.total, Orders.createdate,
					Orders.locale,
					char_length(Orders.comments) > 0 as has_comments,
					BillingAddress.fullname
				from Orders
					left outer join Account on Orders.account = Account.id
					inner join OrderAddress as BillingAddress
						on Orders.billing_address = BillingAddress.id
					inner join OrderAddress as ShippingAddress 
						on Orders.shipping_address = ShippingAddress.id
					inner join Locale on Orders.locale = Locale.id
					inner join Region on Locale.region = Region.id
				where %s
				order by %s';

		// Order by id and not createdate in case two createdates are the same.
		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'Orders.id desc'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		if ($store->getRowCount() > 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');

		foreach ($store->getRows() as $row)
			$row->title = $this->getOrderTitle($row);

		return $store;
	}

	// }}}
	// {{{ protected function getOrderTitle()

	protected function getOrderTitle($order) 
	{
		return sprintf(Store::_('Order %s'), $order->id);
	}

	// }}}
}

?>
