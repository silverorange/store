<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StorePriceRangeWrapper.php';

/**
 * Index page for PriceRanges
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePriceRangeIndex extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/PriceRange/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('PriceRange/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = 'select * from PriceRange order by %s';

		$sql = sprintf('select * from PriceRange order by %s',
			$this->getOrderByClause($view,
				'PriceRange.start_price nulls first, PriceRange.end_price'));

		$rs = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StorePriceRangeWrapper'));

		$store = new SwatTableStore();

		foreach ($rs as $row)
		{
			$ds = new SwatDetailsStore($row);
			$ds->title = $row->getTitle();
			$store->add($ds);
		}

		return $store;
	}

	// }}}
}

?>
