<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatWidgetCellRenderer.php';

require_once 'Site/exceptions/SiteNotFoundException.php';

require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/dataobjects/StoreInvoiceWrapper.php';
require_once 'Store/pages/StoreAccountPage.php';
require_once 'Store/StoreShippingAddressCellRenderer.php';
require_once 'Store/StoreUI.php';

/**
 * Page to display invoices attached to this account
 *
 * Invoices are added through a tool in the admin
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccount
 * @see       StoreInvoice
 */
class StoreAccountInvoicePage extends StoreAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-invoice.xml';

	protected $invoice = null;
	protected $ui;

	// }}}
	// {{{ private properties

	private $id;

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

		$this->loadInvoice();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);
		$this->ui->init();
	}

	// }}}
	// {{{ private function loadInvoice()

	private function loadInvoice()
	{
		$this->invoice =
			$this->app->session->account->invoices->getByIndex($this->id);

		if ($this->invoice === null)
			throw new SiteNotFoundException(
				sprintf('An invoice with an id of ‘%d’ does not exist.',
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
			$uri = sprintf('checkout/invoice%s', $this->invoice->id);
			$this->app->relocate($uri);
		}
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

		$title = $this->invoice->getTitle();
		$this->layout->data->title = $title;
		$this->layout->navbar->createEntry($title);

		$this->buildInvoiceDetails();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInvoiceDetails()

	protected function buildInvoiceDetails()
	{
		$details_view =  $this->ui->getWidget('invoice_details');
		$details_view->data = new SwatDetailsStore($this->invoice);

		$createdate_column = $details_view->getField('createdate');
		$createdate_renderer = $createdate_column->getFirstRenderer();
		$createdate_renderer->display_time_zone =
			$this->app->default_time_zone;

		if ($this->invoice->comments === null)
			$details_view->getField('comments')->visible = false;

		$items_view = $this->ui->getWidget('items_view');

		$store = $this->getInvoiceDetailsTableStore();
		$items_view->model = $store;

		$items_view->getRow('subtotal')->value = $this->invoice->getSubtotal();

		$shipping = $this->invoice->getShippingTotal();
		if ($shipping !== null)
			$items_view->getRow('shipping')->value = $shipping;
		else
			$items_view->getRow('shipping')->visible = false;

		$tax = $this->invoice->getTaxTotal();
		if ($tax !== null && $tax > 0)
			$items_view->getRow('tax')->value = $tax;
		else
			$items_view->getRow('tax')->visible = false;

		$total = $this->invoice->getTotal();
		if ($total !== null)
			$items_view->getRow('total')->value = $total;
		else
			$items_view->getRow('total')->visible = false;
	}

	// }}}
	// {{{ protected function getInvoiceDetailsTableStore()

	protected function getInvoiceDetailsTableStore()
	{
		$store = $this->invoice->getInvoiceDetailsTableStore();
		//$this->setItemPaths($store);

		return $store;
	}

	// }}}
	// {{{ private function setItemPaths()

	private function setItemPaths($store)
	{
		// NOTE: currently note used.

		$sql = sprintf('select InvoiceItem.id,
				getCategoryPath(ProductPrimaryCategoryView.primary_category) as path,
				Product.shortname
			from InvoiceItem
				left outer join Item as MatchItem on MatchItem.sku = InvoiceItem.sku
				left outer join AvailableItemView on AvailableItemView.item = MatchItem.id
					and AvailableItemView.region = %s
				left outer join Item on AvailableItemView.item = Item.id
				left outer join Product on Item.product = Product.id
				left outer join ProductPrimaryCategoryView
					on Item.product = ProductPrimaryCategoryView.product
			where InvoiceItem.invoice = %s',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->app->db->quote($this->invoice->id, 'integer'));

		$item_paths = SwatDB::query($this->app->db, $sql);

		$paths = array();

		foreach ($item_paths as $row)
			if ($row->path !== null)
				$paths[$row->id] = 'store/'.$row->path.'/'.$row->shortname;

		foreach ($store->getRows() as $row)
			$row->path = (isset($paths[$row->id])) ? $paths[$row->id] : null;
	}

	// }}}
}

?>
