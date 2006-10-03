<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StoreCatalog.php';
require_once 'Store/StoreClassMap.php';

/**
 * Cell renderer that displays a summary of the status of a catalog
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
class StoreCatalogStatusCellRenderer extends SwatCellRenderer
{
	public $catalog;
	public $db;

	public function render()
	{
		$class_map = StoreClassMap::instance();
		$catalog = $class_map->resolveClass('StoreCatalog');

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
			echo call_user_func(array($catalog, 'getStatusTitle'),
				$row->available);

			echo '<br />';
		}
	}
}

?>
