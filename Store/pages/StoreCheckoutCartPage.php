<?php

require_once 'Store/pages/StoreCheckoutUIPage.php';
require_once 'Store/StoreMessage.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';

/**
 * Cart edit page of checkout
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
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

				if (!$form->hasMessage() &&
					$this->continueButtonHasBeenClicked()) {
					$this->app->cart->save();
					$this->app->relocate('checkout/confirmation');
				}
			}
		}
	}

	// }}}
	// {{{ protected function continueButtonHasBeenClicked()

	/**
	 * Whether or not a button has been clicked indicating the customer
	 * wants to return to the checkout
	 *
	 * @return boolean true if the customer is to return to the checkout
	 *                  and false if the customer is to stay on the checkout
	 *                  cart page.
	 */
	protected function continueButtonHasBeenClicked()
	{
		$continue_button_clicked = false;

		foreach ($this->getContinueButtons() as $button) {
			if ($button->hasBeenClicked()) {
				$continue_button_clicked = true;
				break;
			}
		}

		return $continue_button_clicked;
	}

	// }}}
	// {{{ protected function getContinueButtons()

	protected function getContinueButtons()
	{
		$buttons = array();
		$continue_button_ids =
			array('header_continue_button', 'footer_continue_button');

		foreach ($continue_button_ids as $id)
			$buttons[]= $this->ui->getWidget($id);

		return $buttons;
	}

	// }}}
	// {{{ protected function getQuantityWidgets()

	protected function getQuantityWidgets()
	{
		$view = $this->ui->getWidget('cart_view');
		$column = $view->getColumn('quantity_column');
		$renderer = $column->getRendererByPosition();
		$widgets = $renderer->getWidgets('quantity_entry');

		return $widgets;
	}

	// }}}
	// {{{ protected function getQuantityWidget()

	protected function getQuantityWidget($id)
	{
		$view = $this->ui->getWidget('cart_view');
		$column = $view->getColumn('quantity_column');
		$renderer = $column->getRendererByPosition();
		$widget = $renderer->getWidget($id);

		return $widget;
	}

	// }}}
	// {{{ protected function getMoveButtons()

	protected function getMoveButtons()
	{
		$view = $this->ui->getWidget('cart_view');
		$column = $view->getColumn('move_column');
		$renderer = $column->getRendererByPosition();
		$buttons = $renderer->getWidgets('move_button');

		return $buttons;
	}

	// }}}
	// {{{ protected function getRemoveButtons()

	protected function getRemoveButtons()
	{
		$view = $this->ui->getWidget('cart_view');
		$column = $view->getColumn('remove_column');
		$renderer = $column->getRendererByPosition();
		$buttons = $renderer->getWidgets('remove_button');

		return $buttons;
	}

	// }}}
	// {{{ protected function processEntries()

	protected function processEntries()
	{
		$num_entries_moved   = 0;
		$num_entries_removed = 0;
		$num_entries_updated = 0;

		$num_entries_removed += $this->processRemovedEntries();

		if ($num_entries_removed == 0)
			$num_entries_moved += $this->processMovedEntries();

		if ($num_entries_removed == 0 && $num_entries_moved == 0) {
			$result = $this->processUpdatedEntries();
			$num_entries_removed += $result['num_entries_removed'];
			$num_entries_updated += $result['num_entries_updated'];
		}

		$this->buildMessages($num_entries_moved, $num_entries_removed,
			$num_entries_updated);
	}

	// }}}
	// {{{ protected function processRemovedEntries()

	protected function processRemovedEntries()
	{
		$num_entries_removed = 0;

		foreach ($this->getRemoveButtons() as $id => $button) {
			if ($button->hasBeenClicked()) {
				$num_entries_removed++;
				$this->app->cart->checkout->removeEntryById($id);
				break;
			}
		}

		return $num_entries_removed;
	}

	// }}}
	// {{{ protected function processMovedEntries()

	protected function processMovedEntries()
	{
		$num_entries_moved = 0;

		foreach ($this->getMoveButtons() as $id => $button) {
			if ($button->hasBeenClicked()) {
				$entry = $this->app->cart->checkout->getEntryById($id);

				// make sure entry wasn't already moved
				// (i.e. a page resubmit)
				if ($entry === null)
					break;

				$quantity = $this->getQuantityWidget($id)->value;
				$entry->setQuantity($quantity);
				$this->app->cart->checkout->removeEntry($entry);
				$this->app->cart->saved->addEntry($entry);
				$num_entries_moved++;
				break;
			}
		}

		return $num_entries_moved;
	}

	// }}}
	// {{{ protected function processUpdatedEntries()

	protected function processUpdatedEntries()
	{
		$num_entries_removed = 0;
		$num_entries_updated = 0;

		foreach ($this->getQuantityWidgets() as $id => $widget) {
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

		return array(
			'num_entries_updated' => $num_entries_updated,
			'num_entries_removed' => $num_entries_removed,
		);
	}

	// }}}
	// {{{ protected function buildMessages()

	protected function buildMessages($num_entries_moved, $num_entries_removed,
		$num_entries_updated)
	{
		$message_display = $this->ui->getWidget('message_display');

		if ($num_entries_removed > 0) {
			$message_display->add(new StoreMessage(sprintf(Store::ngettext(
				'One item has been removed from shopping cart.',
				'%s items have been removed form shopping cart.',
				$num_entries_removed),
				SwatString::numberFormat($num_entries_removed)),
				StoreMessage::CART_NOTIFICATION));
		}

		if ($num_entries_moved > 0) {
			$message_display->add(new StoreMessage(
				Store::_('One item has been saved for later.'),
				StoreMessage::CART_NOTIFICATION));
		}

		if ($num_entries_updated > 0) {
			$message_display->add(new StoreMessage(sprintf(Store::ngettext(
				'One item quantity has been updated.',
				'%s item quantities have been updated.',
				$num_entries_updated),
				SwatString::numberFormat($num_entries_removed)),
				StoreMessage::CART_NOTIFICATION));
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

		$class_name = SwatDBClassMap::get('StoreOrderAddress');
		$view->getRow('shipping')->value = $cart->getShippingTotal(
			new $class_name(), new $class_name());

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
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getDetailsStore()

	protected function getDetailsStore($entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->quantity = $entry->getQuantity();
		$ds->description = $entry->getDescription();
		$ds->price = $entry->getCalculatedItemPrice();
		$ds->extension = $entry->getExtension();

		return $ds;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-cart-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
