<?php

require_once 'Swat/SwatFlydown.php';
require_once 'Swat/SwatEntry.php';
require_once 'Swat/SwatControl.php';

/**
 * A custom action for grouping items inside products
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemGroupAction extends SwatControl
{
	public $db;
	public $product_id;

	private $groups;
	private $group_title;
	private $options;

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addJavaScript('javascript/store-item-group-action.js');
	}

	public function init()
	{
		$this->groups = new SwatFlydown($this->id.'_groups');
		$this->groups->show_blank = false;
		$this->groups->parent = $this;

		$this->group_title = new SwatEntry($this->id.'_title');
		$this->group_title->value = Store::_('group name');
		$this->group_title->size = 10;
		$this->group_title->parent = $this;

		$this->options = SwatDB::getOptionArray($this->db, 'ItemGroup', 'title',
			 'id', 'title', sprintf('product = %s', $this->product_id));
	}

	public function display()
	{
		if (count($this->options)) {
			$this->groups->addOptionsByArray($this->options);
			$this->groups->addDivider();
		}

		$this->groups->addOption('no_group', Store::_('<none>'));
		$this->groups->addOption('new_group', Store::_('<new group>'));

		$this->groups->display();
		$this->group_title->display();
		$this->displayInlineJavaScript();
	}

	public function process()
	{
		$this->groups->process();
		$this->group_title->process();
	}

	public function processAction($items)
	{
		$msg = null;
		$group_id = $this->groups->value;

		// create a new item group
		if (strcmp($group_id, 'new_group') == 0) {
			$new_title = $this->group_title->value;

			$group_id = SwatDB::insertRow($this->db, 'ItemGroup',
				array('title', 'integer:product'),
				array('title' => $new_title, 'product' => $this->product_id),
				'id');

			$msg = new SwatMessage(
				sprintf(Store::ngettext(
				'One item has been added to the new group “%s”.',
				'%d items have been added to the new group “%s”.',
				count($items)), SwatString::numberFormat(count($items)),
				$new_title), SwatMessage::NOTIFICATION);

		} elseif (strcmp($group_id, 'no_group') == 0) {
			$group_id = null;

			$msg = new SwatMessage(
				sprintf(Store::ngettext(
				'One item has been removed from a group.', 
				'%d items have been removed from group(s).', count($items)),
				SwatString::numberFormat(count($items))),
				SwatMessage::NOTIFICATION);

		} else {
			$sql = 'select title from ItemGroup where id = %s';
			$sql = sprintf($sql, $this->db->quote($group_id, 'integer'));
			$old_title = SwatDB::queryOne($this->db, $sql);
			$msg = new SwatMessage(
				sprintf(Store::ngettext(
				'One item has been added to the group “%s”.', 
				'%d items have been added to the group “%s”.',
				count($items)), SwatString::numberFormat(count($items)),
				$old_title), SwatMessage::NOTIFICATION);
		}

		SwatDB::updateColumn($this->db, 'Item', 'integer:item_group', $group_id, 
			'integer:id', $items);

		return $msg;
	}

	public function getFocusableHtmlId()
	{
		if ($this->groups === null)
			return null;

		return $this->groups->id;
	}

	protected function displayInlineJavaScript()
	{
		$values = array();
		foreach ($this->groups->options as $option)
			$values[] = "'".$option->value."'";

		return sprintf("var %s = new ItemGroupAction('%s', [%s]);\n",
			$this->id, $this->id, implode(', ', $values));
	}
}

?>
