<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreFeature.php';

/**
 * A recordset wrapper class for StoreFeature objects
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreFeature
 */
class StoreFeatureWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public static function getFeatures()

	public static function getFeatures($app)
	{
		$date = null;

		// easter egg for testing
		if (isset($_GET['warp'])) {
			$date = trim($_GET['warp']);
			$date = new SwatDate($date);
			$date->setTZbyID($app->config->date->time_zone);
		}

		$key = 'StoreFeatureWrapper.getFeatures.'.$date;
		$features = $app->getCacheValue($key, 'product');
		if ($features !== false)
			return $features;

		$features = array();

		$sql = 'select * from Feature where enabled = true
			and (region is null or region = %s)
			order by display_slot, start_date desc';

		$sql = sprintf($sql,
			$app->db->quote($app->getRegion()->id, 'integer'));

		$all_features = SwatDB::query($app->db, $sql,
			'StoreFeatureWrapper');

		foreach ($all_features as $feature) {
			if ($feature->isActive($date)) {
				$features[$feature->display_slot] = $feature;
			}
		}

		$app->addCacheValue($features, $key, 'product');

		return $features;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreFeature');
	}

	// }}}
}

?>
