<?php

/**
 * Index page for Features.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeatureIndex extends AdminIndex
{
    // init phase

    protected function initInternal()
    {
        $this->ui->loadFromXML(__DIR__ . '/index.xml');
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        $locale = SwatI18NLocale::get();
        $num = count($view->checked_items);
        $message = null;

        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('Feature/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;

            case 'enable':
                SwatDB::updateColumn(
                    $this->app->db,
                    'Feature',
                    'boolean:enabled',
                    true,
                    'id',
                    $view->getSelection()
                );

                if (isset($this->app->memcache)) {
                    $this->app->memcache->flushNs('StoreFeature');
                }

                $message = new SwatMessage(
                    sprintf(
                        Store::ngettext(
                            'One feature has been enabled.',
                            '%s features have been enabled.',
                            $num
                        ),
                        $locale->formatNumber($num)
                    )
                );

                break;

            case 'disable':
                SwatDB::updateColumn(
                    $this->app->db,
                    'Feature',
                    'boolean:enabled',
                    false,
                    'id',
                    $view->getSelection()
                );

                if (isset($this->app->memcache)) {
                    $this->app->memcache->flushNs('StoreFeature');
                }

                $message = new SwatMessage(
                    sprintf(
                        Store::ngettext(
                            'One feature has been disabled.',
                            '%s features have been disabled.',
                            $num
                        ),
                        $locale->formatNumber($num)
                    )
                );

                break;
        }

        if ($message !== null) {
            $this->app->messages->add($message);
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $view = $this->ui->getWidget('index_view');

        if ($view->hasGroup('instance')) {
            $view->getGroup('instance')->visible =
                $this->app->isMultipleInstanceAdmin();
        }

        if ($view->hasColumn('region')) {
            $sql = 'select count(id) from Region';
            $region_count = SwatDB::queryOne($this->app->db, $sql);

            $view->getColumn('region')->visible = ($region_count > 1);
        }
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $instance_where = ($this->app->getInstanceId() === null) ?
            '1 = 1' :
            sprintf(
                'instance = %s',
                $this->app->db->quote($this->app->getInstanceId(), 'integer')
            );

        $sql = sprintf(
            'select Feature.*, Instance.title as instance_title
			from Feature
			left outer join instance on Instance.id = Feature.instance
			where %s
			order by instance_title nulls first, display_slot, priority,
				start_date',
            $instance_where
        );

        $wrapper = SwatDBClassMap::get(StoreFeatureWrapper::class);
        $features = SwatDB::query($this->app->db, $sql, $wrapper);

        $store = new SwatTableStore();
        $counts = [];

        foreach ($features as $feature) {
            $ds = new SwatDetailsStore($feature);
            $instance_id = $feature->getInternalValue('instance');
            if ($instance_id === null) {
                $instance_id = 0;
            }

            $ds->instance_id = $instance_id;

            if (!isset($counts[$instance_id][$ds->display_slot])) {
                $counts[$instance_id][$ds->display_slot] = 0;
            }

            $counts[$instance_id][$ds->display_slot]++;

            if ($feature->region === null) {
                $ds->region = 'All';
            } else {
                $ds->region = $feature->region->title;
            }

            $store->add($ds);
        }

        foreach ($store as $ds) {
            $ds->priority_sensitive =
                ($counts[$ds->instance_id][$ds->display_slot] > 1);
        }

        return $store;
    }
}
