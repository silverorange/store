<?php

/**
 * Page to display old orders placed using an account.
 *
 * Items in old orders can be added to the checkout card from this page.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreAccount
 * @see       StoreOrder
 */
class StoreAccountOrderPage extends SiteUiPage
{
    /**
     * @var StoreOrder
     */
    protected $order;

    /**
     * @var array
     */
    private $items_added = [];

    protected function getUiXml()
    {
        return __DIR__ . '/account-order.xml';
    }

    protected function getArgumentMap()
    {
        return [
            'order' => [0, 0],
        ];
    }

    // init phase

    public function init()
    {
        // Redirect to orders page if not logged in. Note: We do not redirect
        // directly to this order to prevent the case where you log out of
        // one account from an order page and then try to log in using a
        // different account.
        if (!$this->app->session->isLoggedIn()) {
            $uri = sprintf(
                '%s?relocate=%s',
                $this->app->config->uri->account_login,
                $this->getOrdersPageUri()
            );

            $this->app->relocate($uri);
        }

        parent::init();
    }

    protected function initInternal()
    {
        $order_id = intval($this->getArgument('order'));
        $this->loadOrder($order_id);

        $this->initAddButtonColumn();
    }

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
        $add_item_column->addMappingToRenderer(
            $add_item_renderer,
            'id',
            'replicator_id'
        );

        $add_item_column->addMappingToRenderer(
            $add_item_renderer,
            'show_add_button',
            'visible',
            $add_item_button
        );

