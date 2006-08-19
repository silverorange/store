<?php

require_once 'Store/pages/StoreArticlePage.php';
require_once 'Store/StoreUI.php';

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';

/**
 * Shopping cart display page
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreCartPage extends StoreArticlePage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/cart.xml';

	/**
	 * @var StoreUI
	 */
	protected $ui;

	/**
	 * An array of cart entry ids that were updated
	 *
	 * @var array
	 */
	protected $updated_entry_ids = array();

	/**
	 * An array of cart entry ids that were added (or moved) to a cart 
	 *
	 * @var array
	 */
	protected $added_entry_ids = array();

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		if (!isset($this->app->cart->checkout))
			throw new StoreException('Store has no checkout cart.');

		if (!isset($this->app->cart->saved))
			throw new StoreException('Store has no saved cart.');

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);

		// set table store for widget validation
		$available_view = $this->ui->getWidget('available_cart_view');
		$available_view->model = $this->getAvailableTableStore();

		$form = $this->ui->getWidget('form');
		$form->action = $this->source;

		$form = $this->ui->getWidget('saved_cart_form');
		$form->action = $this->source;

		$this->ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$this->processCheckoutCartForm();
		$this->processSavedCartForm();
	}

	// }}}
	// {{{ protected function processCheckoutCartForm()

	protected function processCheckoutCartForm()
	{
		$form = $this->ui->getWidget('form');
		$form->process();

		if ($form->isProcessed()) {
			$checkout_top = $this->ui->getWidget('checkout_top');
			$checkout_bottom = $this->ui->getWidget('checkout_bottom');

			if ($form->hasMessage()) {
				$msg = new SwatMessage(Store::_(
					'There is a problem with the information submitted.'),
					SwatMessage::ERROR);

				$msg->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->ui->getWidget('message_display')->add($msg);
			} else {
				$this->updateCheckoutCart();
				if (!$form->hasMessage() &&
					($checkout_top->hasBeenClicked() ||
					$checkout_bottom->hasBeenClicked())) {
					$this->app->cart->save();
					$this->app->relocate('checkout');
				}
			}
		}
	}

	// }}}
	// {{{ protected function processSavedCartForm()

	protected function processSavedCartForm()
	{
		$form = $this->ui->getWidget('saved_cart_form');
		$form->process();

		if ($form->isProcessed()) {
			if ($form->hasMessage()) {
				$msg = new SwatMessage(Store::_(
					'There is a problem with the information submitted.'),
					SwatMessage::ERROR);

				$msg->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->ui->getWidget('message_display')->add($msg);
			} else {
				$move_all = $this->ui->getWidget('saved_cart_move_all');
				if ($move_all->hasBeenClicked())
					$this->moveAllSavedCart();
				else
					$this->updateSavedCart();
			}
		}
	}

	// }}}
	// {{{ protected function updateCheckoutCart()

	protected function updateCheckoutCart()
	{
		$message_display = $this->ui->getWidget('message_display');
		$available_view = $this->ui->getWidget('available_cart_view');

		// check for removed available items
		$remove_column = $available_view->getColumn('remove_column');
		$remove_renderer = $remove_column->getRendererByPosition(); 
		$item_removed = false;
		$num_items_removed = 0;
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$item_removed = true;
				$num_items_removed++;
				$this->app->cart->checkout->removeEntryById($id);

				break;
			}
		}

		// check for removed unavailable items
		if (!$item_removed) {
			$unavailable_view = $this->ui->getWidget('unavailable_cart_view');
			$remove_column =
				$unavailable_view->getColumn('remove_column');

			$remove_renderer = $remove_column->getRendererByPosition(); 
			foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
				if ($widget->hasBeenClicked()) {
					$item_removed = true;
					$num_items_removed++;
					$this->app->cart->checkout->removeEntryById($id);

					break;
				}
			}
		}

		// check for moved available items
		$item_moved = false;
		if (!$item_removed) {
			$quantity_column = $available_view->getColumn('quantity_column');
			$quantity_renderer = $quantity_column->getRendererByPosition(); 
			$move_column = $available_view->getColumn('move_column');
			$move_renderer = $move_column->getRendererByPosition(); 
			foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
				if ($widget->hasBeenClicked()) {
					$entry = $this->app->cart->checkout->getEntryById($id);

					// make sure entry wasn't already moved
					// (i.e. a page resubmit)
					if ($entry === null)
						break;

					$quantity = $quantity_renderer->getWidget($id)->value;

					$this->added_entry_ids[] = $id;
					$item_moved = true;

					$entry->setQuantity($quantity);
					$this->app->cart->checkout->removeEntry($entry);
					$this->app->cart->saved->addEntry($entry);

					break;
				}
			}
		}

		// check for moved unavailable items
		if (!$item_removed && !$item_moved) {
			$move_column = $unavailable_view->getColumn('move_column');
			$move_renderer = $move_column->getRendererByPosition(); 
			foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
				if ($widget->hasBeenClicked()) {
					$entry = $this->app->cart->checkout->getEntryById($id);

					$this->added_entry_ids[] = $id;
					$item_moved = true;

					$this->app->cart->checkout->removeEntry($entry);
					$this->app->cart->saved->addEntry($entry);

					break;
				}
			}
		}

		// check for updated items
		$item_updated = false;
		$num_items_updated = 0;
		if (!$item_removed && !$item_moved) {
			$quantity_column = $available_view->getColumn('quantity_column');
			$quantity_renderer = $quantity_column->getRendererByPosition(); 
			foreach ($quantity_renderer->getClonedWidgets() as $id => $widget) {
				if (!$widget->hasMessage()) {
					$entry = $this->app->cart->checkout->getEntryById($id);
					if ($entry !== null &&
						$entry->getQuantity() !== $widget->value) {
						$this->updated_entry_ids[] = $id;
						$this->app->cart->checkout->setEntryQuantity($entry,
							$widget->value);
						
						if ($widget->value > 0) {
							$num_items_updated++;
							$item_updated = true;
						} else {
							$num_items_removed++;
							$item_removed = true;
						}

						$widget->value = $entry->getQuantity();
					}
				}
			}
		}

		if ($item_removed)
			$message_display->add(new SwatMessage(sprintf(ngettext(
				'One item has been removed from shopping cart.',
				'%s items have been removed from shopping cart.', 
				$num_items_removed),
				SwatString::numberFormat($num_items_removed)),
				SwatMessage::NOTIFICATION));

		if ($item_updated)
			$message_display->add(new SwatMessage(sprintf(Store::ngettext(
				'One item quantity updated.', '%s item quantities updated.',
				$num_items_updated),
				SwatString::numberFormat($num_items_updated)),
				SwatMessage::NOTIFICATION));

		if ($item_moved)
			$message_display->add(new SwatMessage(
				'One item has been saved for later.'));

		foreach ($this->app->cart->checkout->getMessages() as $message)
			$message_display->add($message);
	}

	// }}}
	// {{{ protected function updateSavedCart()

	protected function updateSavedCart()
	{
		$message_display = $this->ui->getWidget('message_display');
		$saved_view = $this->ui->getWidget('saved_cart_view');

		// check for removed saved items
		$item_removed = false;
		$remove_column = $saved_view->getColumn('remove_column');
		$remove_renderer = $remove_column->getRendererByPosition(); 
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$item_removed = true;
				$entry = $this->app->cart->saved->getEntryById($id);
				$this->app->cart->saved->removeEntry($entry);

				break;
			}
		}

		// check for item being moved to checkout 
		$item_moved = false;
		if (!$item_removed) {
			$move_column = $saved_view->getColumn('move_column');
			$move_renderer = $move_column->getRendererByPosition(); 
			foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
				if ($widget->hasBeenClicked()) {
					$entry = $this->app->cart->saved->getEntryById($id);

					// make sure entry wasn't already moved
					// (i.e. a page resubmit)
					if ($entry === null)
						break;

					$this->added_entry_ids[] = $id;
					$item_moved = true;

					$this->app->cart->saved->removeEntry($entry);
					$this->app->cart->checkout->addEntry($entry);

					break;
				}
			}
		}

		if ($item_removed)
			$message_display->add(new SwatMessage(Store::_(
				'One item has been removed from saved cart.'),
				SwatMessage::NOTIFICATION));

		if ($item_moved)
			$message_display->add(new SwatMessage(Store::_(
				'One item has been moved to shopping cart.'),
				SwatMessage::NOTIFICATION));
	}

	// }}}
	// {{{ protected function moveAllSavedCart()

	/**
	 * Moves all saved cart items to checkout cart
	 */
	protected function moveAllSavedCart()
	{
		$message_display = $this->ui->getWidget('message_display');
		$saved_view = $this->ui->getWidget('saved_cart_view');

		$num_moved_items = 0;
		$move_column = $saved_view->getColumn('move_column');
		$move_renderer = $move_column->getRendererByPosition(); 
		foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
			$entry = $this->app->cart->saved->getEntryById($id);
			$this->added_entry_ids[] = $id;
			$this->app->cart->saved->removeEntry($entry);
			$this->app->cart->checkout->addEntry($entry);
			$num_moved_items++;
		}

		$message_display->add(new SwatMessage(sprintf(Store::ngettext(
			'One item moved to shopping cart.',
			'%s items moved to shopping cart.', $num_moved_items),
			SwatString::numberFormat($num_moved_items))));
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->addHtmlHeadEntry(
			new SwatStyleSheetHtmlHeadEntry('packages/store/styles/cart.css', 1));

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		if ($this->app->cart->checkout->isEmpty()) {
			$empty_message = new SwatMessage(Store::_(
				'Your Shopping Cart is Empty'),
				SwatMessage::NOTIFICATION);

			$empty_message->content_type = 'text/xml';
			$empty_message->secondary_content = Store::_(
				'You can add items to your shopping cart by selecting from '.
				'the menu on the left and browsing for products.');

			$messages = $this->ui->getWidget('message_display');
			$messages->add($empty_message);

			$this->ui->getWidget('cart_frame')->visible = false;
		} else {
			$this->buildAvailableTableView();
			$this->buildUnavailableTableView();
		}

		// always show saved cart if it has items
		$this->buildSavedTableView();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildAvailableTableView()

	protected function buildAvailableTableView()
	{
		$available_view = $this->ui->getWidget('available_cart_view');
		$available_view->model = $this->getAvailableTableStore();

		$available_view->getRow('subtotal')->value =
			$this->app->cart->checkout->getSubtotal();

		$available_view->getRow('shipping')->value =
			$this->app->cart->checkout->getShippingTotal(
				new StoreOrderAddress, new StoreOrderAddress);

		// fall-through assignment of visiblity to both checkout buttons
		$this->ui->getWidget('checkout_top')->visible =
			$this->ui->getWidget('checkout_bottom')->visible =
			$available_view->visible =
			($available_view->model->getRowCount() > 0);

	}

	// }}}
	// {{{ protected function buildUnavailableTableView()

	protected function buildUnavailableTableView()
	{
		$unavailable_view = $this->ui->getWidget('unavailable_cart_view');
		$unavailable_view->model = $this->getUnavailableTableStore();

		$count = $unavailable_view->model->getRowCount();
		if ($count > 0) {
			$this->ui->getWidget('unavailable_cart')->visible = true;
			$message = $this->ui->getWidget('unavailable_cart_message');
			$message->content_type = 'text/xml';

			$title = Store::ngettext('Unavailable Item', 'Unavailable Items',
				$count);

			$text = Store::ngettext(
				'The item below is in your shopping cart but is not '.
				'currently available for purchasing and will not be included '.
				'in your order.',
				'The items below are in your shopping cart but are not '.
				'currently available for purchasing and will not be included '.
				'in your order.', $count);

			ob_start();

			$header_tag = new SwatHtmlTag('h3');
			$header_tag->setContent($title);
			$header_tag->display();

			$paragraph_tag = new SwatHtmlTag('p');
			$paragraph_tag->setContent($text);
			$paragraph_tag->display();

			$message->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function buildSavedTableView()

	protected function buildSavedTableView()
	{
		$saved_view = $this->ui->getWidget('saved_cart_view');
		$saved_view->model = $this->getSavedTableStore();

		$count = $saved_view->model->getRowCount();
		if ($count > 0) {
			if ($count > 1)
				$this->ui->getWidget('saved_cart_move_all_field')->visible =
					true;

			$this->ui->getWidget('saved_cart_form')->visible = true;
			$message = $this->ui->getWidget('saved_cart_message');
			$message->content_type = 'text/xml';

			$title = Store::ngettext('Saved Item', 'Saved Items', $count);
			$text = Store::ngettext(
				'The item below is saved for later and will not be included '.
				'in your order. You may move the item to your shopping cart '.
				'by clicking the “add to cart” button.',
				'The items below are saved for later and will not be included '.
				'in your order. You may move any of the items to your '.
				'shopping cart by clicking the “add to cart” button next to '.
				'the item.',
				$count);

			ob_start();

			$header_tag = new SwatHtmlTag('h3');
			$header_tag->setContent($title);
			$header_tag->display();

			$paragraph_tag = new SwatHtmlTag('p');
			$paragraph_tag->setContent($text);
			$paragraph_tag->display();

			$message->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function getAvailableTableStore()

	protected function getAvailableTableStore()
	{
		$store = new SwatTableStore();

		$entries = $this->app->cart->checkout->getAvailableEntries();
		foreach ($entries as $entry)
			$store->addRow($this->getAvailableRow($entry));

		return $store;
	}

	// }}}
	// {{{ protected function getAvailableRow()

	/**
	 * @return SwatDetailsStore
	 */
	protected function getAvailableRow(StoreCartEntry $entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->quantity = $entry->getQuantity();
		$ds->description = $entry->item->getDescription();
		$ds->price = $entry->getCalculatedItemPrice();
		$ds->extension = $entry->getExtension();
		$ds->message = null;

		if ($entry->item->product->primary_category === null)
			$ds->product_link = null;
		else
			$ds->product_link = 'store/'.$entry->item->product->path;

		return $ds;
	}

	// }}}
	// {{{ protected function getUnavailableTableStore()

	protected function getUnavailableTableStore()
	{
		$store = new SwatTableStore();

		$entries = $this->app->cart->checkout->getUnavailableEntries();
		foreach ($entries as $entry)
			$store->addRow($this->getUnavailableRow($entry));

		return $store;
	}

	// }}}
	// {{{ protected function getUnavailableRow()

	/**
	 * @return SwatDetailsStore
	 */
	protected function getUnavailableRow(StoreCartEntry $entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->description = $entry->item->getDescription();
		$ds->message = null;

		if ($entry->item->product->primary_category === null)
			$ds->product_link = null;
		else
			$ds->product_link = 'store/'.$entry->item->product->path;

		return $ds;
	}

	// }}}
	// {{{ protected function getSavedTableStore()

	protected function getSavedTableStore()
	{
		$store = new SwatTableStore();

		$entries = $this->app->cart->saved->getEntries();
		foreach ($entries as $entry)
			$store->addRow($this->getSavedRow($entry));

		return $store;
	}

	// }}}
	// {{{ protected function getSavedRow()

	/**
	 * @return SwatDetailsStore
	 */
	protected function getSavedRow(StoreCartEntry $entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->quantity = $entry->getQuantity();
		$ds->description = $entry->item->getDescription();
		$ds->price = $entry->getCalculatedItemPrice();
		$ds->extension = $entry->getExtension();
		$ds->message = null;

		if ($entry->item->product->primary_category === null)
			$ds->product_link = null;
		else
			$ds->product_link = 'store/'.$entry->item->product->path;

		return $ds;
	}

	// }}}
}

?>
