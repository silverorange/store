<?php

require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SiteArticlePage.php';
require_once 'Store/StoreUI.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatYUI.php';
require_once 'XML/RPCAjax.php';

/**
 *
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreQuickOrderPage extends SiteArticlePage
{
	// {{{ protected properties

	protected $form_xml = 'Store/pages/quick-order.xml';
	protected $cart_xml = 'Store/pages/quick-order-cart.xml';

	protected $form_ui;
	protected $cart_ui;

	protected $num_rows = 10;
	protected $items_added = array();

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->form_ui = new StoreUI();
		$this->form_ui->loadFromXML($this->form_xml);

		$form = $this->form_ui->getWidget('quick_order_form');
		$form->action = $this->source;

		$view = $this->form_ui->getWidget('quick_order_view');
		$view->model = $this->getQuickOrderTableStore();

		$column = $view->getColumn('item_selector_column');
		$item_selector =
			$column->getRendererByPosition()->getPrototypeWidget();

		$item_selector->db = $this->app->db;
		$item_selector->region = $this->app->getRegion();
		$item_selector->sku = null;

		$this->form_ui->init();

		$this->cart_ui = new StoreUI();
		$this->cart_ui->loadFromXML($this->cart_xml);

		$cart_form = $this->cart_ui->getWidget('cart_form');
		$cart_form->action = $this->source;

		$this->cart_ui->init();
	}

	// }}}
	// {{{ protected function getQuickOrderTableStore()

	/**
	 *
	 * @return SwatTableStore
	 */
	protected function getQuickOrderTableStore()
	{
		$store = new SwatTableStore();

		for ($i = 0; $i < $this->num_rows; $i++) {
			$row = null;
			$row->id = $i;
			$store->add($row);
		}

		return $store;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->processForm();
	}

	// }}}
	// {{{ protected function processForm()

	protected function processForm()
	{
		$this->form_ui->process();

		$form = $this->form_ui->getWidget('quick_order_form');
		$view = $this->form_ui->getWidget('quick_order_view');

		$quantity_column = $view->getColumn('quantity_column');
		$quantity_renderer = $quantity_column->getRenderer('renderer');

		$item_selector_column = $view->getColumn('item_selector_column');
		$item_selector_renderer =
			$item_selector_column->getRendererByPosition();

		$sku_column = $view->getColumn('sku_column');
		$sku_renderer = $sku_column->getRenderer('renderer');

		$message_display = $this->cart_ui->getWidget('messages');

		if ($form->isProcessed()) {
			foreach ($sku_renderer->getClonedWidgets() as $id => $sku_widget) {
				$item_selector = $item_selector_renderer->getWidget($id);
				$sku = $sku_widget->value;
				$quantity_widget = $quantity_renderer->getWidget($id);
				$quantity = $quantity_widget->value;

				// populate item flydown
				if ($sku !== null) {
					$item_selector->sku = $sku;
					$item_selector->db = $this->app->db;
					$item_selector->region = $this->app->getRegion();
					$item_selector->init();
				}

				$item_id = $item_selector->value;

				if ($item_id === null && $sku !== null) {
					$item_id = $this->getItemId($sku);
					if ($item_id === null) {
						$message = new SwatMessage(sprintf(Store::_(
							'“%s” is not an available %%s.'),
							$sku), SwatMessage::ERROR);

						$sku_widget->addMessage($message);
					}
				}

				if ($item_id !== null && !$sku_renderer->hasMessage($id) &&
					!$quantity_renderer->hasMessage($id) &&
					$this->addItem($item_id, $quantity, $sku)) {
					// clear fields after a successful add
					$sku_widget->value = '';
					$quantity_widget->value = 1;
					$item_selector->sku = null;
					$item_selector->init();
				}
			}

			if ($form->hasMessage()) {
				$message = new SwatMessage(Store::_('There is a problem with '.
					'one or more of the items you requested.'),
					SwatMessage::ERROR);

				$message->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->cart_ui->getWidget('messages')->add($message);
			}
		}
	}

	// }}}
	// {{{ protected function getItemId()

	/**
	 * Gets the item id for a given sku
	 *
	 * @param string $sku the sku of the item to get.
	 *
	 * @return integer the id of the item with the given sku or null if no
	 *                  item is found.
	 */
	protected function getItemId($sku)
	{
		$sql = sprintf('select id from Item
			inner join VisibleProductCache on
				Item.product = VisibleProductCache.product and
					VisibleProductCache.region = %s
			where lower(sku) = %s
			limit 1',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->app->db->quote($sku, 'text'));

		$item = SwatDB::queryOne($this->app->db, $sql);
		return $item;
	}

	// }}}
	// {{{ protected function addItem()

	protected function addItem($item_id, $quantity, $sku)
	{
		$cart_entry = $this->getCartEntry($item_id, $quantity, $sku);

		$added = $this->app->cart->checkout->addEntry($cart_entry);

		if ($added)
			$this->items_added[] = $cart_entry->item;

		return $added;
	}

	// }}}
	// {{{ protected function getCartEntry()

	protected function getCartEntry($item_id, $quantity, $sku)
	{
		$cart_entry_class = SwatDBClassMap::get('StoreCartEntry');
		$cart_entry = new $cart_entry_class();

		$this->app->session->activate();

		if ($this->app->session->isLoggedIn())
			$cart_entry->account = $this->app->session->getAccountId();
		else
			$cart_entry->sessionid = $this->app->session->getSessionId();

		$item_class = SwatDBClassMap::get('StoreItem');
		$item = new $item_class();
		$item->setDatabase($this->app->db);
		$item->setRegion($this->app->getRegion(), false);
		$item->load($item_id);

		// explicitly load product to get product path information
		$product_wrapper = SwatDBClassMap::get('StoreProductWrapper');
		$sql = sprintf('select id, title, shortname,
				getCategoryPath(primary_category) as path
			from Product
				left outer join ProductPrimaryCategoryView on product = id
			where id = %s',
			$this->app->db->quote(
				$item->getInternalValue('product'), 'integer'));

		$products = SwatDB::query($this->app->db, $sql, $product_wrapper);
		$product = $products->getFirst();
		$item->product = $product;

		$cart_entry->item = $item;
		$cart_entry->quantity = $quantity;
		$cart_entry->quick_order = true;

		return $cart_entry;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildCartView();
		$this->buildQuickOrderView();

		$this->layout->startCapture('content', true);
		$this->cart_ui->display();
		$this->layout->endCapture();

		$this->layout->startCapture('content');
		$this->form_ui->display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildCartView()

	protected function buildCartView()
	{
		$message_display = $this->cart_ui->getWidget('messages');
		foreach ($this->app->cart->checkout->getMessages() as $message)
			$message_display->add($message);

		$cart_view = $this->cart_ui->getWidget('cart_view');
		$cart_view->model = $this->getCartTableStore();

		$count = count($cart_view->model);
		if ($count > 0) {
			$frame = $this->cart_ui->getWidget('cart_frame');
			$frame->title = Store::ngettext(
				'The following item was added to your cart:',
				'The following items were added to your cart:',
				$count);

			$this->cart_ui->getWidget('cart_form')->visible = true;
		}
	}

	// }}}
	// {{{ protected function buildQuickOrderView()

	protected function buildQuickOrderView()
	{
		$view = $this->form_ui->getWidget('quick_order_view');

		if (count($view->model) == 0)
			$this->form_ui->getWidget('quick_order_form')->visible = false;
	}

	// }}}
	// {{{ protected function getCartTableStore()

	protected function getCartTableStore()
	{
		$ids = array();
		foreach ($this->items_added as $item)
			$ids[] = $item->id;

		$store = new SwatTableStore();

		$entries = $this->app->cart->checkout->getEntries();
		foreach ($entries as $entry) {
			// filter entries by added items 
			if (in_array($entry->item->id, $ids)) {
				$row = $this->getCartTableStoreRow($entry);

				$store->add($row, $entry->item->id);
			}
		}

		return $store;
	}

	// }}}
	// {{{ protected function getCartTableStoreRow()

	protected function getCartTableStoreRow($entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->quantity = $entry->getQuantity();
		$ds->description = $entry->item->getDescription();
		$ds->price = $entry->getCalculatedItemPrice();
		$ds->extension = $entry->getExtension();
		$ds->product_link = 'store/'.$entry->item->product->path;

		return $ds;
	}

	//}}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		static $translations_displayed = false;

		$id = 'quick_order';
		$item_selector_id = 'item_selector';

		$javascript = '';
		if (!$translations_displayed) {
			$javascript.= sprintf("StoreQuickOrder.loading_text = '%s';\n",
				Store::_('loading …'));

			$translations_displayed = true;
		}

		$javascript.= sprintf(
			"var %s_obj = new StoreQuickOrder('%s', '%s', %s);",
			$id, $id, $item_selector_id, $this->num_rows);

		return $javascript;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(XML_RPCAjax::getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('event', 'animation'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-quick-order-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-quick-order-page.css',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->cart_ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntrySet(
			$this->form_ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
