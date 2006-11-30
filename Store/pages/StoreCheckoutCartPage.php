<?php

require_once 'Store/pages/StoreCheckoutUIPage.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';

/**
 * Cart edit page of checkout
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCheckoutCartPage extends StoreCheckoutUIPage
{
	// {{{ protected properties

	protected $updated_entry_ids = array();

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->ui_xml = 'Store/pages/checkout-cart.xml';
	}

	// }}}

	// init phase
	// {{{ protected function getProgressDependencies()

	protected function getProgressDependencies()
	{
		return array('checkout/first');
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
			if ($form->hasMessage()) {
				$message = new SwatMessage(Store::_('There is a problem with '.
					'the information submitted.'), SwatMessage::ERROR);

				$message->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->ui->getWidget('message_display')->add($message);
			} else {
				$this->processEntries();

				$continue_button_ids =
					array('header_continue_button', 'footer_continue_button');

				$continue_button_clicked = false;
				foreach ($continue_button_ids as $id) {
					$button = $this->ui->getWidget($id);
					if ($button->hasBeenClicked()) {
						$continue_button_clicked = true;
						break;
					}
				}

				if (!$form->hasMessage() && $continue_button_clicked) {
					$this->app->cart->save();
					$this->app->relocate('checkout/confirmation');
				}
			}
		}
	}

	// }}}
	// {{{ protected function processEntries()

	protected function processEntries()
	{
		if (!$this->processRemovedEntries())
			if (!$this->processMovedEntries())
				$this->processUpdatedEntries();
	}

	// }}}
	// {{{ protected function processRemovedEntries()

	protected function processRemovedEntries()
	{
		$view = $this->ui->getWidget('cart_view');
		$remove_column = $view->getColumn('remove_column');
		$remove_renderer = $remove_column->getRendererByPosition(); 

		$num_entries_removed = 0;
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$num_entries_removed++;
				$this->app->cart->checkout->removeEntryById($id);
				break;
			}
		}

		if ($num_entries_removed > 0) {
			$message_display = $this->ui->getWidget('message_display');
			$message_display->add(new SwatMessage(sprintf(Store::ngettext(
				'One item has been removed from shopping cart.',
				'%s items have been removed form shopping cart.',
				$num_entries_removed),
				SwatString::numberFormat($num_entries_removed))));
		}

		return ($num_entries_removed > 0);
	}

	// }}}
	// {{{ protected function processMovedEntries()

	protected function processMovedEntries()
	{
		$view = $this->ui->getWidget('cart_view');
		$quantity_column = $view->getColumn('quantity_column');
		$quantity_renderer = $quantity_column->getRendererByPosition(); 
		$move_column = $view->getColumn('move_column');
		$move_renderer = $move_column->getRendererByPosition();

		$entry_moved = false;
		foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$entry = $this->app->cart->checkout->getEntryById($id);

				// make sure entry wasn't already moved
				// (i.e. a page resubmit)
				if ($entry === null)
					break;

				$quantity = $quantity_renderer->getWidget($id)->value;
				$entry->setQuantity($quantity);
				$this->app->cart->checkout->removeEntry($entry);
				$this->app->cart->saved->addEntry($entry);
				$entry_moved = true;
				break;
			}
		}

		if ($entry_moved) {
			$message_display = $this->ui->getWidget('message_display');
			$message_display->add(new SwatMessage(
				Store::_('One item has been saved for later.')));
		}

		return $entry_moved;
	}

	// }}}
	// {{{ protected function processUpdatedEntries()

	protected function processUpdatedEntries()
	{
		$message_display = $this->ui->getWidget('message_display');
		$view = $this->ui->getWidget('cart_view');
		$quantity_column = $view->getColumn('quantity_column');
		$quantity_renderer = $quantity_column->getRendererByPosition(); 

		$num_entries_removed = 0;
		$num_entries_updated = 0;

		foreach ($quantity_renderer->getClonedWidgets() as $id => $widget) {
			if (!$widget->hasMessage()) {
				$entry = $this->app->cart->checkout->getEntryById($id);
				if ($entry !== null &&
					$entry->getQuantity() !== $widget->value) {
					$this->updated_entry_ids[] = $id;
					$this->app->cart->checkout->setEntryQuantity($entry,
						$widget->value);
					
					if ($widget->value > 0)
						$num_entries_updated++;
					else
						$num_entries_removed++;

					$widget->value = $entry->getQuantity();
				}
			}
		}

		$message_display = $this->ui->getWidget('message_display');

		if ($num_entries_updated > 0) {
			$message_display->add(new SwatMessage(sprintf(Store::ngettext(
				'One item quantity has been updated.',
				'%s item quantities have been updated.',
				$num_entries_updated),
				SwatString::numberFormat($num_entries_updated))));
		}

		if ($num_entries_removed > 0) {
			$message_display->add(new SwatMessage(sprintf(Store::ngettext(
				'One item has been removed from shopping cart.',
				'%s items have been removed form shopping cart.',
				$num_entries_removed),
				SwatString::numberFormat($num_entries_removed))));
		}

		foreach ($this->app->cart->checkout->getMessages() as $message)
			$message_display->add($message);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildTableView();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-cart-page.css',
			Store::PACKAGE_ID));

		parent::build();
	}

	// }}}
	// {{{ protected function buildTableView()

	protected function buildTableView()
	{
		$cart = $this->app->cart->checkout;
		$order = $this->app->session->order;

		$view = $this->ui->getWidget('cart_view');
		$view->model = $this->getTableStore();

		$view->getRow('subtotal')->value = $cart->getSubtotal();

		$view->getRow('shipping')->value = $cart->getShippingTotal(
			new StoreOrderAddress(), new StoreOrderAddress());

		$view->getRow('total')->value = $cart->getTotal(
			$order->billing_address, $order->shipping_address);
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore()
	{
		$store = new SwatTableStore();

		$entries = $this->app->cart->checkout->getAvailableEntries();
		foreach ($entries as $entry) {
			$ds = $this->getDetailsStore($entry);
			$store->addRow($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getDetailsStore()

	protected function getDetailsStore($entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->quantity = $entry->getQuantity();
		$ds->description = $entry->item->getDescription();
		$ds->price = $entry->getCalculatedItemPrice();
		$ds->extension = $entry->getExtension();

		return $ds;
	}

	// }}}
}

?>
