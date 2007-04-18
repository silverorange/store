<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';

require_once 'Store/exceptions/StoreException.php';
require_once 'Store/StoreUI.php';
require_once 'Store/StoreShippingAddressCellRenderer.php';
require_once 'Site/SiteMultipartMailMessage.php';

/**
 * An email messages for order confirmations
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreOrderConfirmationMailMessage
	extends SiteMultipartMailMessage
{
	// {{{ protected properties

	/**
	 * @var StoreOrder
	 */
	protected $order;

	/**
	 * @var string
	 */
	protected $ui_xml;

	/**
	 * @var string
	 */
	protected $www_path = '';

	// }}}
	// {{{ class constants

	/**
	 * The string sequence to represent a line break in text email
	 */
	const LINE_BREAK = "\n";

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, StoreOrder $order)
	{
		parent::__construct($app);

		$this->order = $order;

		$this->smtp_server = $this->app->config->email->smtp_server;

		$this->from_address = $this->app->config->email->service_address;
		$this->from_name = $this->getFromName();

		$this->to_address = $order->email;
		$this->to_name = $order->billing_address->fullname;

		$this->subject = $this->getSubject(); 

		$this->html_body = $this->getHtmlBody();
		$this->text_body = $this->getTextBody();
	}

	// }}}
	// {{{ protected abstract function getFromName()

	protected abstract function getFromName();

	// }}}
	// {{{ protected function getSubject()

	protected function getSubject()
	{
		return sprintf('Order Confirmation: Order %s',
			$this->order->id);
	}

	// }}}

	// html email
	// {{{ public function getHtmlBody()

	public function getHtmlBody()
	{
		if ($this->ui_xml === null)
			throw new StoreException('A UI XML file is required ');

		$ui = new StoreUI();
		$ui->loadFromXML($this->ui_xml);
		$ui->init();

		$this->buildOrderDetails($ui);

		ob_start();

		echo '<html><head>';
		echo '<style type="text/css">';
		echo '#order-confirmation-email { font-family: sans-serif; }';
		echo '</style>';

		$ui->getRoot()->getHtmlHeadEntrySet()->displayInline(
			$this->getWebRoot(),
			'SwatStyleSheetHtmlHeadEntry');

		echo '</head><body id="order-confirmation-email">';
		echo SwatString::toXHTML(SwatString::linkify(
			$this->order->getReceiptHeader()));

		$ui->display();

		echo SwatString::toXHTML(SwatString::linkify(
			$this->order->getReceiptFooter()));

		echo '</body></html>';

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function buildOrderDetails()

	protected function buildOrderDetails(StoreUI $ui)
	{
		$ui->getRoot()->addStyleSheet('packages/store/styles/store-cart.css');

		$details_view =  $ui->getWidget('order_details');
		$details_view->data = new SwatDetailsStore($this->order);

		$date_field = $details_view->getField('createdate');
		$date_renderer = $date_field->getFirstRenderer();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		if ($this->order->comments === null)
			$details_view->getField('comments')->visible = false;

		if ($this->order->phone === null)
			$details_view->getField('phone')->visible = false;

		if ($this->order->payment_method === null)
			$details_view->getField('payment_method')->visible = false;

		$items_view = $ui->getWidget('items_view');
		$items_view->model = $this->order->getOrderDetailsTableStore();

		$items_view->getRow('shipping')->value = $this->order->shipping_total;
		$items_view->getRow('subtotal')->value = $this->order->getSubtotal();

		$items_view->getRow('total')->value = $this->order->total;
	}

	// }}}
	// {{{ abstract protected function getWebRoot()

	abstract protected function getWebRoot();

	// }}}

	// text email
	// {{{ protected function getTextBody()

	public function getTextBody()
	{
		ob_start();

		$this->displayHeaderText();
		$this->displayDetailsText();
		$this->displayItemsText();
		$this->displayTotalsText();
		$this->displayFooterText();

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function displayDetailsText()

	protected function displayDetailsText()
	{
		$createdate = clone $this->order->createdate;
		$createdate->convertTZ($this->app->default_time_zone);
		printf('Order Placed: %s',
			$createdate->format(SwatDate::DF_DATE_TIME,
				SwatDate::TZ_CURRENT_SHORT));

		echo self::LINE_BREAK;
		printf('Email: %s', $this->order->email);
		echo self::LINE_BREAK;

		if ($this->order->phone !== null) {
			printf('Phone: %s', $this->order->phone);
			echo self::LINE_BREAK;
		}

		if ($this->order->comments !== null) {
			echo 'Comments:', self::LINE_BREAK,
				$this->order->comments,
				self::LINE_BREAK, self::LINE_BREAK;
		}

		if ($this->order->payment_method !== null) {
			echo 'Payment:', self::LINE_BREAK;
			$this->displayPaymentMethodText();
			echo self::LINE_BREAK, self::LINE_BREAK;
		}

		echo 'Billing Address:', self::LINE_BREAK;
		$this->order->billing_address->displayCondensedAsText();
		echo self::LINE_BREAK, self::LINE_BREAK;

		echo 'Shipping Address:', self::LINE_BREAK;
		if ($this->order->billing_address->id == $this->order->shipping_address->id)
			echo '<ship to billing address>';
		else
			$this->order->shipping_address->displayCondensedAsText();

		echo self::LINE_BREAK, self::LINE_BREAK;
	}

	// }}}
	// {{{ protected function displayItemsText()

	protected function displayItemsText()
	{
		$locale = $this->order->locale->id;

		echo 'Order Items:';

		echo self::LINE_BREAK, self::LINE_BREAK;

		$product = null;

		foreach ($this->order->items as $item) {
			if ($item->product !== $product) {
				echo $item->product_title, self::LINE_BREAK;
				$product = $item->product;
			}

			$this->displayItemHeader($item);
			$this->displayItemFooter($item);

			echo self::LINE_BREAK, self::LINE_BREAK;
		}
	}

	// }}}
	// {{{ protected function displayItemHeader()

	protected function displayItemHeader($item)
	{
		printf('   Item #: %s, Quantity: %s',
			$item->sku,
			$item->quantity);

		echo self::LINE_BREAK;

		if ($item->description !== null)
			echo '   Description: ', $item->description, self::LINE_BREAK;
	}

	// }}}
	// {{{ protected function displayItemFooter()

	protected function displayItemFooter($item)
	{
		$locale = $this->order->locale->id;

		printf('   Price: %s, Total: %s',
			SwatString::moneyFormat($item->price, $locale),
			SwatString::moneyFormat($item->extension, $locale));
	}

	// }}}
	// {{{ protected function displayTotalsText()

	protected function displayTotalsText()
	{
		$order = $this->order;
		$locale = $this->order->locale->id;

		$subtotal = $order->getSubtotal();
		printf('Subtotal: %s', SwatString::moneyFormat($subtotal, $locale));
		echo self::LINE_BREAK;

		if ($order->shipping_total == 0) {
			echo 'Shipping Total: Free!',
				self::LINE_BREAK;
		} else {
			printf('Shipping Total: %s',
				SwatString::moneyFormat($order->shipping_total, $locale));

			echo self::LINE_BREAK;
		}

		echo self::LINE_BREAK;
		printf('Total: %s', SwatString::moneyFormat($order->total, $locale));
	}

	// }}}
	// {{{ protected function displayHeaderText()

	protected function displayHeaderText()
	{
		$header = $this->order->getReceiptHeader();
		if (strlen($header) > 0) {
			echo $header;
			echo self::LINE_BREAK;
			echo self::LINE_BREAK;
		}
	}

	// }}}
	// {{{ protected function displayFooterText()

	protected function displayFooterText()
	{
		$footer = $this->order->getReceiptFooter();
		if (strlen($footer) > 0) {
			echo self::LINE_BREAK;
			echo self::LINE_BREAK;
			echo $footer;
		}
	}

	// }}}
	// {{{ protected function displayPaymentMethodText()

	protected function displayPaymentMethodText()
	{
		$this->order->payment_method->displayAsText(true, self::LINE_BREAK);
	}

	// }}}
}

?>
