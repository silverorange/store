<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Index page for Regions
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreRegionIndex extends AdminIndex
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
			$this->app->replacePage('Region/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatTableView $view)
	{
		$sql = sprintf('select id, title from Region order by %s',
			$this->getOrderByClause($view, 'title'));

		$rs = SwatDB::query($this->app->db, $sql);

		return $rs;
	}

	// }}}
}

?>
