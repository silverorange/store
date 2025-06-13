<?php

/**
 * An integer entry widget especially taillored to quantity entry for an
 * e-commerce web application.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityEntry extends SwatIntegerEntry
{
    public function __construct($id = null)
    {
        parent::__construct($id);

        $this->addStyleSheet('packages/store/styles/store-quantity-entry.css');

        $this->minimum_value = 0;
        $this->maxlength = 8;
        $this->size = 3;
        $this->show_thousands_separator = false;
    }

    /**
     * Gets the array of CSS classes that are applied to this entry widget.
     *
     * @return array the array of CSS classes that are applied to this entry
     *               widget
     */
    protected function getCSSClassNames()
    {
        $classes = parent::getCSSClassNames();
        $classes[] = 'store-quantity-entry';

        return array_merge($classes, $this->classes);
    }

    /**
     * Get validation message.
     *
     * @see SwatEntry::getValidationMessage()
     *
     * @param mixed $id
     */
    protected function getValidationMessage($id)
    {
        $message = parent::getValidationMessage($id);

        switch ($id) {
            case 'integer':
                $message->primary_content =
                    Store::_('The %s field must be a whole number.');

                break;

            case 'below-minimum':
                if ($this->minimum_value === 0) {
                    $message->primary_content =
                        Store::_('The %%s field must be at least 1.');
                } else {
                    $message->primary_content =
                        Store::_('The %%s field must be at least %s.');
                }

                break;
        }

        return $message;
    }
}
