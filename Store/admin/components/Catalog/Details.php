<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminPage.php';
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

	protected $id;

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

		$ds = $this->getDetailsStore();

		$component_details = $this->ui->getWidget('component_details');
		$component_details->data = $ds;

		if ($ds->parent_id !== null)
			$component_details->getField('parent')->visible = true;

		$frame = $this->ui->getWidget('frame');
		$frame->subtitle = $ds->title;

		$this->ui->getWidget('toolbar')->setToolLinkValues($this->id);

		// see if the Catalog is a clone
		$sql = 'select id, title from Catalog where clone_of = %s';
		$clone = SwatDB::queryRow($this->app->db,
			sprintf($sql, $this->app->db->quote($this->id, 'integer')));

		if ($clone !== null) {
			$component_details->getField('clone')->visible = true;
			$ds->clone_title = $clone->title;
			$ds->clone_id = $clone->id;
		} else {
			$ds->clone_title = null;
			$ds->clone_id = null;
		}

		// get number of products
		$sql = 'select count(id) from Product where catalog = %s';
		$ds->num_products = SwatDB::queryOne($this->app->db,
			sprintf($sql, $this->app->db->quote($this->id, 'integer')));

		// check to see if Catalog is enabled
		$sql = 'select count(region) from CatalogRegionBinding
			where catalog = %s';

		$enabled = (SwatDB::queryOne($this->app->db,
			sprintf($sql, $this->app->db->quote($this->id, 'integer'))) > 0);

		// sensitize the delete button
		if (!$enabled || $ds->num_products == 0)
			$this->ui->getWidget('delete_link')->sensitive = true;

		// sensitize the clone button
		if ($ds->clone_id === null && $ds->parent_id === null)
			$this->ui->getWidget('clone_link')->sensitive = true;

		// setup status renderer
		$status_renderer =
			$component_details->getField('status')->getRendererByPosition();

		$status_renderer->db = $this->app->db;
		$status_renderer->regions = SwatDB::getOptionArray($this->app->db,
			'Region', 'title', 'id', 'title');

		$this->navbar->createEntry($ds->title);
		$this->buildMessages();
	}

	// }}}
	// {{{ protected function getDetailsStore()

	protected function getDetailsStore()
	{
		$sql = 'select Catalog1.id, Catalog1.title,
					Catalog1.in_season,
					Catalog2.title as parent_title,
					Catalog1.clone_of as parent_id
				from Catalog as Catalog1
					left outer join Catalog as Catalog2
						on Catalog1.clone_of = Catalog2.id
				where Catalog1.id = %s';

		$row = SwatDB::queryRow($this->app->db,
			sprintf($sql, $this->app->db->quote($this->id, 'integer')));

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Store::_("%s with id ‘%s’ not found."),
				Store::_('Catalog'), $this->id));

		return $row;
	}

	// }}}
}

?>
