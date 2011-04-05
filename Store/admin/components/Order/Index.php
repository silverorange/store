<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'Store/dataobjects/StoreOrderWrapper.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Index page for Orders
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
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

		$search_region = $this->ui->getWidget('search_region');
		$search_region->show_blank = true;
		$options = SwatDB::getOptionArray($this->app->db,
				'Region', 'title', 'id', 'title');

		if (count($options) > 1) {
			$search_region->addOptionsByArray($options);
			$search_region->parent->visible = true;
		}

		if ($this->app->getInstance() === null) {
			$search_instance = $this->ui->getWidget('search_instance');
			$search_instance->show_blank = true;
			$options = SwatDB::getOptionArray($this->app->db,
					'Instance', 'title', 'id', 'title');

			if (count($options) > 1) {
				$search_instance->addOptionsByArray($options);
				$search_instance->parent->visible = true;
			}
		}

		// Set a default order on the table view. Default to id and not
		// createdate in case two createdates are the same.
		$index_view = $this->ui->getWidget('index_view');
		$index_view->setDefaultOrderbyColumn(
			$index_view->getColumn('id'),
			 SwatTableViewOrderableColumn::ORDER_BY_DIR_DESCENDING);
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
		$date_renderer->time_zone_format = SwatDate::TZ_CURRENT_SHORT;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$where = '1=1';

		// Instance
		$instance_id = $this->app->getInstanceId();
		if ($instance_id === null)
			$instance_id = $this->ui->getWidget('search_instance')->value;

		if ($instance_id !== null)
			$where.= sprintf(' and Orders.instance = %s',
				$this->app->db->quote($instance_id, 'integer'));

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
		$where.= $this->getFullnameWhereClause();

		// email, check accounts and order
		$email = $this->ui->getWidget('search_email')->value;
		if (trim($email) != '') {
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
			$clause->operator = AdminSearchClause::OP_GTE;
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
	// {{{ protected function getFullnameWhereClause()

	protected function getFullnameWhereClause()
	{
		$where = '';

		// fullname, check accounts, and both order addresses
		$fullname = $this->ui->getWidget('search_fullname')->value;
		if (trim($fullname) != '') {
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

		return $where;
	}

	// }}}
	// {{{ protected function getSelectClause()

	protected function getSelectClause()
	{
		$clause = 'Orders.id, Orders.total, Orders.createdate,
					Orders.locale, Orders.notes, Orders.comments,
					Orders.billing_address, Orders.email, Orders.phone,
					(Orders.comments is not null and Orders.comments != %s)
						as has_comments';

		$clause = sprintf($clause,
			$this->app->db->quote('', 'text'));

		return $clause;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = 'select count(Orders.id) from Orders
					left outer join Account on Orders.account = Account.id
					left outer join OrderAddress as BillingAddress
						on Orders.billing_address = BillingAddress.id
					left outer join OrderAddress as ShippingAddress
						on Orders.shipping_address = ShippingAddress.id
					inner join Locale on Orders.locale = Locale.id
					inner join Region on Locale.region = Region.id
				where %s';

		$sql = sprintf($sql, $this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$orders = $this->getOrders($view,
			$pager->page_size, $pager->current_record);

		if (count($orders) > 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage('result', 'results');

		$store = new SwatTableStore();
		foreach ($orders as $order) {
			$ds = new SwatDetailsStore($order);
			$ds->fullname = $this->getOrderFullname($order);
			$ds->title = $this->getOrderTitle($order);
			$ds->has_notes = ($order->notes != '');
			$ds->has_comments = ($order->comments != '');

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getOrderFullname()

	protected function getOrderFullname(StoreOrder $order)
	{
		$fullname = null;

		if ($order->billing_address !== null) {
			$fullname = $order->billing_address->getFullname();
		} elseif ($order->email !== null) {
			$fullname = $order->email;
		} elseif ($order->phone !== null) {
			$fullname = $order->phone;
		}

		return $fullname;
	}

	// }}}
	// {{{ protected function getOrders()

	protected function getOrders($view, $limit, $offset)
	{
		$sql = 'select %s
				from Orders
					left outer join Account on Orders.account = Account.id
					left outer join OrderAddress as BillingAddress
						on Orders.billing_address = BillingAddress.id
					left outer join OrderAddress as ShippingAddress
						on Orders.shipping_address = ShippingAddress.id
					inner join Locale on Orders.locale = Locale.id
					inner join Region on Locale.region = Region.id
				where %s
				order by %s';

		$sql = sprintf($sql,
			$this->getSelectClause(),
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'Orders.id desc'));

		$this->app->db->setLimit($limit, $offset);

		$orders = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreOrderWrapper'));

		return $orders;
	}

	// }}}
	// {{{ protected function getOrderTitle()

	protected function getOrderTitle($order)
	{
		return sprintf(Store::_('Order %s'), $order->id);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/admin/styles/store-order-index.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
