<?php

require_once 'Store/pages/StoreCheckoutUIPage.php';

/**
 * Abstract base class for final page of the checkout
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutFinalPage extends StoreCheckoutUIPage
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->resetProgress();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
	}

	// }}}
	// {{{ protected function loadUI()

	protected function loadUI()
	{
		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ protected function checkCart()

	protected function checkCart()
	{
		// always return true - cart should be empty now
		return true;
	}

	// }}}
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout/confirmation');
	}

	// }}}
	// {{{ protected function initDataObjects()

	protected function initDataObjects()
	{
		// do nothing
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();
		$this->logoutSession();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$order = $this->getOrder();

		$this->buildOrderHeader($order);
		$this->buildOrderDetails($order);
		$this->buildOrderFooter($order);

		$this->buildFinalNote($order);
		$this->buildAccountNote($order);
	}

	// }}}
	// {{{ protected function buildOrderHeader()

	protected function buildOrderHeader(StoreOrder $order)
	{
		$header = $this->ui->getWidget('header');
		if ($header instanceof SwatContentBlock) {
			$header->content_type = 'text/xml';
			$header->content =
				SwatString::toXHTML($order->getReceiptHeaderXml());
		}
	}

	// }}}
	// {{{ protected function buildOrderFooter()

	protected function buildOrderFooter(StoreOrder $order)
	{
		$footer = $this->ui->getWidget('footer');
		if ($footer instanceof SwatContentBlock) {
			$footer->content_type = 'text/xml';
			$footer->content = SwatString::toXHTML($order->getReceiptFooter());
		}
	}

	// }}}
	// {{{ protected function buildOrderDetails()

	protected function buildOrderDetails(StoreOrder $order)
	{
		$details_view =  $this->ui->getWidget('order_details');
		$details_view->data = $this->getOrderDetailsStore($order);

		$createdate_column = $details_view->getField('createdate');
		$createdate_renderer = $createdate_column->getFirstRenderer();
		$createdate_renderer->display_time_zone = $this->app->default_time_zone;

		if ($order->email === null)
			$details_view->getField('email')->visible = false;

		if ($order->comments === null)
			$details_view->getField('comments')->visible = false;

		if ($order->phone === null)
			$details_view->getField('phone')->visible = false;

		$items_view = $this->ui->getWidget('items_view');
		$items_view->model = $order->getOrderDetailsTableStore();

		$items_view->getRow('shipping')->value = $order->shipping_total;
		$items_view->getRow('subtotal')->value = $order->getSubtotal();

		if ($order->surcharge_total > 0)
			$items_view->getRow('surcharge')->value = $order->surcharge_total;

		$items_view->getRow('total')->value = $order->total;
	}

	// }}}
	// {{{ protected function getOrderDetailsStore()

	protected function getOrderDetailsStore(StoreOrder $order)
	{
		$ds = new SwatDetailsStore($order);

		return $ds;
	}

	// }}}
	// {{{ protected function buildFinalNote()

	protected function buildFinalNote(StoreOrder $order)
	{
		$note = $this->ui->getWidget('final_note');
		if ($note instanceof SwatContentBlock) {
			$note->content_type = 'text/xml';
			ob_start();
			$this->displayFinalNote($order);
			$note->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function buildAccountNote()

	protected function buildAccountNote(StoreOrder $order)
	{
		/* TODO: Possilble refactor this. Veseys displays an account note
		 *       but does not use this mechanism to do it.  It is displayed
		 *       in a SwatMessageDisplay instead.
		 */
		if (!$this->ui->hasWidget('account_note'))
			return;

		$note = $this->ui->getWidget('account_note');
		if ($note instanceof SwatContentBlock && $order->account !== null) {
			$note->content_type = 'text/xml';
			ob_start();
			$this->displayAccountNote();
			$note->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ abstract protected function displayFinalNote()

	/**
	 * Displays the final note at the top of the page to the user
	 *
	 * This note indicated whether or not the checkout was successful and may
	 * contain additional instructions depending on the particular store.
	 *
	 * @param StoreOrder $order the order for which to display the final note.
	 */
	abstract protected function displayFinalNote(StoreOrder $order);

	// }}}
	// {{{ protected function displayAccountNote()

	protected function displayAccountNote()
	{
		$header_tag = new SwatHtmlTag('h3');
		$header_tag->setContent(Store::_('Your Account'));
		$paragraph_tag = new SwatHtmlTag('p');
		$paragraph_tag->setContent(Store::_('By logging into your account '.
			'the next time you visit our website, you can edit your addresses '.
			'and payment methods, view previously placed orders, re-order '.
			'items from your previous orders, and checkout without '.
			'having to re-enter all of your address and payment '.
			'information.'));

		$header_tag->display();
		$paragraph_tag->display();
	}

	// }}}
	// {{{ protected function getOrder()

	/**
	 * @return StoreOrder
	 */
	protected function getOrder()
	{
		return $this->app->session->order;
	}

	// }}}
	// {{{ protected function logoutSession()

	protected function logoutSession()
	{
		$this->app->session->logout();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-final-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
