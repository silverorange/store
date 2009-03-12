<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/dataobjects/StoreAttributeWrapper.php';

/**
 * Index page for Attributes
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAttributeIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Attribute/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select Attribute.*
			from Attribute
			inner join AttributeType on
				Attribute.attribute_type = AttributeType.id
			order by attribute_type, %s',
			$this->getOrderByClause($view, 'displayorder'));

		$attributes = SwatDB::query($this->app->db, $sql,
			'StoreAttributeWrapper');

		$store = new SwatTableStore();
		foreach ($attributes as $attribute) {
			$ds = new SwatDetailsStore($attribute);
			$ds->order_sensitive =
				(count($attribute->attribute_type->attributes) > 0);

			$store->add($ds);
		}
		return $store;
	}

	// }}}
}

?>
