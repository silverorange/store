<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';

require_once 'include/StoreCatalogStatusCellRenderer.php';

/**
 * Index page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCatalogIndex extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Catalog/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		// set a default order on the table view
		$index_view = $this->ui->getWidget('index_view');
		$index_view->getColumn('title')->setDirection(
			SwatTableViewOrderableColumn::ORDER_BY_DIR_ASCENDING);
	}

	// }}}

	// process phase

	// build phase
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = sprintf('select id, title, clone_of from Catalog order by %s',
			$this->getOrderByClause($view, 'title'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		$view = $this->ui->getWidget('index_view');
		$view->getColumn('status')->getRendererByPosition()->db =
			$this->app->db;

		return $store;
	}

	// }}}
}

?>
