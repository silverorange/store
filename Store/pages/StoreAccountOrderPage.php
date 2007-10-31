<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatWidgetCellRenderer.php';

require_once 'Site/exceptions/SiteNotFoundException.php';

require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/pages/StoreAccountPage.php';
require_once 'Store/StoreShippingAddressCellRenderer.php';
require_once 'Swat/SwatUI.php';

/**
 * Page to display old orders placed using an account
 *
 * Items in old orders can be added to the checkout card from this page.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 * @see       StoreOrder
 */
class StoreAccountOrderPage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-order.xml';

	/**
	 * @var StoreOrder
	 */
	protected $order = null;

	/**
	 * @var SwatUI
	 */
	protected $ui;

	// }}}
	// {{{ private properties

	/**
	 * @var array
	 */
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

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);
		$this->ui->init();

		$this->initAddButtonColumn();
	}

	// }}}
	// {{{ protected function initAddButtonColumn()

	protected function initAddButtonColumn()
	{
		// add item cell renderer
		$items_view = $this->ui->getWidget('items_view');
		$add_item_renderer = new SwatWidgetCellRenderer();
		$add_item_renderer->id = 'add_item_renderer';
		$add_item_button = new SwatButton('add_item_button');
		$add_item_button->title = Store::_('Add to Cart');
		$add_item_button->classes[] = 'cart-move';
		$add_item_button->classes[] = 'compact-button';
		$add_item_renderer->setPrototypeWidget($add_item_button);
		$add_item_column = new SwatTableViewColumn();
		$add_item_column->id = 'add_item_column';
		$add_item_column->addRenderer($add_item_renderer);
		$add_item_column->addMappingToRenderer($add_item_renderer,
			'id', 'replicator_id');

		$add_item_column->addMappingToRenderer($add_item_renderer,
			'show_add_button', 'visible', $add_item_button);

		$items_view->appendColumn($add_item_column);
	}

	// }}}
	// {{{ protected function loadOrder()

	protected function loadOrder()
	{
		$this->order = $this->app->session->account->orders->getByIndex(
			$this->id);

		if ($this->order === null)
			throw new SiteNotFoundException(
				sprintf('An order with an id of ‘%d’ does not exist.',
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
	// {{{ protected function addItem()

	/**
	 * @return StoreCartEntry the entry that was added.
	 */
	protected function addItem($item_id, StoreOrderItem $order_item)
	{
		if ($item_id !== null) {
			$cart_entry_class = SwatDBClassMap::get('StoreCartEntry');
			$cart_entry = new $cart_entry_class();
			$cart_entry->account = $this->app->session->getAccountId();

			// load item manually here so we can specify region
			$item_class = SwatDBClassMap::get('StoreItem');
			$item = new $item_class();
			$item->setDatabase($this->app->db);
			$item->setRegion($this->app->getRegion());
			$item->load($item_id);

			$cart_entry->item = $item;
			$cart_entry->quantity = $order_item->quantity;

			if ($this->app->cart->checkout->addEntry($cart_entry)) {
				$this->items_added[] = $item;
				return $cart_entry;
			}
		}

		$message = new SwatMessage(sprintf(Store::_(
			'Sorry, “%s” is no longer available.'),
			$order_item->sku));

		$this->ui->getWidget('message_display')->add($message);

		return null;
	}

	// }}}
	// {{{ protected function addAllItems()

	protected function addAllItems()
	{
		foreach ($this->order->items as $order_item) {
			$item_id = $this->findItem($order_item);
			$this->addItem($item_id, $order_item);
		}
	}

	// }}}
	// {{{ protected function addOneItem()

	protected function addOneItem()
	{
		$items_view = $this->ui->getWidget('items_view');
		$column = $items_view->getColumn('add_item_column');
		$renderer = $column->getRenderer('add_item_renderer');

		foreach ($this->order->items as $order_item) {
			$button = $renderer->getWidget($order_item->id);
			if ($button instanceof SwatButton && $button->hasBeenClicked()) {
				$item_id = $this->findItem($order_item);
				$this->addItem($item_id, $order_item);
			}
		}
	}

	// }}}
	// {{{ protected function findItem()

	/**
	 * @return integer
	 */
	protected function findItem(StoreOrderItem $order_item)
	{
		$item_id = $order_item->getAvailableItemId($this->app->getRegion());
		return $item_id;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

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

		if ($this->orderIsBlank()) {
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

		$store = $this->getOrderDetailsTableStore();
		$items_view->model = $store;

		$items_view->getRow('shipping')->value = $this->order->shipping_total;

		if ($this->order->tax_total > 0)
			$items_view->getRow('taxes')->value = $this->order->tax_total;
		else
			$items_view->getRow('taxes')->visible = false;

		$items_view->getRow('subtotal')->value = $this->order->getSubtotal();
		$items_view->getRow('total')->value = $this->order->total;

		$locale_id = $this->order->getInternalValue('locale');
		if ($this->app->getLocale() != $locale_id) {
			$this->ui->getWidget('currency_note')->content = sprintf(
				Store::_('Prices for this order are in %s.'),
				SwatString::getInternationalCurrencySymbol($locale_id));
		}
	}

	// }}}
	// {{{ protected function getOrderDetailsTableStore()

	protected function getOrderDetailsTableStore()
	{
		$store = $this->order->getOrderDetailsTableStore();
		$this->setItemPaths($store);

		return $store;
	}

	// }}}
	// {{{ protected function buildCartMessages()

	protected function buildCartMessages()
	{
		$num = count($this->items_added);
		if ($num > 0) {
			$message = new SwatMessage(sprintf(Store::ngettext(
				'“%1$s” added to %3$sshopping cart%4$s.',
				'%2$s items added to %3$sshopping cart%4$s.', $num),
				current($this->items_added)->sku, $num,
				'<a href="cart">', '</a>'));

			$message->content_type = 'text/xml';

			$this->ui->getWidget('message_display')->add($message);
		}
	}

	// }}}
	// {{{ protected function orderIsBlank()

	protected function orderIsBlank()
	{
		return (strlen($this->order->billing_address->fullname) == 0);
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

		foreach ($store as $row) {
			if (isset($paths[$row->id])) {
				$row->path = $paths[$row->id];
				$row->show_add_button = true;
			} else {
				$row->path = null;
				$row->show_add_button = false;
			}
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-account-order-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
