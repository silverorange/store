<?php

require_once 'Swat/SwatEntry.php';

/**
 * An entry widget where it is possible to specify the input name manually
 *
 * This is useful for HTTP GET forms where the input name is displayed in the
 * request URI.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreKeywordEntry extends SwatEntry
{
	/**
	 * The name of this keyword entry widget
	 *
	 * The name is used as the XHTML form element name. It will be displayed
	 * in the URI if the parent form is set to use HTTP GET.
	 *
	 * @var string
	 */
	public $name;

	public function display()
	{
		if (!$this->visible)
			return;

		$input_tag = new SwatHtmlTag('input');
		$input_tag->type = $this->html_input_type;
		$input_tag->name = ($this->name === null) ? $this->id : $this->name;
		$input_tag->class = 'swat-entry';
		$input_tag->id = $this->id;
		$input_tag->onfocus = 'this.select();';
		if (!$this->isSensitive())
			$input_tag->disabled = 'disabled';

		$value = $this->getDisplayValue();
		if ($value !== null)
			$input_tag->value = $value;

		if ($this->size !== null)
			$input_tag->size = $this->size;

		if ($this->maxlength !== null)
			$input_tag->maxlength = $this->maxlength;

		if (strlen($this->access_key) > 0)
			$input_tag->accesskey = $this->access_key;

		$input_tag->display();
	}
}

?>
