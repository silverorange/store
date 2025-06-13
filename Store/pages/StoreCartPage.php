<?php

/**
 * Shopping cart display page.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartPage extends SitePage
{
    /**
     * @var SwatUI
     */
    protected $ui;

    /**
     * An array of cart entry ids that were updated.
     *
     * @var array
     */
    protected $updated_entry_ids = [];

    /**
     * An array of cart entry ids that were added (or moved) to a cart.
     *
     * @var array
     */
    protected $added_entry_ids = [];

    /**
     * An array of product counts by product id for available items.
     *
     * @var array
     */
    protected $available_item_counts = [];

    /**
     * An array of product counts by product id for unavailable items.
     *
     * @var array
     */
    protected $unavailable_item_counts = [];

    /**
     * An array of product counts by product id for saved items.
     *
     * @var array
     */
    protected $saved_item_counts = [];

    /**
     * An array of ItemMinimumQuantityGroup.id for groups that haven't
     * reached the minimum quantity.
     */
    protected $item_minimum_quantity_group_warnings = [];

    // init phase

    public function init()
    {
        parent::init();

        if (!isset($this->app->cart->checkout)) {
            throw new StoreException('Store has no checkout cart.');
        }

        $this->ui = new SwatUI();
        $this->ui->loadFromXML($this->getUiXml());

        // set table store for widget validation
        $available_view = $this->ui->getWidget('available_cart_view');
        $available_view->model = $this->getAvailableTableStore();

        $forms = $this->ui->getRoot()->getDescendants('SwatForm');
        foreach ($forms as $form) {
            $form->action = $this->source;
        }

        $this->initInternal();

        $this->ui->init();

        if (isset($this->layout->cart_lightbox)) {
            $div_tag = new SwatHtmlTag('div');
            $div_tag->class = 'empty-message';
            $div_tag->setContent(Store::_('You can view and edit your ' .
                'shopping cart using the form below.'));

            $this->layout->cart_lightbox->override_content =
                $div_tag->__toString();
        }
    }

    protected function initInternal() {}

    protected function getUiXml()
    {
        return __DIR__ . '/cart.xml';
    }

    // process phase

    public function process()
    {
        parent::process();

        $this->processCheckoutCart();
        $this->processSavedCart();
        $this->processItemMinimumQuantityGroupMessages();
    }

    // process phase - checkout cart

    protected function processCheckoutCart()
    {
        $form = $this->ui->getWidget('form');
        $form->process();

        if ($form->isProcessed()) {
            $button = $this->getAvailableMoveAllButton();
            if ($button !== null && $button->hasBeenClicked()) {
                $this->moveAllAvailableCheckoutCart();
            }

            $button = $this->getAvailableRemoveAllButton();
            if ($button !== null && $button->hasBeenClicked()) {
                $this->removeAllAvailableCheckoutCart();
            }

            if ($this->getUnavailableRemoveAllButton()->hasBeenClicked()) {
                $this->removeAllUnavailableCheckoutCart();
            }

            if ($form->hasMessage()) {
                // TODO: this message can show after all items are removed
                $message = new SwatMessage(
                    Store::_(
                        'There is a problem with the information submitted.'
                    ),
                    SwatMessage::ERROR
                );

                $message->secondary_content = Store::_('Please address the ' .
                    'fields highlighted below and re-submit the form.');

                $this->ui->getWidget('message_display')->add($message);
            } else {
                $this->updateCheckoutCart();
                if (!$form->hasMessage() && $this->canCheckout()) {
                    $this->processCheckoutCartCheckoutButtons();
                }
            }
        }
    }

    protected function processCheckoutCartCheckoutButtons()
    {
        if ($this->continueButtonHasBeenClicked()) {
            $this->app->cart->save();
            $this->app->relocate('checkout');
        }
    }

    protected function updateCheckoutCart()
    {
        $message_display = $this->ui->getWidget('message_display');

        $num_entries_moved = 0;
        $num_entries_removed = 0;
        $num_entries_updated = 0;

        $num_entries_removed += $this->removeAvailableEntries();

        if ($num_entries_removed == 0) {
            $num_entries_removed += $this->removeUnavailableEntries();
        }

        if ($num_entries_removed == 0) {
            $num_entries_moved += $this->moveAvailableEntries();
        }

        if ($num_entries_removed == 0 && $num_entries_moved == 0) {
            $num_entries_moved += $this->moveUnavailableEntries();
        }

        if ($num_entries_removed == 0 && $num_entries_moved == 0) {
            $result = $this->updateAvailableEntries();
            $num_entries_updated += $result['num_entries_updated'];
            $num_entries_removed += $result['num_entries_removed'];
        }

        if ($num_entries_removed > 0) {
            $message_display->add(new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been removed from your cart.',
                        '%s items have been removed from your cart.',
                        $num_entries_removed
                    ),
                    SwatString::numberFormat($num_entries_removed)
                ),
                'cart'
            ));
        }

        if ($num_entries_updated > 0) {
            $message_display->add(new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item quantity has been updated.',
                        '%s item quantities have been updated.',
                        $num_entries_updated
                    ),
                    SwatString::numberFormat($num_entries_updated)
                ),
                'cart'
            ));
        }

        if ($num_entries_moved > 0) {
            $moved_message = new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been saved for later.',
                        '%s items have been saved for later.',
                        $num_entries_moved
                    ),
                    SwatString::numberFormat($num_entries_moved)
                ),
                'cart'
            );

            $moved_message->content_type = 'text/xml';

            if (!$this->app->session->isLoggedIn()) {
                $moved_message->secondary_content = sprintf(Store::_(
                    'Items will not be saved unless you %screate an account ' .
                    'or sign in%s.'
                ), '<a href="account">', '</a>');
            }

            $message_display->add($moved_message);
        }

        foreach ($this->app->cart->checkout->getMessages() as $message) {
            $message_display->add($message);
        }
    }

    /**
     * Whether or not a button has been clicked indicating the customer
     * wants to proceed to the checkout.
     *
     * @return bool true if the customer is to proceed to the checkout
     *              and false if the customer is to stay on the cart
     *              page
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

    protected function getContinueButtons()
    {
        $buttons = [];
        $continue_button_ids =
            ['header_checkout_button', 'footer_checkout_button'];

        foreach ($continue_button_ids as $id) {
            if ($this->ui->hasWidget($id)) {
                $buttons[] = $this->ui->getWidget($id);
            }
        }

        return $buttons;
    }

    /**
     * Whether or not the current cart can proceed to the checkout.
     *
     * Subclasses may add additional validation here. By default, all carts can
     * proceed to the checkout.
     *
     * @return bool true if the checkout cart can proceed to the checkout
     *              process. Otherwise false.
     */
    protected function canCheckout()
    {
        return $this->app->cart->checkout->checkoutEnabled();
    }

    // process phase - checkout cart - available entries

    protected function getAvailableQuantityWidgets()
    {
        $view = $this->ui->getWidget('available_cart_view');
        $column = $view->getColumn('quantity_column');
        $renderer = $column->getFirstDescendant('SwatWidgetCellRenderer');

        return $renderer->getWidgets('quantity_entry');
    }

    protected function getAvailableQuantityWidget($id)
    {
        $view = $this->ui->getWidget('available_cart_view');
        $column = $view->getColumn('quantity_column');
        $renderer = $column->getRendererByPosition();

        return $renderer->getWidget($id);
    }

    protected function getAvailableMoveButtons()
    {
        $buttons = [];
        $view = $this->ui->getWidget('available_cart_view');

        if ($view->hasColumn('move_column')) {
            $column = $view->getColumn('move_column');
            $renderer = $column->getRendererByPosition();
            $buttons = $renderer->getWidgets('available_move_button');
        }

        return $buttons;
    }

    protected function getAvailableMoveAllButton()
    {
        if ($this->ui->hasWidget('available_move_all_button')) {
            $button = $this->ui->getWidget('available_move_all_button');
        } else {
            $button = null;
        }

        return $button;
    }

    protected function getAvailableRemoveButtons()
    {
        $buttons = [];
        $view = $this->ui->getWidget('available_cart_view');

        if ($view->hasColumn('remove_column')) {
            $column = $view->getColumn('remove_column');
            $renderer = $column->getRendererByPosition();
            $buttons = $renderer->getWidgets('available_remove_button');
        }

        return $buttons;
    }

    protected function getAvailableRemoveAllButton()
    {
        $button = null;

        if ($this->ui->hasWidget('available_remove_all_button')) {
            $button = $this->ui->getWidget('available_remove_all_button');
        }

        return $button;
    }

    /**
     * @return int the number of entries that were moved
     */
    protected function moveAvailableEntries()
    {
        $num_entries_moved = 0;

        foreach ($this->getAvailableMoveButtons() as $id => $button) {
            if ($button->hasBeenClicked()) {
                $entry = $this->app->cart->checkout->getEntryById($id);

                // make sure entry wasn't already moved
                // (i.e. a page resubmit)
                if ($entry === null) {
                    break;
                }

                $quantity = $this->getAvailableQuantityWidget($id)->value;

                $this->added_entry_ids[] = $id;
                $num_entries_moved++;

                $entry->setQuantity($quantity);

                // note: removing entry needs to happen before adding entry
                // or else the moved entry will be deleted from the database
                $this->app->cart->checkout->removeEntry($entry);
                $this->app->cart->saved->addEntry($entry);

                $this->removeFromAvailableProductCount($entry);

                break;
            }
        }

        return $num_entries_moved;
    }

    /**
     * @return int the number of entries that were removed
     */
    protected function removeAvailableEntries()
    {
        $num_entries_removed = 0;

        foreach ($this->getAvailableRemoveButtons() as $id => $button) {
            if ($button->hasBeenClicked()) {
                $entry = $this->app->cart->checkout->getEntryById($id);
                if ($this->app->cart->checkout->removeEntryById($id) !== null) {
                    $num_entries_removed++;
                    $this->removeFromAvailableProductCount($entry);
                }

                break;
            }
        }

        return $num_entries_removed;
    }

    /**
     * @return int the number of entries that were updated
     */
    protected function updateAvailableEntries()
    {
        $num_entries_updated = 0;
        $num_entries_removed = 0;

        foreach ($this->getAvailableQuantityWidgets() as $id => $widget) {
            if (!$widget->hasMessage()) {
                $entry = $this->app->cart->checkout->getEntryById($id);
                if ($entry !== null
                    && $entry->getQuantity() != $widget->value) {
                    $this->updated_entry_ids[] = $id;
                    $this->app->cart->checkout->setEntryQuantity(
                        $entry,
                        $widget->value
                    );

                    if ($widget->value > 0) {
                        $num_entries_updated++;
                    } else {
                        $num_entries_removed++;
                        $this->removeFromAvailableProductCount($entry);
                    }

                    $widget->value = $entry->getQuantity();
                }
            }
        }

        return [
            'num_entries_updated' => $num_entries_updated,
            'num_entries_removed' => $num_entries_removed,
        ];
    }

    /**
     * moves all available cart items.
     */
    protected function moveAllAvailableCheckoutCart()
    {
        $message_display = $this->ui->getWidget('message_display');

        $num_entries_moved = 0;

        // use individual move buttons to iterate entry ids
        foreach ($this->getAvailableMoveButtons() as $id => $button) {
            $entry = $this->app->cart->checkout->getEntryById($id);

            // make sure entry wasn't already moved
            // (i.e. a page resubmit)
            if ($entry !== null) {
                $quantity = $this->getAvailableQuantityWidget($id)->value;

                $this->added_entry_ids[] = $id;
                $num_entries_moved++;

                $entry->setQuantity($quantity);
                $this->app->cart->checkout->removeEntry($entry);
                $this->app->cart->saved->addEntry($entry);
            }
        }

        if ($num_entries_moved > 0) {
            // since everything was moved, reset entire count.
            $this->resetAvailableProductItemCount();
            $this->resetSavedProductItemCount();

            $moved_message = new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been saved for later.',
                        '%s items have been saved for later.',
                        $num_entries_moved
                    ),
                    SwatString::numberFormat($num_entries_moved)
                ),
                'cart'
            );

            $moved_message->content_type = 'text/xml';

            if (!$this->app->session->isLoggedIn()) {
                $moved_message->secondary_content = sprintf(Store::_(
                    'Items will not be saved unless you %screate an account ' .
                    'or sign in%s.'
                ), '<a href="account">', '</a>');
            }

            $message_display->add($moved_message);
        }
    }

    /**
     * Removes all available cart items.
     */
    protected function removeAllAvailableCheckoutCart()
    {
        $message_display = $this->ui->getWidget('message_display');

        $num_removed_items = 0;

        // use individual remove buttons to iterate entry ids
        foreach ($this->getAvailableRemoveButtons() as $id => $button) {
            $entry = $this->app->cart->checkout->getEntryById($id);

            // make sure entry wasn't already removed
            // (i.e. a page resubmit)
            if ($entry !== null) {
                $this->app->cart->checkout->removeEntry($entry);
                $num_removed_items++;
            }
        }

        if ($num_removed_items > 0) {
            // since everything was moved, reset entire count.
            $this->resetAvailableProductItemCount();

            $message_display->add(new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been removed from your cart.',
                        '%s items have been removed from your cart.',
                        $num_removed_items
                    ),
                    SwatString::numberFormat($num_removed_items)
                ),
                'cart'
            ));
        }
    }

    // process phase - checkout cart - unavailable entries

    protected function getUnavailableMoveButtons()
    {
        $buttons = [];
        $view = $this->ui->getWidget('unavailable_cart_view');

        if ($view->hasColumn('move_column')) {
            $column = $view->getColumn('move_column');
            $renderer = $column->getRendererByPosition();
            $buttons = $renderer->getWidgets('unavailable_move_button');
        }

        return $buttons;
    }

    protected function getUnavailableRemoveButtons()
    {
        $buttons = [];
        $view = $this->ui->getWidget('unavailable_cart_view');

        if ($view->hasColumn('remove_column')) {
            $column = $view->getColumn('remove_column');
            $renderer = $column->getRendererByPosition();
            $buttons = $renderer->getWidgets('unavailable_remove_button');
        }

        return $buttons;
    }

    protected function getUnavailableRemoveAllButton()
    {
        return $this->ui->getWidget('unavailable_remove_all_button');
    }

    /**
     * @return int the number of entries that were moved
     */
    protected function moveUnavailableEntries()
    {
        $num_entries_moved = 0;

        foreach ($this->getUnavailableMoveButtons() as $id => $button) {
            if ($button->hasBeenClicked()) {
                $entry = $this->app->cart->checkout->getEntryById($id);

                // make sure entry wasn't already moved
                // (i.e. a page resubmit)
                if ($entry === null) {
                    break;
                }

                $this->added_entry_ids[] = $id;
                $num_entries_moved++;

                // note: removing entry needs to happen before adding entry
                // or else the moved entry will be deleted from the database
                $this->app->cart->checkout->removeEntry($entry);
                $this->app->cart->saved->addEntry($entry);

                break;
            }
        }

        return $num_entries_moved;
    }

    /**
     * @return int the number of entries that were removed
     */
    protected function removeUnavailableEntries()
    {
        $num_entries_removed = 0;

        foreach ($this->getUnavailableRemoveButtons() as $id => $button) {
            if ($button->hasBeenClicked()) {
                if ($this->app->cart->checkout->removeEntryById($id) !== null) {
                    $num_entries_removed++;
                }

                break;
            }
        }

        return $num_entries_removed;
    }

    /**
     * Removes all unavailable cart items.
     */
    protected function removeAllUnavailableCheckoutCart()
    {
        $message_display = $this->ui->getWidget('message_display');

        $num_removed_items = 0;

        // use individual remove buttons to iterate entry ids
        foreach ($this->getUnavailableRemoveButtons() as $id => $button) {
            $entry = $this->app->cart->checkout->getEntryById($id);

            // make sure entry wasn't already removed
            // (i.e. a page resubmit)
            if ($entry !== null) {
                $this->app->cart->checkout->removeEntry($entry);
                $num_removed_items++;
            }
        }

        if ($num_removed_items > 0) {
            $this->resetUnavailableProductItemCount();

            $message_display->add(new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been removed from your unavailable items.',
                        '%s items have been removed from your unavailable items.',
                        $num_removed_items
                    ),
                    SwatString::numberFormat($num_removed_items)
                ),
                'cart'
            ));
        }
    }

    // process phase - saved cart

    protected function processSavedCart()
    {
        if (!isset($this->app->cart->saved)
            || !$this->ui->hasWidget('saved_cart_form')) {
            return;
        }

        $form = $this->ui->getWidget('saved_cart_form');
        $form->process();

        if ($form->isProcessed()) {
            if ($form->hasMessage()) {
                $message = new SwatMessage(
                    Store::_(
                        'There is a problem with the information submitted.'
                    ),
                    SwatMessage::ERROR
                );

                $message->secondary_content = Store::_('Please address the ' .
                    'fields highlighted below and re-submit the form.');

                $this->ui->getWidget('message_display')->add($message);
            } else {
                if ($this->getSavedMoveAllButton()->hasBeenClicked()) {
                    $this->moveAllSavedCart();
                } elseif ($this->getSavedRemoveAllButton()->hasBeenClicked()) {
                    $this->removeAllSavedCart();
                } else {
                    $this->updateSavedCart();
                }
            }
        }
    }

    protected function getSavedMoveButtons()
    {
        $view = $this->ui->getWidget('saved_cart_view');
        $column = $view->getColumn('move_column');
        $renderer = $column->getRendererByPosition();

        return $renderer->getWidgets('saved_move_button');
    }

    protected function getSavedMoveAllButton()
    {
        return $this->ui->getWidget('saved_move_all_button');
    }

    protected function getSavedRemoveButtons()
    {
        $view = $this->ui->getWidget('saved_cart_view');
        $column = $view->getColumn('remove_column');
        $renderer = $column->getRendererByPosition();

        return $renderer->getWidgets('saved_remove_button');
    }

    protected function getSavedRemoveAllButton()
    {
        return $this->ui->getWidget('saved_remove_all_button');
    }

    protected function updateSavedCart()
    {
        $message_display = $this->ui->getWidget('message_display');

        $num_entries_moved = 0;
        $num_entries_removed = 0;

        $num_entries_removed += $this->removeSavedEntries();

        if ($num_entries_removed == 0) {
            $num_entries_moved += $this->moveSavedEntries();
        }

        if ($num_entries_removed > 0) {
            $message_display->add(new SwatMessage(
                Store::_('One item has been removed from your saved items.'),
                'cart'
            ));
        }

        if ($num_entries_moved > 0) {
            $message_display->add(new SwatMessage(
                Store::_('One item has been moved to your cart.'),
                'cart'
            ));
        }
    }

    /**
     * @return int the number of entries that were moved
     */
    protected function moveSavedEntries()
    {
        $num_entries_moved = 0;

        foreach ($this->getSavedMoveButtons() as $id => $button) {
            if ($button->hasBeenClicked()) {
                $entry = $this->app->cart->saved->getEntryById($id);

                // make sure entry wasn't already moved
                // (i.e. a page resubmit)
                if ($entry === null) {
                    break;
                }

                // note: removing entry needs to happen before adding entry
                // or else the moved entry will be deleted from the database
                $this->app->cart->saved->removeEntry($entry);
                $added_entry = $this->app->cart->checkout->addEntry($entry);

                // make sure entry was added to checkout cart
                if ($added_entry === null) {
                    // put it back in the saved cart if it was not added to
                    // checkout cart
                    $this->app->cart->saved->addEntry($entry);
                    break;
                }

                $this->added_entry_ids[] = $id;
                $num_entries_moved++;

                if ($entry->isAvailable()) {
                    $this->addToAvailableProductCount($entry);
                }

                break;
            }
        }

        return $num_entries_moved;
    }

    /**
     * @return int the number of entries that were removed
     */
    protected function removeSavedEntries()
    {
        $num_entries_removed = 0;

        foreach ($this->getSavedRemoveButtons() as $id => $button) {
            if ($button->hasBeenClicked()) {
                if ($this->app->cart->saved->removeEntryById($id) !== null) {
                    $num_entries_removed++;
                }

                break;
            }
        }

        return $num_entries_removed;
    }

    /**
     * Moves all saved cart items to checkout cart.
     */
    protected function moveAllSavedCart()
    {
        $message_display = $this->ui->getWidget('message_display');

        $num_moved_items = 0;

        // use individual move buttons to iterate entry ids
        foreach ($this->getSavedMoveButtons() as $id => $button) {
            $entry = $this->app->cart->saved->getEntryById($id);

            // make sure entry wasn't already moved
            // (i.e. a page resubmit)
            if ($entry !== null) {
                $this->added_entry_ids[] = $id;

                $this->app->cart->saved->removeEntry($entry);
                $added_entry = $this->app->cart->checkout->addEntry($entry);

                // make sure it was added to the checkout cart
                if ($added_entry === null) {
                    // put it back in the saved cart if it was not added to
                    // checkout cart
                    $this->app->cart->saved->addEntry($entry);
                } else {
                    $num_moved_items++;
                }
            }
        }

        if ($num_moved_items > 0) {
            $this->resetSavedProductItemCount();
            $this->resetAvailableProductItemCount();

            $message_display->add(new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been moved to your cart.',
                        '%s items have been moved to your cart.',
                        $num_moved_items
                    ),
                    SwatString::numberFormat($num_moved_items)
                ),
                'cart'
            ));
        }
    }

    /**
     * Removes all saved cart items.
     */
    protected function removeAllSavedCart()
    {
        $message_display = $this->ui->getWidget('message_display');

        $num_removed_items = 0;

        // use individual remove buttons to iterate entry ids
        foreach ($this->getSavedRemoveButtons() as $id => $button) {
            $entry = $this->app->cart->saved->getEntryById($id);

            // make sure entry wasn't already removed
            // (i.e. a page resubmit)
            if ($entry !== null) {
                $this->app->cart->saved->removeEntry($entry);
                $num_removed_items++;
            }
        }

        if ($num_removed_items > 0) {
            $this->resetSavedProductItemCount();

            $message_display->add(new SwatMessage(
                sprintf(
                    Store::ngettext(
                        'One item has been removed from your saved items.',
                        '%s items have been removed from your saved items.',
                        $num_removed_items
                    ),
                    SwatString::numberFormat($num_removed_items)
                ),
                'cart'
            ));
        }
    }

    // process item mininum quantity group messages

    protected function processItemMinimumQuantityGroupMessages()
    {
        $groups = [];

        foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
            $group = $entry->item->getInternalValue('minimum_quantity_group');

            if ($group !== null) {
                if (!isset($groups[$group])) {
                    $groups[$group] = new stdClass();
                    $groups[$group]->entries = [];
                    $groups[$group]->quantiy = 0;
                    $groups[$group]->group =
                        $entry->item->minimum_quantity_group;
                }

                $groups[$group]->entries[] = $entry;
                $groups[$group]->quantity +=
                    $entry->quantity * $entry->item->part_count;
            }
        }

        foreach ($groups as $g) {
            if ($g->quantity < $g->group->minimum_quantity) {
                $form = $this->getItemMinimumQuantityGroupForm($g->group);

                if ($form->isSubmitted()) {
                    $this->removeItemMinimumQuantityGroupEntries($g);
                } else {
                    $this->addItemMinimumQuantityGroupMessage(
                        $g->group,
                        $g->entries,
                        $g->quantity,
                        $form
                    );
                }
            }
        }
    }

    protected function addItemMinimumQuantityGroupMessage(
        StoreItemMinimumQuantityGroup $group,
        array $entries,
        $quantity,
        SwatForm $form
    ) {
        $this->item_minimum_quantity_group_warnings[] = $group->id;

        $skus = [];

        foreach ($entries as $entry) {
            $skus[] = $entry->item->sku;
        }

        $locale = SwatI18NLocale::get();

        $title = sprintf(
            Store::_('You must purchase a minimum of %s %s ' .
            'in order to check out.'),
            $locale->formatNumber($group->minimum_quantity),
            $group->getSearchLink()
        );

        $content = '';

        if ($group->description != '') {
            $content .= $group->description . ' ';
        }

        $unit = ($quantity == 1) ? $group->part_unit : $group->part_unit_plural;

        $content .= sprintf(
            Store::ngettext(
                'You currently have one %4$s from %2$s in your cart (%3$s).',
                'You currently have %1$s %4$s from %2$s in your cart (%3$s).',
                $quantity
            ),
            $locale->formatNumber($quantity),
            $group->getSearchLink(),
            SwatString::toList($skus),
            $unit
        );

        ob_start();
        $div_tag = new SwatHtmlTag('div');
        $div_tag->class = 'store-item-minimum-quantity-remove';
        $div_tag->open();
        echo SwatString::minimizeEntities(Store::_('You can also:'));
        $form->display();
        $div_tag->close();
        $content .= ob_get_clean();

        $m = new SwatMessage($title, 'warning');
        $m->secondary_content = $content;
        $m->content_type = 'text/xml';

        $message_display = $this->ui->getWidget('message_display');
        $message_display->add($m);
    }

    protected function removeItemMinimumQuantityGroupEntries(stdClass $g)
    {
        foreach ($g->entries as $entry) {
            $this->app->cart->checkout->removeEntry($entry);
        }

        $message_display = $this->ui->getWidget('message_display');
        $message_display->add(new SwatMessage(sprintf(
            Store::_('All %s have been removed from your cart.'),
            $g->group->title
        )));
    }

    protected function getItemMinimumQuantityGroupForm(
        StoreItemMinimumQuantityGroup $group
    ) {
        $form = new SwatForm('item_minimum_quantity_group_' . $group->shortname);
        $form->action = $this->source;

        $button = new SwatButton();
        $button->title = sprintf(
            Store::_('Remove all %s from your cart'),
            $group->title
        );

        $form->addChild($button);

        return $form;
    }

    // build phase

    public function build()
    {
        parent::build();

        if ($this->app->cart->checkout->isEmpty()) {
            $messages = $this->ui->getWidget('message_display');
            $messages->add(
                $this->getEmptyCartMessage(),
                SwatMessageDisplay::DISMISS_OFF
            );

            $this->ui->getWidget('cart_frame')->visible = false;
        } else {
            $this->buildAvailableTableView();
            $this->buildUnavailableTableView();
            $this->buildMessages();

            if (!$this->app->cart->checkout->checkoutEnabled()) {
                foreach ($this->getContinueButtons() as $button) {
                    $button->sensitive = false;
                }
            }
        }

        $this->buildSavedTableView();
        $this->buildInternal();

        $this->layout->startCapture('content');
        $this->ui->display();
        $this->layout->endCapture();
    }

    protected function buildInternal()
    {
        $this->buildPaymentNote();
    }

    protected function getEmptyCartMessage()
    {
        $empty_message = new SwatMessage(
            Store::_('Your Shopping Cart is Empty'),
            'cart'
        );

        $empty_message->content_type = 'text/xml';
        $empty_message->secondary_content = Store::_(
            'You can add items to your cart by browsing our ' .
            '<a href="store">store</a>.'
        );

        return $empty_message;
    }

    protected function buildAvailableTableView()
    {
        $available_view = $this->ui->getWidget('available_cart_view');
        $available_view->model = $this->getAvailableTableStore();

        // no saved card, hide move buttons
        if (!isset($this->app->cart->saved)) {
            if ($available_view->hasColumn('move_column')) {
                $available_view->getColumn('move_column')->visible = false;
            }

            $button = $this->getAvailableMoveAllButton();
            if ($button !== null) {
                $button->visible = false;
            }
        }

        $available_view->getRow('subtotal')->value =
            $this->app->cart->checkout->getSubtotal();

        $class_name = SwatDBClassMap::get('StoreOrderAddress');
        $available_view->getRow('shipping')->value =
            $this->app->cart->checkout->getShippingTotal(
                new $class_name(),
                new $class_name()
            );

        if (count($available_view->model) == 1) {
            $remove_all_button = $this->getAvailableRemoveAllButton();
            if ($remove_all_button !== null) {
                if ($remove_all_button->parent instanceof SwatTableViewRow) {
                    $remove_all_button->parent->visible = false;
                } else {
                    $remove_all_button->visible = false;
                }
            }

            $move_all_button = $this->getAvailableMoveAllButton();
            if ($move_all_button !== null) {
                if ($move_all_button->parent instanceof SwatTableViewRow) {
                    $move_all_button->parent->visible = false;
                } else {
                    $move_all_button->visible = false;
                }
            }
        }

        $available_view->visible = (count($available_view->model) > 0);

        foreach ($this->getContinueButtons() as $button) {
            $button->visible = $available_view->visible;
        }
    }

    protected function buildUnavailableTableView()
    {
        $unavailable_view = $this->ui->getWidget('unavailable_cart_view');
        $unavailable_view->model = $this->getUnavailableTableStore();

        $count = count($unavailable_view->model);
        if ($count > 0) {
            $this->ui->getWidget('unavailable_cart')->visible = true;
            $message = $this->ui->getWidget('unavailable_cart_message');
            $message->content_type = 'text/xml';

            $title = Store::ngettext(
                'Unavailable Item',
                'Unavailable Items',
                $count
            );

            $text = Store::ngettext(
                'The item below is in your cart but is not ' .
                'currently available for purchasing and will not be included ' .
                'in your order.',
                'The items below are in your cart but are not ' .
                'currently available for purchasing and will not be included ' .
                'in your order.',
                $count
            );

            ob_start();

            $header_tag = new SwatHtmlTag('h3');
            $header_tag->id = 'unavailable_cart_title';
            $header_tag->setContent($title);
            $header_tag->display();

            $paragraph_tag = new SwatHtmlTag('p');
            $header_tag->id = 'unavailable_cart_description';
            $paragraph_tag->setContent($text);
            $paragraph_tag->display();

            $message->content = ob_get_clean();

            if ($count === 1) {
                $remove_all_button = $this->getUnavailableRemoveAllButton();
                if ($remove_all_button->parent instanceof SwatTableViewRow) {
                    $remove_all_button->parent->visible = false;
                } else {
                    $remove_all_button->visible = false;
                }
            }
        }
    }

    protected function buildSavedTableView()
    {
        if (!isset($this->app->cart->saved)
            || !$this->ui->hasWidget('saved_cart_view')) {
            return;
        }

        $saved_view = $this->ui->getWidget('saved_cart_view');
        $saved_view->model = $this->getSavedTableStore();

        $count = count($saved_view->model);
        if ($count > 0) {
            if ($count > 1) {
                $this->ui->getWidget('saved_cart_move_all_field')->visible =
                    true;
            }

            $this->ui->getWidget('saved_cart_form')->visible = true;
            $this->ui->getWidget('saved_cart_frame')->title =
                Store::_('Saved Items');

            if (!$this->app->session->isLoggedIn()) {
                $message_display =
                    $this->ui->getWidget('saved_cart_message_display');

                $warning_message = new SwatMessage(
                    sprintf(Store::_(
                        'Items will not be saved unless you %screate an account ' .
                        'or sign in%s.'
                    ), '<a href="account">', '</a>'),
                    SwatMessage::WARNING
                );

                $warning_message->content_type = 'text/xml';

                $message_display->add(
                    $warning_message,
                    SwatMessageDisplay::DISMISS_OFF
                );
            }

            $message = $this->ui->getWidget('saved_cart_message');
            $message->content_type = 'text/xml';

            $text = Store::ngettext(
                'The item below is saved for later and will not be included ' .
                'in your order. You may move the item to your cart ' .
                'by clicking the “Move to Cart” button.',
                'The items below are saved for later and will not be included ' .
                'in your order. You may move any of the items to your ' .
                'cart by clicking the “Move to Cart” button next to ' .
                'the item.',
                $count
            );

            ob_start();

            $paragraph_tag = new SwatHtmlTag('p');
            $paragraph_tag->id = 'saved_cart_description';
            $paragraph_tag->setContent($text);
            $paragraph_tag->display();

            $message->content = ob_get_clean();

            if ($count === 1) {
                $remove_all_button = $this->getSavedRemoveAllButton();
                if ($remove_all_button->parent instanceof SwatTableViewRow) {
                    $remove_all_button->parent->visible = false;
                } else {
                    $remove_all_button->visible = false;
                }

                $move_all_button = $this->getSavedMoveAllButton();
                if ($move_all_button->parent instanceof SwatTableViewRow) {
                    $move_all_button->parent->visible = false;
                } else {
                    $move_all_button->visible = false;
                }
            }
        }
    }

    protected function getAvailableTableStore()
    {
        $store = new SwatTableStore();

        $entries = $this->app->cart->checkout->getAvailableEntries();

        foreach ($entries as $entry) {
            $store->add($this->getAvailableRow($entry));
        }

        return $store;
    }

    /**
     * @return SwatDetailsStore
     */
    protected function getAvailableRow(StoreCartEntry $entry)
    {
        $ds = new SwatDetailsStore($entry);

        $ds->quantity = $entry->getQuantity();
        $ds->description = $this->getEntryDescription($entry);
        $ds->price = $entry->getCalculatedItemPrice();
        $ds->extension = $entry->getExtension();
        $ds->discount = $entry->getDiscount();
        $ds->discount_extension = $entry->getDiscountExtension();
        $ds->message = null;
        $ds->product_link = $this->app->config->store->path . $entry->item->product->path;
        $ds->item_count = $this->getAvailableProductItemCount($entry);

        $group = $entry->item->minimum_quantity_group;
        $ds->minimum_quantity_group = $entry->item->minimum_quantity_group;
        $ds->minimum_quantity_group_warning = ($group !== null
            && in_array($group->id, $this->item_minimum_quantity_group_warnings));

        $image = $entry->item->product->primary_image;
        if ($image === null) {
            $ds->image = null;
            $ds->image_width = null;
            $ds->image_height = null;
        } else {
            $ds->image = $image->getUri($this->getImageDimension());
            $ds->image_width = $image->getWidth($this->getImageDimension());
            $ds->image_height = $image->getHeight($this->getImageDimension());
        }

        if ($entry->alias === null) {
            $ds->alias_sku = null;
        } else {
            $ds->alias_sku = sprintf(
                '(%s)',
                SwatString::minimizeEntities($entry->alias->sku)
            );
        }

        return $ds;
    }

    /**
     * @return string Image dimension shortname
     */
    protected function getImageDimension()
    {
        return 'pinky';
    }

    protected function getUnavailableTableStore()
    {
        $store = new SwatTableStore();

        $entries = $this->app->cart->checkout->getUnavailableEntries();
        foreach ($entries as $entry) {
            $store->add($this->getUnavailableRow($entry));
        }

        return $store;
    }

    /**
     * @return SwatDetailsStore
     */
    protected function getUnavailableRow(StoreCartEntry $entry)
    {
        $ds = new SwatDetailsStore($entry);

        $ds->quantity = $entry->getQuantity();
        $ds->description = $this->getEntryDescription($entry);
        $ds->price = $entry->getCalculatedItemPrice();
        $ds->extension = $entry->getExtension();
        $ds->message = null;

        if ($entry->item->product->primary_category === null) {
            $ds->product_link = null;
        } else {
            $ds->product_link = $this->app->config->store->path .
                $entry->item->product->path;
        }

        $status = $entry->item->getStatus();
        $ds->status = sprintf(
            '<span class="status-%s">%s</span>',
            $status->shortname,
            SwatString::minimizeEntities($status->title)
        );

        if ($entry->alias === null) {
            $ds->alias_sku = null;
        } else {
            $ds->alias_sku = sprintf(
                '(%s)',
                SwatString::minimizeEntities($entry->alias->sku)
            );
        }

        return $ds;
    }

    protected function getSavedTableStore()
    {
        $store = new SwatTableStore();

        $entries = $this->app->cart->saved->getEntries();
        foreach ($entries as $entry) {
            $store->add($this->getSavedRow($entry));
        }

        return $store;
    }

    /**
     * @return SwatDetailsStore
     */
    protected function getSavedRow(StoreCartEntry $entry)
    {
        $ds = new SwatDetailsStore($entry);

        $ds->quantity = $entry->getQuantity();
        $ds->description = $this->getEntryDescription($entry);
        $ds->price = $entry->getCalculatedItemPrice();
        $ds->extension = $entry->getExtension();
        $ds->message = null;
        $status = $entry->item->getStatus();
        $ds->status = sprintf(
            '<span class="status-%s">%s</span>',
            $status->shortname,
            SwatString::minimizeEntities($status->title)
        );

        if ($entry->item->product->primary_category === null) {
            $ds->product_link = null;
        } else {
            $ds->product_link = $this->app->config->store->path .
                $entry->item->product->path;
        }

        if ($entry->alias === null) {
            $ds->alias_sku = null;
        } else {
            $ds->alias_sku = sprintf(
                '(%s)',
                SwatString::minimizeEntities($entry->alias->sku)
            );
        }

        return $ds;
    }

    protected function getEntryDescription(StoreCartEntry $entry)
    {
        $description = [];
        foreach ($entry->item->getDescriptionArray() as $element) {
            $description[] =
                '<div>' . SwatString::minimizeEntities($element) . '</div>';
        }

        $sale_note = $entry->item->getSaleDiscountNote();
        if ($sale_note !== null) {
            $description[] = $sale_note;
        }

        return implode("\n", $description);
    }

    protected function buildMessages()
    {
        try {
            $message_display = $this->ui->getWidget('message_display');
            foreach ($this->app->messages->getAll() as $message) {
                $message_display->add($message);
            }
        } catch (SwatWidgetNotFoundException $e) {
        }
    }

    protected function buildPaymentNote()
    {
        try {
            $payment_cart_note = $this->ui->getWidget('payment_cart_note');
            $payment_cart_note->content_type = 'text/xml';
            ob_start();
            $this->displayPaymentNote();
            $payment_cart_note->content = ob_get_clean();
        } catch (SwatWidgetNotFoundException $e) {
        }
    }

    protected function displayPaymentNote()
    {
        $payment_types = $this->app->getRegion()->payment_types;

        $header = new SwatHtmlTag('h4');
        $header->setContent(Store::_('Accepted Payment Types'));
        $header->display();

        echo '<ul class="payment-types clearfix">';

        foreach ($payment_types as $type) {
            $li_tag = new SwatHtmlTag('li');
            $li_tag->class = 'payment-type payment-type-' . $type->shortname;
            $li_tag->open();

            $this->displayPaymentType($type);

            $li_tag->close();
        }

        echo '</ul>';
    }

    protected function displayPaymentType(StorePaymentType $type)
    {
        if ($type->shortname == 'card') {
            $this->displayAcceptedCardTypes($type);
        } else {
            echo SwatString::minimizeEntities($type->title);
        }
    }

    protected function displayAcceptedCardTypes(StorePaymentType $type)
    {
        $span = new SwatHtmlTag('span');
        $span->setContent(
            sprintf(
                Store::_('%s:'),
                $type->title
            )
        );
        $span->display();

        $card_types = $this->app->getRegion()->card_types;
        $card_names = [];
        foreach ($card_types as $card_type) {
            $card_names[] = $card_type->title;
        }

        $card_names = SwatString::toList($card_names);

        echo ' ';

        $span = new SwatHtmlTag('span');
        $span->class = 'card-types';
        $span->setContent($card_names);
        $span->display();
    }

    // available_item_counts methods

    protected function getAvailableProductItemCount(StoreCartEntry $entry)
    {
        if (count($this->available_item_counts) === 0) {
            $entries = $this->app->cart->checkout->getAvailableEntries();
            foreach ($entries as $current_entry) {
                $this->addToAvailableProductCount($current_entry);
            }
        }

        $id = $this->getEntryIndex($entry);
        if (array_key_exists($id, $this->available_item_counts)) {
            $count = $this->available_item_counts[$id];
        } else {
            $count = 1;
        }

        return $count;
    }

    protected function addToAvailableProductCount(StoreCartEntry $entry)
    {
        $id = $this->getEntryIndex($entry);
        if (array_key_exists($id, $this->available_item_counts)) {
            $this->available_item_counts[$id]++;
        } else {
            $this->available_item_counts[$id] = 1;
        }
    }

    protected function removeFromAvailableProductCount(StoreCartEntry $entry)
    {
        $id = $this->getEntryIndex($entry);
        // only subtract from count if it exists and we're not already at 0.
        if (array_key_exists($id, $this->available_item_counts)
            && $this->available_item_counts[$id] > 0) {
            $this->available_item_counts[$id]--;
        }
    }

    protected function resetAvailableProductItemCount()
    {
        $this->available_item_counts = [];
    }

    // unavailable_item_counts methods

    protected function getUnavailableProductItemCount(StoreCartEntry $entry)
    {
        if (count($this->unavailable_item_counts) === 0) {
            $entries = $this->app->cart->checkout->getUnavailableEntries();
            foreach ($entries as $current_entry) {
                $this->addToUnavailableProductCount($current_entry);
            }
        }

        $id = $this->getEntryIndex($entry);
        if (array_key_exists($id, $this->unavailable_item_counts)) {
            $count = $this->unavailable_item_counts[$id];
        } else {
            $count = 1;
        }

        return $count;
    }

    protected function addToUnavailableProductCount(StoreCartEntry $entry)
    {
        $id = $this->getEntryIndex($entry);
        if (array_key_exists($id, $this->unavailable_item_counts)) {
            $this->unavailable_item_counts[$id]++;
        } else {
            $this->unavailable_item_counts[$id] = 1;
        }
    }

    protected function removeFromUnvailableProductCount(StoreCartEntry $entry)
    {
        $id = $this->getEntryIndex($entry);
        // only subtract from count if it exists and we're not already at 0.
        if (array_key_exists($id, $this->unavailable_item_counts)
            && $this->unavailable_item_counts[$id] > 0) {
            $this->unavailable_item_counts[$id]--;
        }
    }

    protected function resetUnavailableProductItemCount()
    {
        $this->unavailable_item_counts = [];
    }

    // saved_item_counts methods

    protected function getSavedProductItemCount(StoreCartEntry $entry)
    {
        if (count($this->saved_item_counts) === 0) {
            $entries = $this->app->cart->saved->getEntries();
            foreach ($entries as $current_entry) {
                $this->addToSavedProductCount($current_entry);
            }
        }

        $id = $this->getEntryIndex($entry);
        if (array_key_exists($id, $this->saved_item_counts)) {
            $count = $this->saved_item_counts[$id];
        } else {
            $count = 1;
        }

        return $count;
    }

    protected function addToSavedProductCount(StoreCartEntry $entry)
    {
        $id = $this->getEntryIndex($entry);
        if (array_key_exists($id, $this->saved_item_counts)) {
            $this->saved_item_counts[$id]++;
        } else {
            $this->saved_item_counts[$id] = 1;
        }
    }

    protected function removeFromSavedProductCount(StoreCartEntry $entry)
    {
        $id = $this->getEntryIndex($entry);
        // only subtract from count if it exists and we're not already at 0.
        if (array_key_exists($id, $this->saved_item_counts)
            && $this->saved_item_counts[$id] > 0) {
            $this->saved_item_counts[$id]--;
        }
    }

    protected function resetSavedProductItemCount()
    {
        $this->saved_item_counts = [];
    }

    protected function getEntryIndex(StoreCartEntry $entry)
    {
        return $entry->item->getInternalValue('product');
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry('packages/store/styles/store-cart.css');
        $this->layout->addHtmlHeadEntry(
            'packages/store/styles/store-cart-page.css'
        );

        $this->layout->addHtmlHeadEntrySet(
            $this->ui->getRoot()->getHtmlHeadEntrySet()
        );
    }
}
