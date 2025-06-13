<?php

/**
 * A recordset wrapper class for StoreFeature objects.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreFeature
 */
class StoreFeatureWrapper extends SwatDBRecordsetWrapper
{
    // {{{ public static function getFeatures()

    public static function getFeatures($app)
    {
        $features = false;
        $region = $app->getRegion();
        $instance = $app->getInstanceId();
        $key = 'StoreFeatureWrapper.getFeatures.' . $region->id;

        if ($instance !== null) {
            $key .= '.' . $instance;
        }

        // easter egg for testing, if warp is set, don't bother checking
        // memcache for features, and check each loaded
        if (isset($_GET['warp'])) {
            $date = trim($_GET['warp']);
            $date = new SwatDate($date);
            $date->setTZById($app->config->date->time_zone);
        } else {
            $features = $app->getCacheValue($key, 'StoreFeature');
        }

        if ($features === false) {
            $date = new SwatDate();
            $date->toUTC();

            $features = [];

            $sql = 'select * from Feature
				where enabled = %s
					and (region is null or region = %s)
					and (start_date is null or start_date <= %s)
					and %s
				order by display_slot, start_date desc';

            $sql = sprintf(
                $sql,
                $app->db->quote(true, 'boolean'),
                $app->db->quote($region->id, 'integer'),
                $app->db->quote($date, 'date'),
                ($instance === null) ? '1 = 1' : sprintf(
                    'instance = %s',
                    $app->db->quote($instance, 'integer')
                )
            );

            $all_features = SwatDB::query(
                $app->db,
                $sql,
                'StoreFeatureWrapper'
            );

            $expiry = 0;
            foreach ($all_features as $feature) {
                if ($feature->isActive($date)) {
                    $features[$feature->display_slot] = $feature;

                    // set expiry to the earliest feature expiry.
                    if ($feature->end_date !== null) {
                        $end_date = $feature->end_date->getTimestamp();
                        if ($expiry == 0 || $end_date < $expiry) {
                            $expiry = $end_date;
                        }
                    }
                }
            }

            $app->addCacheValue($features, $key, 'StoreFeature', $expiry);
        }

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