        $items_view->appendColumn($add_item_column);
    }

    protected function loadOrder($id)
    {
        $this->order = $this->app->session->account->orders->getByIndex($id);

        if ($this->order === null) {
            throw new SiteNotFoundException(
                sprintf('An order with an id of ‘%d’ does not exist.', $id)
            );
        }
    }

    protected function getOrdersPageUri()
    {
        return 'account/orders';
    }

    // process phase

    protected function processInternal()
    {
        $form = $this->ui->getWidget('form');
        if ($form->isProcessed()) {
            if ($this->ui->getWidget('add_all_items')->hasBeenClicked()) {
                $this->addAllItems();
            } else {
                $this->addOneItem();
            }
        }
    }

    /**
     * @param mixed $item_id
     *
     * @return StoreCartEntry the entry that was added
     */
    protected function addItem($item_id, StoreOrderItem $order_item)
    {
        if ($item_id !== null) {
            // load item manually here so we can specify region
            $item_class = SwatDBClassMap::get(StoreItem::class);
            $item = new $item_class();
            $item->setDatabase($this->app->db);
            $item->setRegion($this->app->getRegion());
            $item->load($item_id);

            $cart_entry = $this->createCartEntry($item, $order_item);

            if ($this->app->cart->checkout->addEntry($cart_entry)) {
                $this->items_added[] = $item;

                return $cart_entry;
            }
        }

        $message = new SwatMessage(sprintf(
            Store::_(
                'Sorry, “%s” is no longer available.'
            ),
            $order_item->sku
        ));

        $this->ui->getWidget('message_display')->add($message);

        return null;
    }

    /**
     * @return StoreCartEntry the entry that was created
     */
    protected function createCartEntry(
        StoreItem $item,
        StoreOrderItem $order_item
    ) {
        $cart_entry_class = SwatDBClassMap::get(StoreCartEntry::class);
        $cart_entry = new $cart_entry_class();

        $cart_entry->account = $this->app->session->getAccountId();
        $cart_entry->item = $item;
        $cart_entry->source = StoreCartEntry::SOURCE_ACCOUNT_ORDER_PAGE;
        $cart_entry->setQuantity($order_item->quantity);

        if ($order_item->custom_price) {
            $cart_entry->custom_price = $order_item->price;
        }

        return $cart_entry;
    }

    protected function addAllItems()
    {
        foreach ($this->order->items as $order_item) {
            $item_id = $this->findItem($order_item);
            $this->addItem($item_id, $order_item);
        }
    }

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

    /**
     * @return int
     */
    protected function findItem(StoreOrderItem $order_item)
    {
        return $order_item->getAvailableItemId($this->app->getRegion());
    }

    // build phase

    protected function buildInternal()
    {
        $this->ui->getWidget('form')->action = $this->source;

        $this->buildCartMessages();
        $this->buildOrderDetails();
        $this->buildOrderItemsView();
    }

    protected function buildOrderDetails()
    {
        $details_view = $this->ui->getWidget('order_details');
        $ds = new SwatDetailsStore($this->order);
        $details_view->data = $ds;

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
            if (!$this->app->config->store->multiple_payment_support) {
                $ds->payment_method = $this->order->payment_methods->getFirst();
            }

            if ($this->order->comments === null) {
                $details_view->getField('comments')->visible = false;
            }

            if ($this->order->phone === null) {
                $details_view->getField('phone')->visible = false;
            }
        }
    }

    protected function buildOrderItemsView()
    {
        $items_view = $this->ui->getWidget('items_view');

        $store = $this->getOrderDetailsTableStore();
        $items_view->model = $store;

        $items_view->getRow('shipping')->value = $this->order->shipping_total;

        if ($this->order->surcharge_total > 0) {
            $items_view->getRow('surcharge')->value =
                $this->order->surcharge_total;
        }

        if ($this->order->tax_total > 0) {
            $items_view->getRow('tax')->value = $this->order->tax_total;
        } else {
            $items_view->getRow('tax')->visible = false;
        }

        $items_view->getRow('subtotal')->value = $this->order->getSubtotal();
        $items_view->getRow('total')->value = $this->order->total;

        $locale_id = $this->order->getInternalValue('locale');
        if ($this->app->getLocale() != $locale_id) {
            $this->ui->getWidget('currency_note')->content = sprintf(
                Store::_('Prices for this order are in %s.'),
                SwatString::getInternationalCurrencySymbol($locale_id)
            );
        }
    }

    protected function getOrderDetailsTableStore()
    {
        $store = $this->order->getOrderDetailsTableStore();
        $this->setItemPaths($store);

        return $store;
    }

    protected function buildCartMessages()
    {
        $num = count($this->items_added);
        if ($num > 0) {
            $message = new SwatMessage(sprintf(
                Store::ngettext(
                    '“%1$s” added to %3$sshopping cart%4$s.',
                    '%2$s items added to %3$sshopping cart%4$s.',
                    $num
                ),
                current($this->items_added)->sku,
                $num,
                '<a href="cart">',
                '</a>'
            ), 'cart');

            $message->content_type = 'text/xml';

            $this->ui->getWidget('message_display')->add($message);
        }
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        if (!property_exists($this->layout, 'navbar')) {
            return;
        }

        $this->layout->navbar->createEntry($this->order->getTitle());
    }

    protected function buildTitle()
    {
        parent::buildTitle();
        $this->layout->data->title = $this->order->getTitle();
    }

    protected function orderIsBlank()
    {
        return $this->order->billing_address->fullname == '';
    }

    private function setItemPaths($store)
    {
        $sql = sprintf(
            'select OrderItem.id,
				getCategoryPath(ProductPrimaryCategoryView.primary_category)
					as path,
				Product.shortname
			from OrderItem
				left outer join Item as MatchItem on
					MatchItem.sku = OrderItem.sku
				left outer join AvailableItemView on
					AvailableItemView.item = MatchItem.id
					and AvailableItemView.region = %s
				left outer join Item on AvailableItemView.item = Item.id
				left outer join Product on Item.product = Product.id
				left outer join ProductPrimaryCategoryView
					on Item.product = ProductPrimaryCategoryView.product
			where OrderItem.ordernum = %s',
            $this->app->db->quote($this->app->getRegion()->id, 'integer'),
            $this->app->db->quote($this->order->id, 'integer')
        );

        $item_paths = SwatDB::query($this->app->db, $sql);

        $paths = [];

        foreach ($item_paths as $row) {
            if ($row->path !== null) {
                $paths[$row->id] = $this->app->config->store->path .
                    $row->path . '/' . $row->shortname;
            }
        }

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

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry('packages/store/styles/store-cart.css');
    }
}
