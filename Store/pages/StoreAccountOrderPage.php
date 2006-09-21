<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatWidgetCellRenderer.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Store/StoreUI.php';
require_once 'Store/pages/StoreAccountPage.php';
require_once 'Store/StoreShippingAddressCellRenderer.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAccountOrderPage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-order.xml';

	protected $order = null;
	protected $ui;

	// }}}
	// {{{ private properties

	private $id;
	private $items_added = array();

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$id = 0)
	{
		parent::__construct($app, $layout);
		$this->id = intval($id);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->loadOrder();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);
		$this->ui->init();

		// add item cell renderer
		$items_view = $this->ui->getWidget('items_view');
		$add_item_renderer = new SwatWidgetCellRenderer();
		$add_item_renderer->id = 'add_item_renderer';
		$add_item_button = new SwatButton('add_item_button');
		$add_item_button->title = 'Add to Cart';
		$add_item_button->classes[] = 'cart-move';
		$add_item_button->classes[] = 'compact-button';
		$add_item_renderer->setPrototypeWidget($add_item_button);
		$add_item_column = new SwatTableViewColumn();
		$add_item_column->id = 'add_item_column';
		$add_item_column->addRenderer($add_item_renderer);
		$add_item_column->addMappingToRenderer($add_item_renderer,
			'id', 'replicator_id');

		$add_item_column->addMappingToRenderer($add_item_renderer,
			'is_available', 'visible', $add_item_button);

		$items_view->appendColumn($add_item_column);
	}

	// }}}
	// {{{ private function loadOrder()

	private function loadOrder()
	{
		$this->order = $this->app->session->account->orders->getByIndex($this->id);

		if ($this->order === null)
			throw new SiteNotFoundException(
				sprintf('An order with an id of %d does not exist.',
				$this->id));
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('form');

		$form->process();

		if ($form->isProcessed()) {
			if ($this->ui->getWidget('add_all_items')->hasBeenClicked())
				$this->addAllItems();
			else
				$this->addOneItem();
		}
	}

	// }}}
	// {{{ private function addAllItems()

	private function addAllItems()
	{
		foreach ($this->order->items as $item)
			$this->addItem($item);
	}

	// }}}
	// {{{ private function addOneItem()

	private function addOneItem()
	{
		$items_view = $this->ui->getWidget('items_view');
		$column = $items_view->getColumn('add_item_column');
		$renderer = $column->getRenderer('add_item_renderer');

		foreach ($this->order->items as $item) {
			$button = $renderer->getWidget($item->id);
			if ($button->hasBeenClicked())
				$this->addItem($item);
		}
	}

	// }}}
	// {{{ private function addItem()

	private function addItem($order_item)
	{
		$item_id = $order_item->getAvailableItemId($this->app->getRegion());

		if ($item_id !== null) {
			$cart_entry = new CartEntry();
			$cart_entry->account = $this->app->session->getAccountID();

			// load item manually here so we can specify region
			$item = new Item();
			$item->setDatabase($this->app->db);
			$item->setRegion($this->app->getRegion()->id);
			$item->load($item_id);

			$cart_entry->item = $item;
			$cart_entry->quantity = $order_item->quantity;
			$cart_entry->quick_order = false;
			$cart_entry->pay_by_installments = $order_item->pay_by_installments;

			if ($this->app->cart->checkout->addEntry($cart_entry)) {
				$this->items_added[] = $item;
				return true;
			}
		}

		$msg = new SwatMessage(sprintf('Sorry, “%s” is no longer available.',
			$order_item->sku),
			SwatMessage::NOTIFICATION);

		$this->ui->getWidget('message_display')->add($msg);

		return false;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-account-order-page.css',
			Store::PACKAGE_ID));

		$this->ui->getWidget('form')->action = $this->source;

		$this->buildCartMessages();

		$title = $this->order->getTitle();
		$this->layout->data->title = $title;
		$this->layout->navbar->createEntry($title);

		$this->buildOrderDetails();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildOrderDetails()

	protected function buildOrderDetails()
	{
		$details_view =  $this->ui->getWidget('order_details');
		$details_view->data = new SwatDetailsStore($this->order);

		$createdate_column = $details_view->getField('createdate');
		$createdate_renderer = $createdate_column->getFirstRenderer();
		$createdate_renderer->display_time_zone =
			$this->app->default_time_zone;

		$order_is_blank =
			(strlen($this->order->billing_address->fullname) == 0);

		if ($order_is_blank) {
			$details_view->getField('email')->visible = false;
			$details_view->getField('phone')->visible = false;
			$details_view->getField('comments')->visible = false;
			$details_view->getField('payment_method')->visible = false;
			$details_view->getField('billing_address')->visible = false;
			$details_view->getField('shipping_address')->visible = false;
		} else {
			if ($this->order->comments === null)
				$details_view->getField('comments')->visible = false;

			if ($this->order->phone === null)
				$details_view->getField('phone')->visible = false;
		}

		$items_view = $this->ui->getWidget('items_view');

		$items_view->model = $this->order->getOrderDetailsTableStore();
		$this->setItemPaths($items_view->model);

		$items_view->getRow('shipping')->value = $this->order->shipping_total;
		$items_view->getRow('subtotal')->value = $this->order->getSubtotal();
		$items_view->getRow('total')->value = $this->order->total;
	}

	// }}}
	// {{{ private function setItemPaths()

	private function setItemPaths($store)
	{
		$sql = sprintf('select OrderItem.id,
				getCategoryPath(ProductPrimaryCategoryView.primary_category) as path,
				Product.shortname
			from OrderItem
				left outer join Item as MatchItem on MatchItem.sku = OrderItem.sku
				left outer join AvailableItemView on AvailableItemView.item = MatchItem.id
					and AvailableItemView.region = %s
				left outer join Item on AvailableItemView.item = Item.id
				left outer join Product on Item.product = Product.id
				left outer join ProductPrimaryCategoryView
					on Item.product = ProductPrimaryCategoryView.product
			where OrderItem.ordernum = %s',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->app->db->quote($this->order->id, 'integer'));

		$item_paths = SwatDB::query($this->app->db, $sql);

		$paths = array();

		foreach ($item_paths as $row)
			if ($row->path !== null)
				$paths[$row->id] = 'store/'.$row->path.'/'.$row->shortname;

		foreach ($store->getRows() as $row) {
			if (isset($paths[$row->id])) {
				$row->path = $paths[$row->id];
				$row->is_available = true;
			} else {
				$row->path = null;
				$row->is_available = false;
			}
		}
	}

	// }}}
	// {{{ private function buildCartMessages()

	private function buildCartMessages()
	{
		$num = count($this->items_added);
		if ($num > 0) {
			$msg = new SwatMessage(sprintf(ngettext(
				'“%1$s” added to shopping cart.',
				'%2$s items added to shopping cart.', $num),
				current($this->items_added)->sku, $num),
				SwatMessage::NOTIFICATION);

			$this->ui->getWidget('message_display')->add($msg);
		}
	}

	// }}}
}

?>
