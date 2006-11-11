<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminiPage.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Store/admin/components/Catalog/include/'.
	'StoreCatalogStatusCellRenderer.php';


/**
 * Details page for Catalogs
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogDetails extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Catalog/details.xml';

	// }}}
	// {{{ private properties

	private $id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);
		$this->id = SiteApplication::initVar('id');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

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

		$component_details = $this->ui->getWidget('component_details');
		$component_details->data = $row;

		if ($row->parent_id !== null)
			$component_details->getField('parent')->visible = true;

		$frame = $this->ui->getWidget('frame');
		$frame->subtitle = $row->title;

		$this->ui->getWidget('toolbar')->setToolLinkValues($this->id);

		// see if the Catalog is a clone
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

		// get number of products
		$sql = 'select count(id) from Product where catalog = %s';
		$row->num_products = SwatDB::queryOne($this->app->db, sprintf($sql,
			$this->app->db->quote($this->id, 'integer')));

		// check to see if Catalog is enabled
		$sql = 'select count(region) from CatalogRegionBinding 
			where catalog = %s';
		$enabled = (SwatDB::queryOne($this->app->db, sprintf($sql,
			$this->app->db->quote($this->id, 'integer'))) > 0);

		// sensitize the delete button
		if (!$enabled || $row->num_products == 0)
			$this->ui->getWidget('delete')->sensitive = true;

		// sensitize the clone button 
		if ($row->clone_id === null && $row->parent_id === null)
			$this->ui->getWidget('clone')->sensitive = true;

		// setup status renderer
		$status_renderer = 
			$component_details->getField('status')->getRendererByPosition();

		$status_renderer->db = $this->app->db;
		$status_renderer->regions = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');

		$this->navbar->createEntry($row->title);
		$this->buildMessages();
	}

	// }}}
}

?>
