<?php

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/StoreUI.php';
require_once 'Store/pages/StoreStorePage.php';
require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/dataobjects/StoreCategory.php';
require_once 'Store/StoreMessage.php';

/**
 * A product page
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreProductPage extends StoreStorePage
{
	// {{{ public properties

	public $product_id;
	public $product = null;
	public $has_description = false;

	// }}}
	// {{{ protected properties

	protected $items_ui;
	protected $items_ui_xml = 'Store/pages/product-items-view.xml';
	protected $cart_ui;
	protected $cart_ui_xml = 'Store/pages/product-cart.xml';
	protected $item_removed = false;
	protected $added_entry_ids = array();

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initProduct();
		$this->initCart();
	}

	// }}}
	// {{{ protected function initProduct()

	protected function initProduct()
	{
		$this->loadProduct($this->product_id);

		$this->items_ui = new StoreUI();
		$this->items_ui->loadFromXML($this->items_ui_xml);

		$items_form = $this->items_ui->getWidget('form');
		$items_form->action = $this->source;

		$view = $this->items_ui->getWidget('items_view');

		$this->items_ui->init();
		$view->model = $this->getItemTableStore($view);
	}

	// }}}
	// {{{ protected function initCart()

	protected function initCart()
	{
		$this->cart_ui = new StoreUI();
		$this->cart_ui->loadFromXML($this->cart_ui_xml);
		$this->cart_ui->getRoot()->addStyleSheet('packages/store/styles/store-cart.css');

		$cart_form = $this->cart_ui->getWidget('cart_form');
		$cart_form->action = $this->source;

		$this->cart_ui->init();
	}

	// }}}
	// {{{ private function loadProduct()

	private function loadProduct($id)
	{
		$class_map = StoreClassMap::instance();
		$product_class = $class_map->resolveClass('StoreProduct');
		$this->product = new $product_class();
		$this->product->setDatabase($this->app->db);
		$this->product->setRegion($this->app->getRegion()->id);
		$this->product->load($id);
	}

	// }}}
	// {{{ protected function getItemTableStore()

	protected function getItemTableStore($view)
	{
		$store = new SwatTableStore();
		$last_sku = null;
		$tab_index = 1;

		$sql = 'select id, title from ItemGroup where product = %s';
		$sql = sprintf($sql,
			$this->app->db->quote($this->product->id, 'integer'));

		$this->product->items->loadAllSubDataObjects('item_group',
			$this->app->db, $sql, 'StoreItemGroupWrapper');

		foreach ($this->product->items as $item) {
			if ($item->enabled === true) {
				$ds = $this->getItemDetailsStore($item);
				$ds->tab_index = $tab_index++;

				$ds->sku = ($last_sku === $item->sku) ?
					'' : $item->sku;
	
				$last_sku = $item->sku;
				$store->addRow($ds);

				if ($ds->is_available)
					$view->getRow('add_button')->visible = true;
			}
		}

		$view->getRow('add_button')->tab_index = $tab_index;
		return $store;
	}

	// }}}
	// {{{ protected function getItemDetailsStore()

	protected function getItemDetailsStore($item)
	{
		$ds = new SwatDetailsStore($item);
	
		$ds->description = $item->getDescription(false);
	
		if (strlen($ds->description) > 0)
			$this->has_description = true;
	
		$ds->is_available = $item->isAvailableInRegion($this->app->getRegion());
				
		$ds->status = '';

		if (!$ds->is_available)
			$ds->status = sprintf('<span class="item-status">%s</span>',
				Item::getStatusTitle($item->status));
		
		return $ds;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->processProduct();
		$this->processCart();
	}

	// }}}
	// {{{ protected function processProduct()

	protected function processProduct()
	{
		$this->items_ui->process();
		$form = $this->items_ui->getWidget('form');

		if ($form->isProcessed()) {
			$view = $this->items_ui->getWidget('items_view');
			$column = $view->getColumn('quantity_column');
			$renderer = $column->getRenderer('quantity_renderer');

			if ($form->hasMessage()) {
				$message = new SwatMessage('There is a problem with one or '.
					'more of the items you requested.',
					SwatMessage::ERROR);

				$message->secondary_content = 'Please address the fields '.
					'highlighted below and re-submit the form.';

				$this->items_ui->getWidget('message_display')->add($message);
			}

			$num_items_added = 0;
			foreach ($renderer->getClonedWidgets() as $id => $widget) {
				if (!$renderer->hasMessage($id) && $widget->value > 0) {

					$cart_entry = $this->createCartEntry($id);
					$cart_entry->quantity = $widget->value;

					$added_entry =
						$this->app->cart->checkout->addEntry($cart_entry);

					if ($added_entry !== null) {
						$this->added_entry_ids[] = $added_entry->id;
						$num_items_added++;
					}

					// reset quantity entry value (no persistance)
					$widget->value = 0;
				}
			}

			if ($num_items_added) {
				
				$message = new StoreMessage(
					'Your shopping cart has been updated.',
					StoreMessage::CART_NOTIFICATION);

				$message->secondary_content = 'You may continue shopping by '.
					'following any of the links on this page.';

				$this->cart_ui->getWidget('messages')->add($message);
			}

			// add cart messages
			$messages = $this->app->cart->checkout->getMessages(); 
			foreach ($messages as $message)
				$this->cart_ui->getWidget('messages')->add($message);
		}
	}

	// }}}
	// {{{ protected function createCartEntry()

	protected function createCartEntry($item_id)
	{
		$class_map = StoreClassMap::instance();
		$cart_entry_class = $class_map->resolveClass('StoreCartEntry');
		$cart_entry = new $cart_entry_class();

		$this->app->session->activate();

		if ($this->app->session->isLoggedIn())
			$cart_entry->account =
				$this->app->session->getAccountID();
		else
			$cart_entry->sessionid =
				$this->app->session->getSessionID();

		// load item manually here so we can specify region
		$item = new Item();
		$item->setDatabase($this->app->db);
		$item->setRegion($this->app->getRegion()->id);
		$item->load($item_id);

		$cart_entry->item = $item;
		$cart_entry->quick_order = false;

		return $cart_entry;
	}

	// }}}
	// {{{ protected function processCart()

	protected function processCart()
	{
		$this->cart_ui->process();

		$view = $this->cart_ui->getWidget('cart_view');

		// check for removed items
		$remove_column = $view->getColumn('remove_column');
		$remove_renderer = $remove_column->getRendererByPosition(); 
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$this->item_removed = true;
				$this->app->cart->checkout->removeEntryById($id);
				$this->cart_ui->getWidget('messages')->add(new StoreMessage(
					'An item has been removed from your shopping cart.',
					StoreMessage::CART_NOTIFICATION));

				break;
			}
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildCart();
		$this->buildProduct();
		$this->buildNavBar();

		$this->layout->startCapture('content');
		$this->displayCart();
		$this->displayProduct();
		$this->displayProductJavaScript();
		$this->displayCartJavaScript();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildProduct()

	protected function buildProduct()
	{
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-product-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->items_ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->data->title =
			SwatString::minimizeEntities($this->product->title);
	}

	// }}}
	// {{{ protected function buildCart()

	protected function buildCart()
	{
		$this->layout->addHtmlHeadEntry(
			new SwatStyleSheetHtmlHeadEntry(
				'packages/store/styles/store-mini-cart.css', Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->cart_ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$link = 'store';

		foreach ($this->path as $path_entry) {
			$link .= '/'.$path_entry->shortname;
			$this->layout->navbar->createEntry($path_entry->title, $link);
		}

		if ($this->product !== null)
			$this->layout->navbar->createEntry($this->product->title);
	}

	// }}}
	// {{{ protected function getCartTableStore()

	/**
	 * Gets the cart data-store for the product on this page
	 */
	protected function getCartTableStore()
	{
		$store = new SwatTableStore();

		$entries = $this->app->cart->checkout->getEntries();
		foreach ($entries as $entry) {
			// filter entries by item
			if ($this->isOnThisPage($entry->item)) {
				$ds = $this->getCartDetailsStore($entry);
				$store->addRow($ds);
			}
		}

		return $store;
	}

	// }}}
	// {{{ protected function getCartDetailsStore()

	protected function getCartDetailsStore($entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->quantity = $entry->getQuantity();
		$ds->description = $entry->item->getDescription();
		$ds->price = $entry->getCalculatedItemPrice();
		$ds->extension = $entry->getExtension();

		return $ds;
	}

	// }}}
	// {{{ protected function isOnThisPage()

	protected function isOnThisPage($item)
	{
		return ($item->product->id === $this->product_id);
	}

	// }}}
	// {{{ protected function displayCart()

	// mini cart will display if items on this product are in the cart
	protected function displayCart()
	{
		$cart_view = $this->cart_ui->getWidget('cart_view');
		$cart_view->model = $this->getCartTableStore();
		$count = $cart_view->model->getRowCount();
		if ($count > 0) {
			$frame = $this->cart_ui->getWidget('cart_frame');
			$frame->title = ngettext(
				'The following item on this page is in your shopping cart:',
				'The following items on this page are in your shopping cart:',
				$count);

			$this->cart_ui->getWidget('cart_form')->visible = true;
			$this->cart_ui->display();

		} elseif ($this->item_removed || count($this->added_entry_ids) > 0) {
			$this->cart_ui->getWidget('messages')->display();
		}
	}

	// }}}
	// {{{ protected function displayRelatedArticles()

	// displays related articles from the parent category on this page
	protected function displayRelatedArticles()
	{
		$class_map = StoreClassMap::instance();
		$category_class = $class_map->resolveClass('StoreCategory');
		$category = new $category_class();
		$last_entry = $this->path->getLast();

		if ($last_entry === null)
			return;

		$category->id = $last_entry->id;
		$category->setDatabase($this->app->db);
		if (count($category->related_articles) > 0) {
			$div = new SwatHtmlTag('div');
			$div->id = 'related_articles';
			$div->open();
			$this->displayRelatedArticlesTitle();

			$first = true;
			$anchor_tag = new SwatHtmlTag('a');
			foreach ($category->related_articles as $article) {
				if ($first)
					$first = false;
				else
					echo ', ';

				$anchor_tag->href = $article->path;
				$anchor_tag->setContent($article->title);
				$anchor_tag->display();
			}

			$div->close();
		}
	}

	// }}}
	// {{{ protected function displayRelatedArticlesTitle()

	protected function displayRelatedArticlesTitle()
	{
		echo Store::_('Related Articles: ');
	}

	// }}}
	// {{{ protected function displayProduct()

	protected function displayProduct()
	{
		if ($this->product->primary_image !== null)
			$this->displayImage();

		$this->displayBodyText();

		$this->displayRelatedArticles();

		$this->displayItems();

		$this->displayRelatedProducts();
	}

	// }}}
	// {{{ protected function displayImage()

	protected function displayImage()
	{
		$div = new SwatHtmlTag('div');
		$div->id = 'product_image';

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->product->primary_image->getURI('small');
		$img_tag->width = $this->product->primary_image->small_width;
		$img_tag->height = $this->product->primary_image->small_height;
		$img_tag->alt = 'Photo of '.$this->product->title;

		$anchor = new SwatHtmlTag('a');
		$anchor->href = $this->source.'/image';
		$anchor->title = 'View Larger Image';

		$div->open();
		$anchor->open();
		$img_tag->display();
		echo '<span>View Larger Image</span>';
		$anchor->close();
		$div->close();
	}

	// }}}
	// {{{ protected function displayBodyText()

	protected function displayBodyText()
	{
		$div = new SwatHtmlTag('div');
		$div->id = 'product_bodytext';
		$div->setContent(
			SwatString::toXHTML($this->product->bodytext), 'text/xml');

		$div->display();
	}

	// }}}
	// {{{ protected function displayItems()

	protected function displayItems()
	{
		$view = $this->items_ui->getWidget('items_view');

		if (!$this->has_description)
			$view->getColumn('description_column')->visible = false;

		$this->items_ui->display();
	}

	// }}}
	// {{{ protected function displayRelatedProducts()

	protected function displayRelatedProducts()
	{
		if (count($this->product->related_products) == 0)
			return;

		$div = new SwatHtmlTag('div');
		$div->id = 'related_products';

		$header_tag = new SwatHtmlTag('h4');
		$header_tag->setContent('You might also be interested in...');

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'product-list';

		$li_tag = new SwatHtmlTag('li');
		$li_tag->class = 'product-icon';

		$div->open();
		$header_tag->display();
		$ul_tag->open();

		foreach ($this->product->related_products as $product) {
			$li_tag->open();
			$path = 'store/'.$product->path;
			$product->displayAsIcon($path);
			$li_tag->close();
		}

		$ul_tag->close();
		$div->close();
	}

	// }}}
	// {{{ protected function displayProductJavaScript()

	protected function displayProductJavaScript()
	{
		$item_ids = array();
		$model = $this->items_ui->getWidget('items_view')->model;
		foreach ($model->getRows() as $item)
			$item_ids[] = $item->id;

		$form_id = $this->items_ui->getWidget('form')->id;
		$item_ids = "'".implode("', '", $item_ids)."'";

		echo '<script type="text/javascript">', "\n";

		printf("var product_page = new StoreProductPage([%s], '%s');\n",
			$item_ids, $form_id);

		echo '</script>';
	}

	// }}}
	// {{{ protected function displayCartJavaScript()

	protected function displayCartJavaScript()
	{
	}

	// }}}
}

?>
