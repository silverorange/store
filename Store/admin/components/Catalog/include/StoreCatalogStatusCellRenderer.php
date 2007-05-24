<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Cell renderer that displays a summary of the status of a catalog
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogStatusCellRenderer extends SwatCellRenderer
{
	public $catalog;
	public $db;

	public function render()
	{
		$class_map = SwatDBClassMap::instance();
		$catalog_class = $class_map->resolveClass('StoreCatalog');

		$sql = 'select Region.title, available
			from Region
				left outer join CatalogRegionBinding on
					Region.id = region and catalog = %s
			order by Region.title';

		$sql = sprintf($sql, $this->db->quote($this->catalog, 'integer'));
		$catalog_statuses = SwatDB::query($this->db, $sql);

		foreach ($catalog_statuses as $row) {
			echo SwatString::minimizeEntities($row->title);
			echo ': ';

			$status_constant = call_user_func(array($catalog_class,
				'getStatusConstant'), $row->available);

			echo call_user_func(array($catalog_class, 'getStatusTitle'),
				$status_constant);

			echo '<br />';
		}
	}
}

?>
