<?php

require_once 'Swat/SwatLinkCellRenderer.php';

/**
 * A cell renderer that displays a link to add or edit quantity discounts
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemDiscountCellRenderer extends SwatLinkCellRenderer
{
	public $edit_text = 'edit';
	public $id;
	public $db;
	
	public function render()
	{
		$sql = 'select count(QuantityDiscount.id)
			from Item left outer join QuantityDiscount
				on QuantityDiscount.item = Item.id
			where Item.id = %s
			group by Item.id';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		$num = SwatDB::queryOne($this->db, $sql);

		if ($num == 0) {
			parent::render();
			$none_tag = new SwatHtmlTag('span');
			$none_tag->class = 'swat-none';
			$none_tag->setContent(' '.Store::_('<none>'));
			$none_tag->display();
		} else {
			$old_text = $this->text;
			$this->text = $this->edit_text;
			parent::render();
			$this->text = $old_text;
			echo sprintf(Store::_(' (%s)', SwatString::numberFormat($num)));
		}
	}
}

?>
