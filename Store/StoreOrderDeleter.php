<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/Store.php';
require_once 'Store/StorePrivateDataDeleter.php';
require_once 'Store/dataobjects/StoreAddress.php';
require_once 'Store/dataobjects/StoreOrder.php';
require_once 'Store/dataobjects/StoreOrderWrapper.php';

/**
 * Removes personal data from expired orders
 *
 * @package   Store
 * @copyright 2007-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderDeleter extends StorePrivateDataDeleter
{
	// {{{ class constants

	/**
	 * How many records to process in a single iteration
	 *
	 * @var integer
	 */
	const DATA_BATCH_SIZE = 100;

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->app->debug("\n".Store::_('Orders')."\n------\n");

		$total = $this->getTotal();
		if ($total == 0) {
			$this->app->debug(Store::_('No expired orders found. '.
				'No private data removed.')."\n");
		} else {
			$this->app->debug(
				sprintf(Store::_('Found %s expired orders for deletion:')."\n",
				$total));

			if (!$this->app->isDryRun()) {

				/*
				 * Transactions are not used because dataobject saving already
				 * uses transactions.
				 */

				$orders = $this->getOrders();
				$count = count($orders);
				while ($count > 0) {
					foreach ($orders as $order) {
						$this->app->debug(sprintf(
							'=> '.Store::_('cleaning order %s ... '),
							$order->id));

						$this->cleanOrder($order);
						$order->save();
						$this->app->debug("done\n");
					}

					// get next batch of orders
					$orders = $this->getOrders();
					$count = count($orders);
				}

			} else {
				$this->app->debug('=> '.
					Store::_('not cleaning because dry-run is on')."\n");
			}

			$this->app->debug(
				Store::_('Finished cleaning expired orders.')."\n");
		}
	}

	// }}}
	// {{{ protected function cleanOrder()

	/**
	 * Clears an order of private data
	 *
	 * @param StoreOrder $order the order to clear.
	 */
	protected function cleanOrder(StoreOrder $order)
	{
		$order->email = null;
		$order->phone = null;
		$order->comments = null;
		$order->notes = null;

		if (count($order->payment_methods)) {
			foreach ($order->payment_methods as $payment_method) {
				$payment_method->card_fullname = null;
				$payment_method->card_number_preview = null;
				$payment_method->card_number = null;
				$payment_method->card_expiry = null;
			}
		}

		$order->billing_address->fullname = '';
		$order->billing_address->company = '';
		$order->billing_address->phone = '';
		$order->billing_address->line1 = '';
		$order->billing_address->line2 = null;
		$order->billing_address->city = '';
		$order->billing_address->postal_code =
			$this->cleanPostalCode($order->billing_address);

		$order->shipping_address->fullname = '';
		$order->shipping_address->company = '';
		$order->shipping_address->phone = '';
		$order->shipping_address->line1 = '';
		$order->shipping_address->line2 = null;
		$order->shipping_address->city = '';
		$order->shipping_address->postal_code =
			$this->cleanPostalCode($order->shipping_address);
	}

	// }}}
	// {{{ protected function cleanPostalCode()

	/**
	 * Removes personally identifiable information from postal codes and Zip
	 * Codes
	 *
	 * @param StoreAddress $address the address which contains the postal code
	 *                               to clean.
	 *
	 * @return string the postal code with personally identifiable information
	 *                 removed.
	 */
	protected function cleanPostalCode(StoreAddress $address)
	{
		$postal_code = '';
		switch ($address->getInternalValue('country')) {
		case 'CA':
			// leave off local delivery unit from postal code
			$postal_code = substr($address->postal_code, 0, 3);
			break;
		case 'US':
			// leave off +4 from Zip Code
			$postal_code = substr($address->postal_code, 0, 5);
			break;
		}

		return $postal_code;
	}

	// }}}
	// {{{ protected function getOrders()

	protected function getOrders()
	{
		// join billing address for where clause
		$sql = 'select Orders.* from Orders
			inner join OrderAddress on Orders.billing_address = OrderAddress.id
			%s';

		$sql = sprintf($sql,
			$this->getWhereClause());

		$this->app->db->setLimit(self::DATA_BATCH_SIZE);

		$wrapper_class = SwatDBClassMap::get('StoreOrderWrapper');
		$orders = SwatDB::query($this->app->db, $sql, $wrapper_class);

		return $orders;
	}

	// }}}
	// {{{ protected function getTotal()

	protected function getTotal()
	{
		$sql = 'select count(Orders.id) from Orders
			inner join OrderAddress on Orders.billing_address = OrderAddress.id
			%s';

		$sql = sprintf($sql,
			$this->getWhereClause());

		$total = SwatDB::queryOne($this->app->db, $sql);

		return $total;
	}

	// }}}
	// {{{ protected function getExpiryDate()

	protected function getExpiryDate()
	{
		$expiry_orders = $this->app->config->expiry->orders;
		$expiry_date = new SwatDate(strtotime('-'.$expiry_orders),
			DATE_FORMAT_UNIXTIME);

		$expiry_date->toUTC();
		return $expiry_date;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$expiry_date = $this->getExpiryDate();
		$instance_id = $this->app->getInstanceId();

		$sql = 'where length(OrderAddress.fullname) > 0
			and createdate < %s
			and instance %s %s';

		$sql = sprintf($sql,
			$this->app->db->quote($expiry_date->getDate(), 'date'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		return $sql;
	}

	// }}}
}

?>
