<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Store/StoreAddressCellRenderer.php';
require_once 'Store/StorePaymentMethodCellRenderer.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/StoreClassMap.php';

/**
 * Details page for accounts
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountDetails extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Account/details.xml';

	/**
	 * @var integer
	 */
	protected $id;

	// }}}
	// {{{ private propeties

	/**
	 * @var StoreAccount
	 */
	private $account;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);

		$this->id = SiteApplication::initVar('id');
	}

	// }}}
	// {{{ protected function getAccount()

	protected function getAccount()
	{
		if ($this->account === null) {
			$class_map = StoreClassMap::instance();
			$account_class = $class_map->resolveClass('StoreAccount');

			$this->account = new $account_class();
			$this->account->setDatabase($this->app->db);

			if (!$this->account->load($this->id))
				throw new AdminNotFoundException(sprintf(
					Store::_('A account with an id of ‘%d’ does not exist.'),
					$this->id));
		}

		return $this->account;
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($view->id) {
		case 'invoices_view':
			$this->processInvoiceActions($view, $actions);
			return;

		case 'addresses_view':
			$this->processAddressActions($view, $actions);
			return;

		case 'payment_methods_view':
			$this->processPaymentMethodActions($view, $actions);
			return;
		}
	}

	// }}}
	// {{{ private function processInvoiceActions()

	private function processInvoiceActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'invoice_delete':
			$this->app->replacePage('Invoice/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}
	// {{{ private function processAddressActions()

	private function processAddressActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'address_delete':
			$this->app->replacePage('Account/AddressDelete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}
	// {{{ private function processPaymentMethodActions()

	private function processPaymentMethodActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'payment_method_delete':
			$this->app->replacePage('Account/PaymentMethodDelete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	public function buildInternal() 
	{
		parent::buildInternal();

		$this->buildAccountDetails();

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues($this->id);

		$toolbar = $this->ui->getWidget('invoice_toolbar');
		$toolbar->setToolLinkValues($this->id);

		$toolbar = $this->ui->getWidget('address_details_toolbar');
		$toolbar->setToolLinkValues($this->id);

		// set default time zone for orders & invoices
		$this->setTimeZone();

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function setTimeZone()

	protected function setTimeZone()
	{
		$date_column =
			$this->ui->getWidget('orders_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		$date_column =
			$this->ui->getWidget('invoices_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view) 
	{
		switch ($view->id) {
			case 'invoices_view':
				return $this->getInvoicesTableStore($view);
			case 'orders_view':
				return $this->getOrdersTableStore($view);
			case  'addresses_view':
				return $this->getAddressesTableStore($view);
			case 'payment_methods_view':
				return $this->getPaymentMethodsTableStore($view);
		}
	}

	// }}}
	// {{{ protected function getInvoicesTableStore()

	protected function getInvoicesTableStore($view) 
	{
		$sql = 'select * from Invoice
			where Invoice.account = %s
			order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view,
				'Invoice.createdate desc, Invoice.id'));

		$invoices =  SwatDB::query($this->app->db, $sql, 'StoreInvoiceWrapper');

		$store = new SwatTableStore();

		foreach ($invoices as $invoice) {
			$ds = new SwatDetailsStore($invoice);
			$ds->subtotal = $invoice->getSubtotal();
			$store->addRow($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getOrdersTableStore()

	protected function getOrdersTableStore($view) 
	{
		$sql = 'select Orders.id,
					Orders.account as account_id,
					Orders.total,
					Orders.createdate
				from Orders
				where Orders.account = %s
				order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view,
				'Orders.createdate desc, Orders.id'));

		return SwatDB::query($this->app->db, $sql, 'AdminTableStore');
	}

	// }}}
	// {{{ protected function getAddressesTableStore()

	protected function getAddressesTableStore($view) 
	{
		$sql = 'select * from AccountAddress where AccountAddress.account = %s
				order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view, 'AccountAddress.createdate desc'));

		$rs = SwatDB::query($this->app->db, $sql);
		$ts = new SwatTableStore();

		$class_map = StoreClassMap::instance();
		$class_name = $class_map->resolveClass('StoreAccountAddress');
		foreach ($rs as $row) {
			$new_row = null;
			$new_row->id = $row->id;
			$new_row->default_address = $row->default_address;
			$new_row->address = new $class_name($row);
			$new_row->address->setDatabase($this->app->db);
			$ts->addRow($new_row);
		}

		return $ts;
	}

	// }}}
	// {{{ protected function getPaymentMethodsTableStore()

	protected function getPaymentMethodsTableStore($view) 
	{
		$class_map = StoreClassMap::instance();
		$wrapper = $class_map->resolveClass('StoreAccountPaymentMethodWrapper');

		$sql = sprintf('select * from AccountPaymentMethod where account = %s',
			$this->app->db->quote($this->id, 'integer'));

		$payment_methods = SwatDB::query($this->app->db, $sql, $wrapper);

		$store = new SwatTableStore();
		foreach ($payment_methods as $method) {
			$ds = new SwatDetailsStore($method);
			$ds->payment_method = $method;
			$store->addRow($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getAccountDetailsStore()

	protected function getAccountDetailsStore()
	{
		return new SwatDetailsStore($this->getAccount());
	}

	// }}}
	// {{{ protected function buildAccountDetails()

	protected function buildAccountDetails() 
	{
		$ds = $this->getAccountDetailsStore();

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Store::_('Account');
		$details_frame->subtitle = $ds->fullname;

		$details_view = $this->ui->getWidget('details_view');

		$date_field = $details_view->getField('createdate');
		$date_renderer = $date_field->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		$details_view->data = $ds;
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$fullname = $this->getAccount()->fullname;
		$this->navbar->addEntry(new SwatNavBarEntry($fullname));
		$this->title = $fullname;
	}

	// }}}
}

?>
