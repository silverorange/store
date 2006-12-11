<?php

require_once 'Swat/SwatConfirmationButton.php';
require_once 'Store/StoreTableViewButtonRow.php';

/**
 * A table view row with an embedded confirmation button
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreTableViewConfirmationButtonRow extends StoreTableViewButtonRow
{
	// {{{ public properties

	/**
	 * Confirmation message of the button
	 *
	 * @var string
	 */
	public $confirmation_message;

	// }}}
	// {{{ protected function displayButton()

	protected function displayButton()
	{
		// properties may have been modified since the widgets were created
		$this->button->title = $this->title;
		$this->button->tab_index = $this->tab_index;
		$this->button->confirmation_message = $this->confirmation_message;
		$this->button->display();
	}

	// }}}
	// {{{ protected function createEmbeddedWidgets()

	protected function createEmbeddedWidgets()
	{
		if (!$this->widgets_created) {
			$this->button = new SwatConfirmationButton($this->id.'_button');
			$this->button->parent = $this;
			$this->button->classes[] = 'compact-button';
			$this->widgets_created = true;
		}
	}

	// }}}
}
