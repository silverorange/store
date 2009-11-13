<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Index page for item minimum quantity groups
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemMinimumQuantityGroupIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->getSelection());
		$message = null;

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('ItemMinimumQuantityGroup/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}

		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select id, title, shortname
				from ItemMinimumQuantityGroup order by %s',
			$this->getOrderByClause($view, 'title'));

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
}

?>
