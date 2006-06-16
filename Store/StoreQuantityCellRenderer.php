<?php

require_once 'Swat/SwatCellRenderer.php';

/**
 * A renderer for a column of quantity input fields.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreQuantityCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * Id
	 *
	 * The name attribute in the HTML input tag.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Value
	 *
	 * The value attribute in the HTML input tag.
	 *
	 * @var string
	 */
	public $value;

	// }}}
	// {{{ public function render()

	public function render()
	{
		$input_tag = new SwatHtmlTag('input');
		$input_tag->type = 'text';
		$input_tag->name = $this->id.'[]';
		$input_tag->value = $this->value;

		if (isset($_POST[$this->id]))
			if (in_array($this->value, $_POST[$this->id]))
				$input_tag->checked = 'checked';

		$input_tag->display();
	}

	// }}}
	// {{{ public function getTdAttributes()

	/**
	 * Gets TD-tag attributes
	 *
	 * @return array an array of attributes to apply to the TD tag of this cell
	 *                renderer.
	 *
	 * @see SwatCellRenderer::getTdAttributes()
	 */
	public function getTdAttributes()
	{
		return array('class' => 'store-quantity-input-cell-renderer');
	}

	// }}}
}

?>
