<?php

/**
 * Cart edit page of checkout.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutCartPage extends StoreCheckoutPage
{
    protected $updated_entry_ids = [];

    protected function getUiXml()
    {
        return __DIR__ . '/checkout-cart.xml';
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        // set table store for widget validation
        $view = $this->ui->getWidget('cart_view');
        $view->model = $this->getTableStore();

        if ($this->ui->hasWidget('checkout_progress')) {
            $checkout_progress = $this->ui->getWidget('checkout_progress');
            $checkout_progress->current_step = 2;
        }

        if (isset($this->layout->cart_lightbox)) {
            $div_tag = new SwatHtmlTag('div');
            $div_tag->class = 'empty-message';
            $div_tag->setContent(Store::_('You can view and edit your ' .
                'shopping cart using the form below.'));

            $this->layout->cart_lightbox->override_content =
                $div_tag->__toString();
        }
    }

    protected function getProgressDependencies()
    {
        return ['checkout/first'];
    }

    // process phase

    public function process()
    {
        parent::process();

        $form = $this->ui->getWidget('form');
        $form->process();

        if ($form->isProcessed()) {
            if ($form->hasMessage()) {
                $message = new SwatMessage(Store::_('There is a problem with ' .
                    'the information submitted.'), SwatMessage::ERROR);

                $message->secondary_content = Store::_('Please address the ' .
                    'fields highlighted below and re-submit the form.');

                $this->ui->getWidget('message_display')->add($message);
            } else {
                $this->processEntries();

                if (!$form->hasMessage()) {
                    $this->app->cart->save();
                }

                if (!$this->checkCart()) {
                    $this->app->relocate($this->getCartSource());
                }

                if ($this->continueButtonHasBeenClicked()) {
                    $this->app->relocate($this->getConfirmationSource());
                }
            }
        }
    }

    /**
     * Whether or not a button has been clicked indicating the customer
     * wants to return to the checkout.
     *
     * @return bool true if the customer is to return to the checkout
     *              and false if the customer is to stay on the checkout
     *              cart page
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
            ['header_continue_button', 'footer_continue_button'];

        foreach ($continue_button_ids as $id) {
            if ($this->ui->hasWidget($id)) {
                $buttons[] = $this->ui->getWidget($id);
            }
        }

        return $buttons;
    }

    protected function getQuantityWidgets()
    {
        $view = $this->ui->getWidget('cart_view');
        $column = $view->getColumn('quantity_column');
        $renderer = $column->getFirstDescendant('SwatWidgetCellRenderer');

        return $renderer->getWidgets('quantity_entry');
    }

    protected function getQuantityWidget($id)
    {
        $view = $this->ui->getWidget('cart_view');
        $column = $view->getColumn('quantity_column');
        $renderer = $column->getRendererByPosition();

        return $renderer->getWidget($id);
    }

    protected function getMoveButtons()
    {
        $buttons = [];
        $view = $this->ui->getWidget('cart_view');

        if ($view->hasColumn('move_column')) {
            $column = $view->getColumn('move_column');
            $renderer = $column->getRendererByPosition();
            $buttons = $renderer->getWidgets('move_button');
        }

        return $buttons;
    }

    protected function getRemoveButtons()
    {
        $buttons = [];
        $view = $this->ui->getWidget('cart_view');

        if ($view->hasColumn('remove_column')) {
            $column = $view->getColumn('remove_column');
            $renderer = $column->getRendererByPosition();
            $buttons = $renderer->getWidgets('remove_button');
        }

        return $buttons;
    }

    protected function processEntries()
    {
        $num_entries_moved = 0;
        $num_entries_removed = 0;
        $num_entries_updated = 0;

        $num_entries_removed += $this->processRemovedEntries();

        if ($num_entries_removed == 0) {
            $num_entries_moved += $this->processMovedEntries();
        }

        if ($num_entries_removed == 0 && $num_entries_moved == 0) {
            $result = $this->processUpdatedEntries();
            $num_entries_removed += $result['num_entries_removed'];
            $num_entries_updated += $result['num_entries_updated'];
        }

        $this->buildCartMessages(
            $num_entries_moved,
            $num_entries_removed,
            $num_entries_updated
        );
    }

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

    protected function processMovedEntries()
    {
        $num_entries_moved = 0;

        foreach ($this->getMoveButtons() as $id => $button) {
            if ($button->hasBeenClicked()) {
                $entry = $this->app->cart->checkout->getEntryById($id);

                // make sure entry wasn't already moved
                // (i.e. a page resubmit)
                if ($entry === null) {
                    break;
                }

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

    protected function processUpdatedEntries()
    {
        $num_entries_removed = 0;
        $num_entries_updated = 0;

        foreach ($this->getQuantityWidgets() as $id => $widget) {
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

    protected function buildCartMessages(
        $num_entries_moved,
        $num_entries_removed,
        $num_entries_updated
    ) {
        $message_display = $this->ui->getWidget('message_display');

        if ($num_entries_removed > 0) {
            $message_display->add(new SwatMessage(sprintf(
                Store::ngettext(
                    'One item has been removed from shopping cart.',
                    '%s items have been removed form shopping cart.',
                    $num_entries_removed
                ),
                SwatString::numberFormat($num_entries_removed)
            ), 'cart'));
        }

        if ($num_entries_moved > 0) {
            $message_display->add(new SwatMessage(
                Store::_('One item has been saved for later.'),
                'cart'
            ));
        }

        if ($num_entries_updated > 0) {
            $message_display->add(new SwatMessage(sprintf(
                Store::ngettext(
                    'One item quantity has been updated.',
                    '%s item quantities have been updated.',
                    $num_entries_updated
                ),
                SwatString::numberFormat($num_entries_updated)
            ), 'cart'));
        }

        foreach ($this->app->cart->checkout->getMessages() as $message) {
            $message_display->add($message);
        }
    }

    // build phase

    protected function buildInternal()
    {
        $this->buildTableView();
    }

    protected function buildTableView()
    {
        $cart = $this->app->cart->checkout;
        $order = $this->app->session->order;

        $view = $this->ui->getWidget('cart_view');
        $view->model = $this->getTableStore();

        $view->getRow('subtotal')->value = $cart->getSubtotal();

        $view->getRow('shipping')->value = $cart->getShippingTotal(
            $order->billing_address,
            $order->shipping_address,
            $order->shipping_type
        );

        $surcharge_total = $cart->getSurchargeTotal($order->payment_methods);
        if ($surcharge_total > 0) {
            $view->getRow('surcharge')->value = $surcharge_total;
        }

        $view->getRow('total')->value = $cart->getTotal(
            $order->billing_address,
            $order->shipping_address,
            $order->shipping_type,
            $order->payment_methods
        );
    }

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

    protected function getDetailsStore(StoreCartEntry $entry)
    {
        $ds = new SwatDetailsStore($entry);

        $ds->quantity = $entry->getQuantity();
        $ds->description = $this->getEntryDescription($entry);
        $ds->price = $entry->getCalculatedItemPrice();
        $ds->extension = $entry->getExtension();
        $ds->discount = $entry->getDiscount();
        $ds->discount_extension = $entry->getDiscountExtension();
        $ds->product_link = $this->app->config->store->path . $entry->item->product->path;
        $ds->item_count = $this->getAvailableProductItemCount($entry);

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

    protected function getEntryDescription(StoreCartEntry $entry)
    {
        $description = [];
        foreach ($entry->item->getDescriptionArray() as $element) {
            $description[] = '<div>' . SwatString::minimizeEntities($element) .
                '</div>';
        }

        return implode("\n", $description);
    }

    protected function getProvStateExclusionDescription(StoreCartEntry $entry)
    {
        $description = null;
        $order = $this->app->session->order;

        if ($order->shipping_address instanceof StoreOrderAddress) {
            $provstate = $order->shipping_address->getInternalValue(
                'provstate'
            );

            foreach ($entry->item->provstate_exclusion_bindings as $binding) {
                if ($binding->getInternalValue('provstate') == $provstate) {
                    $description = sprintf(
                        '<div class="warning">%s</div>',
                        SwatString::minimizeEntities(
                            sprintf(
                                Store::_(
                                    'Note: this item can not be shipped to %s'
                                ),
                                $order->shipping_address->provstate->title
                            )
                        )
                    );
                }
            }
        }

        return $description;
    }

    /**
     * @return string Image dimension shortname
     */
    protected function getImageDimension()
    {
        return 'pinky';
    }

    protected function getAvailableProductItemCount(StoreCartEntry $entry)
    {
        static $item_counts;

        if ($item_counts === null) {
            $item_counts = [];

            $entries = $this->app->cart->checkout->getAvailableEntries();
            foreach ($entries as $current_entry) {
                $id = $this->getEntryIndex($current_entry);
                if (array_key_exists($id, $item_counts)) {
                    $item_counts[$id]++;
                } else {
                    $item_counts[$id] = 1;
                }
            }
        }

        $id = $this->getEntryIndex($entry);
        if (array_key_exists($id, $item_counts)) {
            $count = $item_counts[$id];
        } else {
            $count = 1;
        }

        return $count;
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
            'packages/store/styles/store-checkout-cart-page.css'
        );
    }
}
