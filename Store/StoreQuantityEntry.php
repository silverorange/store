<?php

require_once 'Swat/SwatIntegerEntry.php';

/**
 * An integer entry widget especially taillored to quantity entry for an
 * e-commerce web application
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityEntry extends SwatIntegerEntry
{
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet('packages/store/styles/store-quantity-entry.css',
			 Store::PACKAGE_ID);

		$this->minimum_value = 0;
		$this->maxlength = 8;
		$this->size = 3;
		$this->show_thousands_separator = false;
	}

	// }}}
	// {{{ protected function getCSSClassNames()
	/**
	 * Gets the array of CSS classes that are applied to this entry widget
	 *
	 * @return array the array of CSS classes that are applied to this entry
	 *                widget.
	 */
	protected function getCSSClassNames()
	{
		$classes = parent::getCSSClassNames();
		$classes[] = 'store-quantity-entry';
		$classes = array_merge($classes, $this->classes);
		return $classes;
	}

	// }}}
	// {{{ protected function getValidationMessage()

	/**
	 * Get validation message
	 *
	 * @see SwatEntry::getValidationMessage()
	 */
	protected function getValidationMessage($id)
	{
		switch ($id) {
		case 'integer':
			return Store::_('The %s field must be a whole number.');
		case 'below-minimum':
			if ($this->minimum_value === 0)
				return Store::_('The %%s field must be at least 1.');
			else
				return Store::_('The %%s field must be at least %s.');

		default:
			return parent::getValidationMessage($id);
		}
	}

	// }}}
}

?>
