<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Cell renderer that displays a summary of the status of a catalog
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogStatusCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	public $catalog;
	public $db;

	// }}}
	// {{{ public function render()

	public function render()
	{
		$catalog_class = SwatDBClassMap::get('StoreCatalog');

		$sql = 'select Region.title, available
			from Region
				left outer join CatalogRegionBinding on
					Region.id = region and catalog = %s
			order by Region.title';

		$sql = sprintf($sql, $this->db->quote($this->catalog, 'integer'));
		$catalog_statuses = SwatDB::query($this->db, $sql);

		foreach ($catalog_statuses as $row) {
			$status = ($row->available === true) ?
				Site::_('Available') : Site::_('Unavailable');

			printf('%s: %s',
				SwatString::minimizeEntities($row->title),
				SwatString::minimizeEntities($status));

			echo '<br />';
		}
	}

	// }}}
}

?>
