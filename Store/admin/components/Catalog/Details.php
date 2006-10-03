<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';

require_once 'include/StoreCatalogStatusCellRenderer.php';

/**
 * Details page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCatalogDetails extends AdminPage
{
	// {{{ private properties

	private $id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/details.xml');
		$this->id = SiteApplication::initVar('id');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$component_details = $this->ui->getWidget('component_details');

		$sql = 'select Catalog1.id, Catalog1.title,
					Catalog2.title as parent_title,
					Catalog1.clone_of as parent_id
				from Catalog as Catalog1
					left outer join Catalog as Catalog2
						on Catalog1.clone_of = Catalog2.id
				where Catalog1.id = %s';

		$row = SwatDB::queryRow($this->app->db, sprintf($sql,
			$this->app->db->quote($this->id, 'integer')));

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_("%s with id ‘%s’ not found."),
				Store::_('Catalog'), $this->id));

		// get number of products
		$sql = 'select count(id) from Product where catalog = %s';
		$row->num_products = SwatDB::queryOne($this->app->db, sprintf($sql,
			$this->app->db->quote($this->id, 'integer')));

		$sql = 'select id, title from Catalog where clone_of = %s';
		$clone = SwatDB::queryRow($this->app->db, sprintf($sql,
			$this->app->db->quote($this->id, 'integer')));

		if ($clone !== null) {
			$component_details->getField('clone')->visible = true;
			$row->clone_title = $clone->title;
			$row->clone_id = $clone->id;
		} else {
			$row->clone_title = null;
			$row->clone_id = null;
		}

		if ($row->parent_id !== null)
			$component_details->getField('parent')->visible = true;

		$component_details->data = $row;

		$sql = 'select count(region) from CatalogRegionBinding 
			where catalog = %s';
		$enabled = (SwatDB::queryOne($this->app->db, sprintf($sql,
			$this->app->db->quote($this->id, 'integer'))) > 0);

		// sensitize the delete button
		if (!$enabled || $row->num_products == 0)
			$this->ui->getWidget('delete')->sensitive = true;

		$frame = $this->ui->getWidget('frame');
		$frame->title = Store::_('Catalog');
		$frame->subtitle = $row->title;

		$this->ui->getWidget('toolbar')->setToolLinkValues($this->id);

		$status_renderer =
			$component_details->getField('status')->getRendererByPosition();

		$status_renderer->db = $this->app->db;
		$status_renderer->regions = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');

		// sensitize the clone button 
		if ($row->clone_id === null && $row->parent_id === null)
			$this->ui->getWidget('clone')->sensitive = true;

		$this->navbar->createEntry($row->title);
		$this->buildMessages();
	}

	// }}}
}

?>
