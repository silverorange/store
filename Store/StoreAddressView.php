<?php

/**
 * A viewer for an address object.
 *
 * This view contains a link to edit the address and a button to remove the
 * address. This view is a Swat widget and can exist in the Swat widget tree.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddressView extends SwatControl
{
    public $address;

    /**
     * @var string
     */
    protected $edit_address_link = 'account/address%s';

    /**
     * Whether or not the 'remove' button of this view whas been clicked.
     *
     * @return bool true if the remove button has been clicked and false if
     *              it has not
     */
    public function hasBeenClicked()
    {
        $remove_button = $this->getCompositeWidget('remove_button');

        return $remove_button->hasBeenClicked();
    }

    public function display()
    {
        if (!$this->visible) {
            return;
        }

        ob_start();
        $this->address->displayCondensedAsText();
        $address_text = ob_get_clean();

        $div = new SwatHtmlTag('div');
        $div->class = $this->getCssClassString();

        $controls = new SwatHtmlTag('div');
        $controls->class = 'store-address-view-controls';

        $edit_link = new SwatToolLink();
        $edit_link->link = sprintf(
            $this->edit_address_link,
            $this->address->id
        );

        $edit_link->title = Store::_('Edit Address');
        $edit_link->setFromStock('edit');

        $remove_button = $this->getCompositeWidget('remove_button');
        $remove_button->title = Store::_('Remove');
        $remove_button->classes[] = 'store-remove';
        $remove_button->confirmation_message = sprintf(
            Store::_(
                "Are you sure you want to remove the following address?\n\n%s"
            ),
            $address_text
        );

        $div->open();
        $this->address->displayCondensed();
        $controls->open();
        $edit_link->display();
        $remove_button->display();
        $controls->close();
        $div->close();
    }

    /**
     * Creates and adds composite widgets of this widget.
     */
    protected function createCompositeWidgets()
    {
        $remove_button = new SwatConfirmationButton($this->id);
        $this->addCompositeWidget($remove_button, 'remove_button');
    }

    /**
     * Gets the array of CSS classes that are applied to this address view.
     *
     * @return array the array of CSS classes that are applied to this address
     *               view
     */
    protected function getCSSClassNames()
    {
        $classes = ['store-address-view'];

        return array_merge($classes, $this->classes);
    }
}
