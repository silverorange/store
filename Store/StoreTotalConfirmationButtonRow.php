<?php

require_once 'Swat/SwatConfirmationButton.php';
require_once 'Store/StoreTotalRow.php';

/**
 * A total row containing a confirmation button
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreTotalConfirmationButtonRow extends StoreTotalRow
{
	public $button_span;
	public $button_title;
	public $button_confirmation_message;
	public $button_tab_index;
	public $button_visible = true;

	protected $button;
	protected $widgets_created = false;

	public function hasBeenClicked()
	{
		$has_been_clicked = false;

		if ($this->offset > 0) {
			$this->createEmbeddedWidgets();
			$has_been_clicked = $this->button->hasBeenClicked();
		}

		return $has_been_clicked;
	}

	public function init()
	{
		parent::init();

		if ($this->offset > 0) {
			$this->createEmbeddedWidgets();
			$this->button->init();
		}
	}

	public function process()
	{
		parent::process();

		if ($this->offset > 0) {
			$this->createEmbeddedWidgets();
			$this->button->process();
		}
	}

	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();

		if ($this->offset > 0) {
			$this->createEmbeddedWidgets();
			$set->addEntrySet($this->button->getHtmlHeadEntrySet());
		}

		return $set;
	}

	protected function displayBlank()
	{
		if ($this->offset > 0) {
			$this->createEmbeddedWidgets();
			$td_tag = new SwatHtmlTag('td');
			$td_tag->class = 'button-cell';
			$td_tag->colspan = $this->offset;
			$td_tag->open();

			$this->button->title = $this->button_title;
			$this->button->classes[] = 'compact-button';
			$this->button->tab_index = $this->button_tab_index;
			$this->button->visible = $this->button_visible;
			$this->button->confirmation_message =
				$this->button_confirmation_message;

			$this->button->display();

			$td_tag->close();
		}
	}

	protected function createEmbeddedWidgets()
	{
		if (!$this->widgets_created) {
			$this->button = new SwatConfirmationButton($this->id.'_button');
			$this->button->parent = $this;
			$this->widgets_created = true;
		}
	}
}

?>
