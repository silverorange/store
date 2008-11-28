<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminIndex.php';

/**
 * Index page for Shipping Types
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreShippingTypeIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()
	protected function initInternal()
	{
		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('ShippingType/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = 'select * from ShippingType order by displayorder';

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
}

?>
