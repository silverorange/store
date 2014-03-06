<?php

require_once 'Site/admin/components/Account/Details.php';
require_once 'Store/StoreAddressCellRenderer.php';
require_once 'Store/StorePaymentMethodCellRenderer.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/dataobjects/StoreAccount.php';

/**
 * Details page for accounts
 *
 * @package   Store
 * @copyright 2006-2014 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountDetails extends SiteAccountDetails
{
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// set a default order on the login history table view
		$view = $this->ui->getWidget('orders_view');
		$view->setDefaultOrderbyColumn(
			$view->getColumn('createdate'),
			SwatTableViewOrderableColumn::ORDER_BY_DIR_DESCENDING);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/admin/components/Account/details.xml';
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($view->id) {
		case 'addresses_view':
			$this->processAddressActions($view, $actions);
			return;

		case 'payment_methods_view':
			$this->processPaymentMethodActions($view, $actions);
			return;
		}
	}

	// }}}
	// {{{ private function processAddressActions()

	private function processAddressActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'address_delete':
			$this->app->replacePage('Account/AddressDelete');
			$this->app->getPage()->setItems($view->getSelection());
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
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$toolbar = $this->ui->getWidget('address_details_toolbar');
		$toolbar->setToolLinkValues($this->id);

		// set default time zone for orders
		$this->setTimeZone();
	}

	// }}}
	// {{{ protected function setTimeZone()

	protected function setTimeZone()
	{
		$date_column =
			$this->ui->getWidget('orders_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
		case 'orders_view':
			return $this->getOrdersTableModel($view);
		case 'addresses_view':
			return $this->getAddressesTableModel($view);
		case 'payment_methods_view':
			return $this->getPaymentMethodsTableModel($view);
		}
	}

	// }}}
	// {{{ protected function getOrdersTableModel()

	protected function getOrdersTableModel(SwatTableView $view)
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

		$store = SwatDB::query($this->app->db, $sql);

		return $store;
	}

	// }}}
	// {{{ protected function getAddressesTableModel()

	protected function getAddressesTableModel(SwatTableView $view)
	{
		$account = $this->getAccount();
		$billing_id = $account->getInternalValue('default_billing_address');
		$shipping_id = $account->getInternalValue('default_shipping_address');

		$ts = new SwatTableStore();

		foreach ($account->addresses as $address) {
			$ds = new SwatDetailsStore($address);
			ob_start();
			$address->displayCondensed();
			$ds->address = ob_get_clean();
			$ds->default_billing_address = ($address->id == $billing_id);
			$ds->default_shipping_address = ($address->id == $shipping_id);
			$ts->add($ds);
		}

		return $ts;
	}

	// }}}
	// {{{ protected function getPaymentMethodsTableModel()

	protected function getPaymentMethodsTableModel(SwatTableView $view)
	{
		$wrapper = SwatDBClassMap::get('StoreAccountPaymentMethodWrapper');

		$sql = sprintf('select * from AccountPaymentMethod
			where account = %s',
			$this->app->db->quote($this->id, 'integer'));

		$payment_methods = SwatDB::query($this->app->db, $sql, $wrapper);

		$store = new SwatTableStore();
		foreach ($payment_methods as $method) {
			$ds = new SwatDetailsStore($method);
			$ds->payment_method = $method;
			ob_start();
			$method->showCardNumber(true);
			$method->showCardExpiry(true);
			$method->showCardFullname(true);
			$method->display(true);
			$ds->payment_method = ob_get_clean();
			$store->add($ds);
		}

		return $store;
	}

	// }}}
}

?>
