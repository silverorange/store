<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatCellRenderer.php';

/**
 * Cell renderer that displays regions for which a catalog is enabled
 *
 * @package   Store
 * @copyright 2005-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogStatusCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $catalog;

	/**
	 * @var MDB2_Driver_Common
	 */
	public $db;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();
		$this->addStyleSheet(
			'packages/swat/styles/swat-null-text-cell-renderer.css',
			Swat::PACKAGE_ID);

	}

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		parent::render();

		$sql = sprintf('select Region.title
			from Region where id in
				(select region from CatalogRegionBinding where catalog = %s)
			order by Region.title',
			$this->db->quote($this->catalog, 'integer'));

		$regions = SwatDB::query($this->db, $sql);

		if (count($regions) > 0) {
			$region_titles = array();
			foreach ($regions as $region) {
				$region_titles[] =
					SwatString::minimizeEntities($region->title);
			}

			echo SwatString::toList($region_titles);
		} else {
			$span_tag = new SwatHtmLTag('span');
			$span_tag->class = 'swat-null-text-cell-renderer';
			$span_tag->setContent(Store::_('<none>'));
			$span_tag->display();
		}
	}

	// }}}
}

?>
