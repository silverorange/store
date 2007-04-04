<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';

require_once 'Store/exceptions/StoreException.php';
require_once 'Store/StoreUI.php';
require_once 'Site/SiteMultipartMailMessage.php';

/**
 * An email messages for invoice notifications
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreInvoiceNotificationMailMessage extends SiteMultipartMailMessage
{
	// {{{ protected properties

	/**
	 * @var StoreInvoice
	 */
	protected $invoice;

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

	public function __construct(SiteApplication $app, StoreInvoice $invoice)
	{
		parent::__construct($app);

		$this->invoice = $invoice;

		$this->smtp_server = $this->app->config->email->smtp_server;

		$this->from_address = $this->app->config->email->service_address;
		$this->from_name = $this->getFromName();

		$this->to_address = $invoice->account->email;
		$this->to_name = $invoice->account->fullname;

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
		return sprintf('Invoice Notification: Invoice %s',
			$this->invoice->id);
	}

	// }}}

	// text email
	// {{{ protected function getTextBody()

	public function getTextBody()
	{
		$order = $this->order;

		$locale = $this->order->locale->id;

		ob_start();

		echo $this->order->getReceiptHeader();

		$this->displayDetailsText();
		$this->displayItemsText();
		$this->displayTotalsText();

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function displayDetailsText()

	protected function displayDetailsText()
	{
		echo self::LINE_BREAK;
		echo self::LINE_BREAK;

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
			$this->order->payment_method->displayAsText();
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
}

?>
