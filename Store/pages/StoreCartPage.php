<?php

require_once 'Site/pages/SiteArticlePage.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/StoreUI.php';
require_once 'Store/StoreMessage.php';

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';

/**
 * Shopping cart display page
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
abstract class StoreCartPage extends SiteArticlePage
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
			$available_view = $this->ui->getWidget('available_cart_view');
			$available_remove_all = $available_view->getRow('subtotal');

			$unavailable_view = $this->ui->getWidget('unavailable_cart_view');
			$unavailable_remove_all = $unavailable_view->getRow('remove_all');

			if ($available_remove_all->hasBeenClicked())
				$this->removeAllAvailableCheckoutCart();

			if ($unavailable_remove_all->hasBeenClicked())
				$this->removeAllUnavailableCheckoutCart();

			if ($form->hasMessage()) {
				//TODO: this message can show after all items are removed
				$message = new SwatMessage(Store::_(
					'There is a problem with the information submitted.'),
					SwatMessage::ERROR);

				$message->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->ui->getWidget('message_display')->add($message);
			} else {
				$this->updateCheckoutCart();
				if (!$form->hasMessage() &&
					$this->continueButtonHasBeenClicked()) {
					$this->app->cart->save();
					$this->app->relocate('checkout');
				}
			}
		}
	}

	// }}}
	// {{{ protected function continueButtonHasBeenClicked()

	/**
	 * Whether or not a button has been clicked indicating the customer
	 * wants to proceed to the checkout
	 *
	 * @return boolean true if the customer is to proceed to the checkout
	 *                  and false if the customer is to stay on the cart
	 *                  page.
	 */
	protected function continueButtonHasBeenClicked()
	{
		$continue_button_ids =
			array('header_checkout_button', 'footer_checkout_button');

		$continue_button_clicked = false;
		foreach ($continue_button_ids as $id) {
			$button = $this->ui->getWidget($id);
			if ($button->hasBeenClicked()) {
				$continue_button_clicked = true;
				break;
			}
		}

		return $continue_button_clicked;
	}

	// }}}
	// {{{ protected function processSavedCartForm()

	protected function processSavedCartForm()
	{
		$form = $this->ui->getWidget('saved_cart_form');
		$form->process();

		if ($form->isProcessed()) {
			if ($form->hasMessage()) {
				$message = new SwatMessage(Store::_(
					'There is a problem with the information submitted.'),
					SwatMessage::ERROR);

				$message->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->ui->getWidget('message_display')->add($message);
			} else {
				$saved_view = $this->ui->getWidget('saved_cart_view');
				$remove_button = $saved_view->getRow('remove_all');
				$move_button =
					$this->ui->getWidget('saved_cart_move_all_button');

				if ($move_button->hasBeenClicked())
					$this->moveAllSavedCart();
				elseif ($remove_button->hasBeenClicked())
					$this->removeAllSavedCart();
				else
					$this->updateSavedCart();
			}
		}
	}

	// }}}
	// {{{ protected function getAvailableMoveRenderer()

	protected function getAvailableMoveRenderer()
	{
		$view = $this->ui->getWidget('available_cart_view');
		$column = $view->getColumn('move_column');
		return $column->getRendererByPosition(); 
	}

	// }}}
	// {{{ protected function getAvailableRemoveRenderer()

	protected function getAvailableRemoveRenderer()
	{
		$view = $this->ui->getWidget('available_cart_view');
		$column = $view->getColumn('remove_column');
		return $column->getRendererByPosition(); 
	}

	// }}}
	// {{{ protected function getAvailableQuantityRenderer()

	protected function getAvailableQuantityRenderer()
	{
		$view = $this->ui->getWidget('available_cart_view');
		$column = $view->getColumn('quantity_column');
		return $column->getRendererByPosition(); 
	}

	// }}}
	// {{{ protected function getUnavailableRemoveRenderer()

	protected function getUnavailableRemoveRenderer()
	{
		$view = $this->ui->getWidget('unavailable_cart_view');
		$column = $view->getColumn('remove_column');
		return $column->getRendererByPosition(); 
	}

	// }}}
	// {{{ protected function getUnavailableMoveRenderer()

	protected function getUnavailableMoveRenderer()
	{
		$view = $this->ui->getWidget('unavailable_cart_view');
		$column = $view->getColumn('move_column');
		return $column->getRendererByPosition(); 
	}

	// }}}
	// {{{ protected function getSavedMoveRenderer()

	protected function getSavedMoveRenderer()
	{
		$view = $this->ui->getWidget('saved_cart_view');
		$column = $view->getColumn('move_column');
		return $column->getRendererByPosition(); 
	}

	// }}}
	// {{{ protected function getSavedRemoveRenderer()

	protected function getSavedRemoveRenderer()
	{
		$view = $this->ui->getWidget('saved_cart_view');
		$column = $view->getColumn('remove_column');
		return $column->getRendererByPosition(); 
	}

	// }}}
	// {{{ protected function updateCheckoutCart()

	protected function updateCheckoutCart()
	{
		$message_display = $this->ui->getWidget('message_display');

		$num_entries_moved   = 0;
		$num_entries_removed = 0;
		$num_entries_updated = 0;

		$num_entries_removed += $this->removeAvailableEntries();

		if ($num_entries_removed == 0)
			$num_entries_removed += $this->removeUnavailableEntries();

		if ($num_entries_removed == 0)
			$num_entries_moved += $this->moveAvailableEntries();

		if ($num_entries_removed == 0 && $num_entries_moved == 0)
			$num_entries_moved += $this->moveUnavailableEntries();

		if ($num_entries_removed == 0 && $num_entries_moved == 0) {
			$result = $this->updateAvailableEntries();
			$num_entries_updated += $result['num_entries_updated'];
			$num_entries_removed += $result['num_entries_removed'];
		}

		if ($num_entries_removed > 0)
			$message_display->add(new StoreMessage(sprintf(Store::ngettext(
				'One item has been removed from shopping cart.',
				'%s items have been removed from shopping cart.', 
				$num_entries_removed),
				SwatString::numberFormat($num_entries_removed)),
				StoreMessage::CART_NOTIFICATION));

		if ($num_entries_updated > 0)
			$message_display->add(new StoreMessage(sprintf(Store::ngettext(
				'One item quantity has been updated.',
				'%s item quantities have been updated.',
				$num_entries_updated),
				SwatString::numberFormat($num_entries_updated)),
				StoreMessage::CART_NOTIFICATION));

		if ($num_entries_moved > 0) {
			$moved_message = new StoreMessage(
				Store::_('One item has been saved for later.'),
				StoreMessage::CART_NOTIFICATION);

			$moved_message->content_type = 'text/xml';

			if (!$this->app->session->isLoggedIn())
				$moved_message->secondary_content = sprintf(Store::_(
					'Items will not be saved unless you %screate an account '.
					'or log in%s.'), '<a href="account">', '</a>');

			$message_display->add($moved_message);
		}

		foreach ($this->app->cart->checkout->getMessages() as $message)
			$message_display->add($message);
	}

	// }}}
	// {{{ protected function removeAllAvailableCheckoutCart()

	/**
	 * Removes all available cart items
	 */
	protected function removeAllAvailableCheckoutCart()
	{
		$message_display = $this->ui->getWidget('message_display');

		$num_removed_items = 0;

		// pick an arbitrary renderer to iterate existing entry ids
		$remove_renderer = $this->getAvailableRemoveRenderer();
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			$entry = $this->app->cart->checkout->getEntryById($id);

			// make sure entry wasn't already removed
			// (i.e. a page resubmit)
			if ($entry !== null) {
				$this->app->cart->checkout->removeEntry($entry);
				$num_removed_items++;
			}
		}

		if ($num_removed_items > 0)
			$message_display->add(new StoreMessage(
				sprintf(Store::ngettext(
				'One item has been removed from cart.',
				'%s items have been removed from cart.', $num_removed_items),
				SwatString::numberFormat($num_removed_items)),
				StoreMessage::CART_NOTIFICATION));
	}

	// }}}
	// {{{ protected function removeAllUnavailableCheckoutCart()

	/**
	 * Removes all unavailable cart items
	 */
	protected function removeAllUnavailableCheckoutCart()
	{
		$message_display = $this->ui->getWidget('message_display');

		$num_removed_items = 0;

		// pick an arbitrary renderer to iterate existing entry ids
		$remove_renderer = $this->getUnavailableRemoveRenderer();
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			$entry = $this->app->cart->checkout->getEntryById($id);

			// make sure entry wasn't already removed
			// (i.e. a page resubmit)
			if ($entry !== null) {
				$this->app->cart->checkout->removeEntry($entry);
				$num_removed_items++;
			}
		}

		if ($num_removed_items > 0)
			$message_display->add(new StoreMessage(
				sprintf(Store::ngettext(
				'One item has been removed from unavailable items.',
				'%s items have been removed from unavailable items.',
				$num_removed_items),
				SwatString::numberFormat($num_removed_items)),
				StoreMessage::CART_NOTIFICATION));
	}

	// }}}
	// {{{ protected function updateSavedCart()

	protected function updateSavedCart()
	{
		$message_display = $this->ui->getWidget('message_display');

		$num_entries_moved   = 0;
		$num_entries_removed = 0;

		$num_entries_removed += $this->removeSavedEntries();

		if ($num_entries_removed == 0)
			$num_entries_moved += $this->moveSavedEntries();

		if ($num_entries_removed > 0)
			$message_display->add(new StoreMessage(
				Store::_('One item has been removed from saved items.'),
				StoreMessage::CART_NOTIFICATION));

		if ($num_entries_moved > 0)
			$message_display->add(new StoreMessage(
				Store::_('One item has been moved to shopping cart.'),
				StoreMessage::CART_NOTIFICATION));
	}

	// }}}
	// {{{ protected function moveAllSavedCart()

	/**
	 * Moves all saved cart items to checkout cart
	 */
	protected function moveAllSavedCart()
	{
		$message_display = $this->ui->getWidget('message_display');

		$num_moved_items = 0;

		// pick an arbitrary renderer to iterate existing entry ids
		$move_renderer = $this->getSavedMoveRenderer();
		foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
			$entry = $this->app->cart->saved->getEntryById($id);

			// make sure entry wasn't already moved
			// (i.e. a page resubmit)
			if ($entry !== null) {
				$this->added_entry_ids[] = $id;
				$this->app->cart->saved->removeEntry($entry);
				$this->app->cart->checkout->addEntry($entry);
				$num_moved_items++;
			}
		}

		if ($num_moved_items > 0)
			$message_display->add(new StoreMessage(
				sprintf(Store::ngettext(
				'One item moved to shopping cart.',
				'%s items moved to shopping cart.', $num_moved_items),
				SwatString::numberFormat($num_moved_items)),
				StoreMessage::CART_NOTIFICATION));
	}

	// }}}
	// {{{ protected function removeAllSavedCart()

	/**
	 * Removes all saved cart items
	 */
	protected function removeAllSavedCart()
	{
		$message_display = $this->ui->getWidget('message_display');

		$num_removed_items = 0;

		// pick an arbitrary renderer to iterate existing entry ids
		$remove_renderer = $this->getSavedRemoveRenderer();
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			$entry = $this->app->cart->saved->getEntryById($id);

			// make sure entry wasn't already removed
			// (i.e. a page resubmit)
			if ($entry !== null) {
				$this->app->cart->saved->removeEntry($entry);
				$num_removed_items++;
			}
		}

		if ($num_removed_items > 0)
			$message_display->add(new StoreMessage(
				sprintf(Store::ngettext(
				'One item has been removed from saved items.',
				'%s items have been removed from saved items.',
				$num_removed_items),
				SwatString::numberFormat($num_removed_items)),
				StoreMessage::CART_NOTIFICATION));
	}

	// }}}
	// {{{ protected function moveAvailableEntries()

	/**
	 * @return integer the number of entries that were moved.
	 */
	protected function moveAvailableEntries()
	{
		$num_entries_moved = 0;

		$quantity_renderer = $this->getAvailableQuantityRenderer();
		$move_renderer = $this->getAvailableMoveRenderer();
		foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$entry = $this->app->cart->checkout->getEntryById($id);

				// make sure entry wasn't already moved
				// (i.e. a page resubmit)
				if ($entry === null)
					break;

				$quantity = $quantity_renderer->getWidget($id)->value;

				$this->added_entry_ids[] = $id;
				$num_entries_moved++;

				$entry->setQuantity($quantity);
				$this->app->cart->checkout->removeEntry($entry);
				$this->app->cart->saved->addEntry($entry);

				break;
			}
		}

		return $num_entries_moved;
	}

	// }}}
	// {{{ protected function removeAvailableEntries()

	/**
	 * @return integer the number of entries that were removed.
	 */
	protected function removeAvailableEntries()
	{
		$num_entries_removed = 0;

		$remove_renderer = $this->getAvailableRemoveRenderer();
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				if ($this->app->cart->checkout->removeEntryById($id) !== null)
					$num_entries_removed++;

				break;
			}
		}

		return $num_entries_removed;
	}

	// }}}
	// {{{ protected function updateAvailableEntries()

	/**
	 * @return integer the number of entries that were updated.
	 */
	protected function updateAvailableEntries()
	{
		$num_entries_updated = 0;
		$num_entries_removed = 0;

		$quantity_renderer = $this->getAvailableQuantityRenderer();
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

		return array(
			'num_entries_updated' => $num_entries_updated,
			'num_entries_removed' => $num_entries_removed,
		);
	}

	// }}}
	// {{{ protected function moveUnavailableEntries()

	/**
	 * @return integer the number of entries that were moved.
	 */
	protected function moveUnavailableEntries()
	{
		$num_entries_moved = 0;

		$move_renderer = $this->getUnavailableMoveRenderer();
		foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$entry = $this->app->cart->checkout->getEntryById($id);

				// make sure entry wasn't already moved
				// (i.e. a page resubmit)
				if ($entry === null)
					break;

				$this->added_entry_ids[] = $id;
				$num_entries_moved++;

				$this->app->cart->checkout->removeEntry($entry);
				$this->app->cart->saved->addEntry($entry);

				break;
			}
		}

		return $num_entries_moved;
	}

	// }}}
	// {{{ protected function removeUnavailableEntries()

	/**
	 * @return integer the number of entries that were removed.
	 */
	protected function removeUnavailableEntries()
	{
		$num_entries_removed = 0;

		$remove_renderer = $this->getUnavailableRemoveRenderer();
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				if ($this->app->cart->checkout->removeEntryById($id) !== null)
					$num_entries_removed++;

				break;
			}
		}

		return $num_entries_removed;
	}

	// }}}
	// {{{ protected function moveSavedEntries()

	/**
	 * @return integer the number of entries that were moved.
	 */
	protected function moveSavedEntries()
	{
		$num_entries_moved = 0;

		$move_renderer = $this->getSavedMoveRenderer();
		foreach ($move_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				$entry = $this->app->cart->saved->getEntryById($id);

				// make sure entry wasn't already moved
				// (i.e. a page resubmit)
				if ($entry === null)
					break;

				$this->added_entry_ids[] = $id;
				$num_entries_moved++;

				$this->app->cart->saved->removeEntry($entry);
				$this->app->cart->checkout->addEntry($entry);

				break;
			}
		}

		return $num_entries_moved;
	}

	// }}}
	// {{{ protected function removeSavedEntries()

	/**
	 * @return integer the number of entries that were removed.
	 */
	protected function removeSavedEntries()
	{
		$num_entries_removed = 0;

		$remove_renderer = $this->getSavedRemoveRenderer();
		foreach ($remove_renderer->getClonedWidgets() as $id => $widget) {
			if ($widget->hasBeenClicked()) {
				if ($this->app->cart->saved->removeEntryById($id) !== null)
					$num_entries_removed++;

				break;
			}
		}

		return $num_entries_removed;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if ($this->app->cart->checkout->isEmpty()) {
			$empty_message = new StoreMessage(
				Store::_('Your Shopping Cart is Empty'),
				StoreMessage::CART_NOTIFICATION);

			$empty_message->content_type = 'text/xml';
			$empty_message->secondary_content = Store::_(
				'You can add items to your shopping cart by selecting from '.
				'the menu on the left and browsing for products.');

			$messages = $this->ui->getWidget('message_display');
			$messages->add($empty_message, SwatMessageDisplay::DISMISS_OFF);

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

		$class_name = SwatDBClassMap::get('StoreOrderAddress');
		$available_view->getRow('shipping')->value =
			$this->app->cart->checkout->getShippingTotal(
				new $class_name(), new $class_name);

		if (count($available_view->model) == 1)
			$available_view->getRow('subtotal')->button_visible = false;

		// fall-through assignment of visiblity to both checkout buttons
		$this->ui->getWidget('header_checkout_button')->visible =
			$this->ui->getWidget('footer_checkout_button')->visible =
			$available_view->visible =
			(count($available_view->model) > 0);

	}

	// }}}
	// {{{ protected function buildUnavailableTableView()

	protected function buildUnavailableTableView()
	{
		$unavailable_view = $this->ui->getWidget('unavailable_cart_view');
		$unavailable_view->model = $this->getUnavailableTableStore();

		$count = count($unavailable_view->model);
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

			if ($count == 1)
				$unavailable_view->getRow('remove_all')->visible = false;
		}
	}

	// }}}
	// {{{ protected function buildSavedTableView()

	protected function buildSavedTableView()
	{
		$saved_view = $this->ui->getWidget('saved_cart_view');
		$saved_view->model = $this->getSavedTableStore();

		$count = count($saved_view->model);
		if ($count > 0) {
			if ($count > 1)
				$this->ui->getWidget('saved_cart_move_all_field')->visible =
					true;

			$this->ui->getWidget('saved_cart_form')->visible = true;
			$this->ui->getWidget('saved_cart_frame')->title =
				Store::_('Saved Items');

			if (!$this->app->session->isLoggedIn()) {
				$message_display =
					$this->ui->getWidget('saved_cart_message_display');

				$warning_message = new SwatMessage(sprintf(Store::_(
					'Items will not be saved unless you %screate an account '.
					'or log in%s.'), '<a href="account">', '</a>'),
					SwatMessage::WARNING);

				$warning_message->content_type = 'text/xml';

				$message_display->add($warning_message,
					SwatMessageDisplay::DISMISS_OFF);
			}

			$message = $this->ui->getWidget('saved_cart_message');
			$message->content_type = 'text/xml';

			$text = Store::ngettext(
				'The item below is saved for later and will not be included '.
				'in your order. You may move the item to your shopping cart '.
				'by clicking the “Move to Cart” button.',
				'The items below are saved for later and will not be included '.
				'in your order. You may move any of the items to your '.
				'shopping cart by clicking the “Move to Cart” button next to '.
				'the item.',
				$count);

			ob_start();

			$paragraph_tag = new SwatHtmlTag('p');
			$paragraph_tag->id = 'saved_cart_description';
			$paragraph_tag->setContent($text);
			$paragraph_tag->display();

			$message->content = ob_get_clean();

			if ($count == 1)
				$saved_view->getRow('remove_all')->visible = false;
		}
	}

	// }}}
	// {{{ protected function getAvailableTableStore()

	protected function getAvailableTableStore()
	{
		$store = new SwatTableStore();

		$entries = $this->app->cart->checkout->getAvailableEntries();
		foreach ($entries as $entry)
			$store->add($this->getAvailableRow($entry));

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
			$store->add($this->getUnavailableRow($entry));

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

		$ds->status = $entry->item->getStatus()->title;

		return $ds;
	}

	// }}}
	// {{{ protected function getSavedTableStore()

	protected function getSavedTableStore()
	{
		$store = new SwatTableStore();

		$entries = $this->app->cart->saved->getEntries();
		foreach ($entries as $entry)
			$store->add($this->getSavedRow($entry));

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

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-cart-page.css',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
