<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Store/Store.php';
require_once 'Admin/Admin.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Class for populating the ProductPopularProductBinding table which caches
 * values used to display "Customers who bought this also bought X" data.
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePopularProductIndexer extends SiteCommandLineApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $title, $documentation)
	{
		parent::__construct($id, $title, $documentation);

		$all = new SiteCommandLineArgument(
			array('-A', '--all'), 'reindex',
			Store::_('Re-indexes all orders rather than just updating '.
				'indexes for new orders.'));

		$this->addCommandLineArgument($all);
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();
		$this->index();
	}

	// }}}
	// {{{ public function reindex()

	public function reindex()
	{
		$this->debug(Store::_('Reindexing all orders ... '));

		SwatDB::exec($this->db, 'truncate ProductPopularProductBinding');
		SwatDB::exec($this->db, sprintf('update Orders set
			popular_products_processed = %s',
			$this->db->quote(false, 'boolean')));
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
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
		$config->addDefinitions(Admin::getConfigDefinitions());
	}

	// }}}
	// {{{ protected function index()

	/**
	 * Indexes documents
	 *
	 * Subclasses should override this method to add or remove additional
	 * indexed tables.
	 */
	protected function index()
	{
		$orders = $this->getOrders();
		$total_orders = count($orders);
		$count = 0;

		$this->debug(Store::_('Indexing orders ... ').'   ');

		foreach ($orders as $order) {
			if ($count % 10 == 0) {
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total_orders) * 100));
			}

			// ProductPopularity rows
			foreach ($this->getProductPopularityData($order->id) as $row)
				$this->insertPopularProduct($row);

			// ProductPopularProductBinding rows
			foreach ($this->getProductCrossList($order->id) as $row)
				$this->insertProductPopularProductBinding($row);

			SwatDB::updateColumn($this->db, 'Orders',
				'boolean:popular_products_processed',
				true, 'id', array($order->id));

			$count++;
		}

		$this->debug(str_repeat(chr(8), 3));
		$this->debug(Store::_('done')."\n\n");
	}

	// }}}
	// {{{ protected function insertPopularProduct()

	protected function insertPopularProduct($row)
	{
		if ($row->popularity === null)
			SwatDB::insertRow($this->db, 'ProductPopularity',
				array('integer:product',
					'integer:order_count',
					'float:total_quantity',
					'float:total_sales'),
				array('product' => $row->product,
					'order_count' => 1,
					'total_quantity' => $row->quantity,
					'total_sales' => $row->extension));
		else
			SwatDB::exec($this->db, sprintf('
				update ProductPopularity
				set order_count = order_count + 1,
					total_quantity = total_quantity + %s,
					total_sales = total_sales + %s
				where product = %s',
				$this->db->quote($row->quantity, 'integer'),
				$this->db->quote($row->extension, 'float'),
				$this->db->quote($row->product, 'integer')));
	}

	// }}}
	// {{{ protected function insertProductPopularProductBinding()

	protected function insertProductPopularProductBinding($row)
	{
		if ($row->order_count === null) {
			SwatDB::insertRow($this->db, 'ProductPopularProductBinding',
				array('integer:source_product',
					'integer:related_product',
					'integer:order_count',
					'float:total_quantity',
					'float:total_sales'),
				array('source_product' => $row->source_product,
					'related_product' => $row->related_product,
					'order_count' => 1,
					'total_quantity' => $row->quantity,
					'total_sales' => $row->extension));
		} else {
			SwatDB::exec($this->db, sprintf('
				update ProductPopularProductBinding
				set order_count = order_count + 1,
					total_quantity = total_quantity + %s,
					total_sales = total_sales + %s
				where source_product = %s and
					related_product = %s',
				$this->db->quote($row->quantity, 'integer'),
				$this->db->quote($row->extension, 'float'),
				$this->db->quote($row->source_product, 'integer'),
				$this->db->quote($row->related_product, 'integer')));
		}
	}

	// }}}
	// {{{ protected function getOrders()

	/**
	 * Gets a list of orders to process
	 */
	protected function getOrders()
	{
		$this->debug(Store::_('Querying orders ... '));

		$sql = sprintf('select Orders.id from Orders
			where Orders.popular_products_processed = %s',
			$this->db->quote(false, 'boolean'));

		$orders = SwatDB::query($this->db, $sql);

		$this->debug(Store::_('done')."\n\n");

		return $orders;
	}

	// }}}
	// {{{ protected function getProductCrossList()

	/**
	 * Gets a list of related products
	 *
	 * Selects a cross-list of all products in an order and check if
	 * a relation aleady exists between the products in the popular
	 * products binding table
	 */
	protected function getProductCrossList($order_id)
	{
		$sql = 'select OrderProductCrossListView.*,
			ProductPopularProductBinding.order_count
			from OrderProductCrossListView
			left outer join ProductPopularProductBinding
				on ProductPopularProductBinding.source_product =
					OrderProductCrossListView.source_product
				and ProductPopularProductBinding.related_product =
					OrderProductCrossListView.related_product
			where ordernum = %s';

		$sql = sprintf($sql,
			$this->db->quote($order_id, 'integer'));

		return SwatDB::query($this->db, $sql);
	}

	// }}}
	// {{{ protected function getProductPopularityData()

	protected function getProductPopularityData($order_id)
	{
		$sql = 'select OrderProductPopularityView.*,
				ProductPopularity.order_count as popularity
			from OrderProductPopularityView
			left outer join ProductPopularity
				on ProductPopularity.product = OrderProductPopularityView.product
			where ordernum = %s';

		$sql = sprintf($sql,
			$this->db->quote($order_id, 'integer'));

		return SwatDB::query($this->db, $sql);
	}

	// }}}
}

?>
