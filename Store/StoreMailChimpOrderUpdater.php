<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Store/dataobjects/StoreMailChimpOrder.php';
require_once 'Store/dataobjects/StoreMailChimpOrderWrapper.php';
require_once 'Store/dataobjects/StoreOrderWrapper.php';
require_once 'Store/dataobjects/StoreOrderItemWrapper.php';
require_once 'Deliverance/Deliverance.php';
require_once 'Deliverance/DeliveranceMailChimpList.php';
require_once 'Deliverance/exceptions/DeliveranceException.php';


/**
 * Command line application used to send mailing list orders to MailChimp
 *
 * If both email_id and compaign_id are set on the order then
 * {@link http://www.mailchimp.com/api/1.3/campaignecommorderadd.func.php MailChimp.campaignEcommAddOrder()}
 * is used to submit the order to MailChimp otherwise if only the email_id is
 * set then {@link http://www.mailchimp.com/api/1.3/ecommorderadd.func.php MailChimp.ecommAddOrder()}
 * is used to submit the order to MailChimp. If the order fails to send on three
 * different occasions than an error date is set on the order and no more
 * submission attempts are made.
 *
 * @package   Store
 * @copyright 2010-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreMailChimpOrderUpdater extends SiteCommandLineApplication
{
	// {{{ constants

	/**
	 * The maximum number of attempts made to send an order to MailChimp
	 *
	 * @var integer
	 */
	const MAX_SEND_ATTEMPTS = 3;

	// }}}
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ protected properties

	/**
	 * The list to add the orders to.
	 *
	 * @var DeliveranceMailChimpList
	 */
	protected $list;

	// }}}
	// {{{ public function run()

	/**
	 * Runs this application
	 */
	public function run()
	{
		$this->initInternal();

		$this->lock();
		$this->runInternal();
		$this->unlock();
	}

	// }}}
	// {{{ protected function initInternal()

	/**
	 * Initializes this application
	 */
	protected function initInternal()
	{
		$this->initModules();
		$this->parseCommandLineArguments();
		$this->initList();
	}

	// }}}
	// {{{ protected function initList()

	/**
	 * Initializes the list
	 */
	protected function initList()
	{
		$this->list = new DeliveranceMailChimpList($this);
		$this->list->setTimeout(
			$this->config->mail_chimp->script_connection_timeout);
	}

	// }}}
	// {{{ protected function runInternal()

	/**
	 * Runs this application
	 */
	protected function runInternal()
	{
		$orders = $this->getOrders();
		$this->debug(sprintf("Found %s Orders:\n", count($orders)), true);

		$success   = false;
		$not_found = false;

		foreach ($orders as $order) {
			try {
				$this->debug('Sending order to MailChimp ... ');
				$success = $this->sendOrder($order);
			} catch (DeliveranceException $e) {
				switch ($e->getCode()) {
				case DeliveranceMailChimpList::NOT_FOUND_ERROR_CODE:
					// if the email address isn't found, consider it a success.
					$not_found = true;
					$success   = true;
					break;
				default:
					throw $e;
				}
			} catch (Exception $e) {
				// TODO: Some of these should be logged while others shouldn't
				$e = new DeliveranceException($e);
				$e->processAndContinue();
			}

			if ($success === true) {
				$message = ($not_found === true) ?
					sprintf('subscriber ‘%s’ not found.',
						$order->email_id):
					'sent';

				$this->debug($message."\n");
				$order->delete();
			} else {
				$order->send_attempts += 1;
				if ($order->send_attempts >= self::MAX_SEND_ATTEMPTS) {
					$this->debug("maximum send attempts reached.\n");

					$order->error_date = new SwatDate();
					$order->error_date->toUTC();
				} else {
					$this->debug("error. Will retry later.\n");
				}

				$order->save();
			}
		}


		$this->debug("All Done.\n", true);
	}

	// }}}
	// {{{ protected function sendOrder()

	protected function sendOrder(StoreMailChimpOrder $order)
	{
		$info = $this->getOrderInfo($order);
		return $this->list->addOrder($info);
	}

	// }}}
	// {{{ protected function getOrders()

	/**
	 * Gets all outstanding MailChimp orders
	 *
	 * @return StoreMailChimpOrderWrapper a recordset wrapper of all
	 *                                     outstanding orders.
	 */
	protected function getOrders()
	{
		$wrapper = SwatDBClassMap::get('StoreMailChimpOrderWrapper');

		$sql = 'select * from MailChimpOrderQueue where error_date %s %s';
		$sql = sprintf($sql,
			SwatDB::equalityOperator(null),
			$this->db->quote(null));

		$orders = SwatDB::query($this->db, $sql, $wrapper);

		// efficiently load orders
		$ordernums_sql = 'select * from Orders where id in (%s)';
		$ordernums = $orders->loadAllSubDataObjects(
			'ordernum',
			$this->db,
			$ordernums_sql,
			SwatDBClassMap::get('StoreOrderWrapper'));

		// efficiently load order items
		if ($ordernums !== null) {
			$ordernums->loadAllSubRecordsets(
				'items',
				SwatDBClassMap::get('StoreOrderItemWrapper'),
				'OrderItem',
				'ordernum');
		}

		return $orders;
	}

	// }}}
	// {{{ protected function getOrderInfo()

	protected function getOrderInfo(StoreMailChimpOrder $order)
	{
		$order_date = clone $order->ordernum->createdate;

		// store_id has a max length of 20 chars.
		$store_id = substr(
			parse_url($this->config->uri->absolute_base, PHP_URL_HOST), 0, 20);

		$info = array(
			'id'         => $order->ordernum->id,
			'email_id'   => $order->email_id,
			'total'      => $order->ordernum->total,
			'tax'        => $order->ordernum->tax_total,
			'shipping'   => $order->ordernum->shipping_total,
			'store_id'   => $store_id,
			'store_name' => $this->config->site->title,
			'plugin_id'  => $this->config->mail_chimp->plugin_id,
			'order_date' => $order_date->formatLikeStrftime('%Y-%m-%d %T'),
		);

		if ($order->campaign_id != '') {
			$info['campaign_id'] = $order->campaign_id;
		}

		$info['items'] = $this->getItemsInfo($order->ordernum);

		return $info;
	}

	// }}}
	// {{{ protected function getItemsInfo()

	protected function getItemsInfo(StoreOrder $order)
	{
		$items = array();

		foreach($order->items as $item) {
			$product  = $this->getProduct($item);

			$entry = array(
				'product_id'    => $product->id,
				'product_name'  => $product->title,
				'qty'           => $item->quantity,
				'cost'          => $item->price,
			);

			// products with no category won't have a primary category.
			if ($product->primary_category !== null) {
				$entry['category_id']   = $product->primary_category->id;
				$entry['category_name'] = $product->primary_category->title;
			}

			$items[] = $entry;
		}

		return $items;
	}

	// }}}
	// {{{ protected function getProduct()

	protected function getProduct(StoreOrderItem $item)
	{
		$sql = 'select Product.*, ProductPrimaryCategoryView.primary_category
			from Product
			left outer join ProductPrimaryCategoryView
				on Product.id = ProductPrimaryCategoryView.product
			where id = %s';

		$sql = sprintf($sql, $this->db->quote($item->product, 'integer'));

		$rs = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));

		return $rs->getFirst();
	}

	// }}}

	// boilerplate code
	// {{{ protected function configure()

	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		$this->database->dsn = $config->database->dsn;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
		parent::addConfigDefinitions($config);

		$config->addDefinitions(Store::getConfigDefinitions());
		$config->addDefinitions(Deliverance::getConfigDefinitions());
	}

	// }}}
}

?>
