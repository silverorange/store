<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Index page for Locales
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreLocaleIndex extends AdminIndex
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
			$this->app->replacePage('Locale/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = 'select Locale.id, Region.title as region_title
				from Locale
				inner join Region on Locale.region = Region.id
				order by %s';

		$sql = sprintf($sql, $this->getOrderByClause($view, 'id'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		return $store;
	}

	// }}}
}	

?>
