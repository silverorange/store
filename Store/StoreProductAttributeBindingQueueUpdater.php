<?php

/**
 * Product Attribute Queue Updater.
 *
 * Checks for queued product attributes changes, and updates if their action
 * date has passed. Deletes always run before additions.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductAttributeBindingQueueUpdater extends SiteCommandLineApplication
{
    public function run()
    {
        $this->initModules();
        $this->parseCommandLineArguments();

        $this->lock();

        $now = new SwatDate();
        $now->setTZ($this->default_time_zone);
        $now->toUTC();

        $this->debug(Store::_('Updating Product Attributes') . "\n\n", true);

        $distinct_attributes = $this->getDistinctAttributes($now);
        $attributes_removed = false;
        $attributes_added = false;

        $this->debug(Store::_('Deleting:') . "\n--------------------\n");
        foreach ($distinct_attributes as $attribute) {
            if ($this->removeAttributes($now, $attribute->attribute)) {
                $attributes_removed = true;
            }
        }
        $this->debug(Store::_('Done deleting attributes.') . "\n\n");

        $this->debug(Store::_('Adding:') . "\n--------------------\n");
        foreach ($distinct_attributes as $attribute) {
            if ($this->addAttributes($now, $attribute->attribute)) {
                $attributes_added = true;
            }
        }

        $this->debug(Store::_('Done adding attributes.') . "\n\n");

        $this->debug(Store::_('Clearing Caches:') . "\n--------------------\n");
        $this->clearQueue($now);
        if (($attributes_removed == true || $attributes_added == true)
            && isset($this->memcache)) {
            $this->flushMemcache();
        }
        $this->debug(Store::_('Done clearing caches.') . "\n\n");

        $this->debug(Store::_('All Done.') . "\n", true);
        $this->unlock();
    }

    protected function removeAttributes(SwatDate $current_date, $attribute)
    {
        $flush_memcache = false;
        $sql = 'delete from ProductAttributeBinding
			where product in (%s) and attribute in (%s)';

        $sql = sprintf(
            $sql,
            $this->getInSql($current_date, 'product', 'remove', $attribute),
            $this->getInSql($current_date, 'attribute', 'remove', $attribute)
        );

        $count = SwatDB::exec($this->db, $sql);

        if ($count > 0) {
            $flush_memcache = true;
            $this->debug(sprintf(
                Store::_('%s products had attribute %s removed.'),
                $count,
                $attribute
            ) . "\n");
        } else {
            $this->debug(sprintf(
                Store::_('No products had attribute %s removed.'),
                $attribute
            ) . "\n");
        }

        return $flush_memcache;
    }

    protected function addAttributes(SwatDate $current_date, $attribute)
    {
        $flush_memcache = false;
        $sql = 'delete from ProductAttributeBinding
			where product in (%s) and attribute in (%s)';

        $sql = sprintf(
            $sql,
            $this->getInSql($current_date, 'product', 'add', $attribute),
            $this->getInSql($current_date, 'attribute', 'add', $attribute)
        );

        $delete_count = SwatDB::exec($this->db, $sql);

        $sql = sprintf(
            'insert into ProductAttributeBinding
			(product, attribute)
			select Product.id, Attribute.id
			from Product cross join Attribute
			where Product.id in (%s) and Attribute.id in (%s)',
            $this->getInSql($current_date, 'product', 'add', $attribute),
            $this->getInSql($current_date, 'attribute', 'add', $attribute)
        );

        $add_count = SwatDB::exec($this->db, $sql);

        if ($add_count != $delete_count) {
            $flush_memcache = true;
            $this->debug(sprintf(
                Store::_('%s products had attribute %s added.'),
                $add_count,
                $attribute
            ) . "\n");
        } else {
            $this->debug(sprintf(
                Store::_('No products had attribute %s added.'),
                $attribute
            ) . "\n");
        }

        return $flush_memcache;
    }

    // helper methods

    protected function getDistinctAttributes(SwatDate $current_date)
    {
        $sql = 'select distinct(attribute) from ProductAttributeBindingQueue
			where action_date <= %s';

        $sql = sprintf(
            $sql,
            $this->db->quote($current_date, 'date')
        );

        return SwatDB::query($this->db, $sql);
    }

    protected function getInSql(
        SwatDate $current_date,
        $field_title,
        $action,
        $attribute
    ) {
        $sql = 'select %s from ProductAttributeBindingQueue
			where action_date <= %s and queue_action = %s and attribute = %s';

        return sprintf(
            $sql,
            $field_title,
            $this->db->quote($current_date, 'date'),
            $this->db->quote($action, 'text'),
            $this->db->quote($attribute, 'integer')
        );
    }

    protected function flushMemcache()
    {
        $instances = SwatDB::queryColumn(
            $this->db,
            'Instance',
            'text:shortname'
        );

        if (count($instances) == 0) {
            $this->memcache->flushNs('product');
            $this->debug('Memcache Flushed' . "\n");
        } else {
            foreach ($instances as $shortname) {
                $this->memcache->setInstance($shortname);
                $this->memcache->flushNs('product');
                $this->debug('Memcache Flushed (' . $shortname . ')' . "\n");
            }
        }
    }

    protected function clearQueue(SwatDate $current_date)
    {
        $sql = 'delete from ProductAttributeBindingQueue
			where action_date <= %s';

        $sql = sprintf(
            $sql,
            $this->db->quote($current_date, 'date')
        );

        SwatDB::exec($this->db, $sql);

        $this->debug(Store::_('Cache table cleared.') . "\n");
    }

    // boilerplate

    protected function getDefaultModuleList()
    {
        return array_merge(
            parent::getDefaultModuleList(),
            [
                'config'   => StoreCommandLineConfigModule::class,
                'database' => SiteDatabaseModule::class,
            ]
        );
    }

    /**
     * Adds configuration definitions to the config module of this application.
     *
     * @param SiteConfigModule $config the config module of this application to
     *                                 which to add the config definitions
     */
    protected function addConfigDefinitions(SiteConfigModule $config)
    {
        parent::addConfigDefinitions($config);
        $config->addDefinitions(Store::getConfigDefinitions());
    }

    /**
     * Configures modules of this application before they are initialized.
     *
     * @param SiteConfigModule $config the config module of this application to
     *                                 use for configuration other modules
     */
    protected function configure(SiteConfigModule $config)
    {
        parent::configure($config);

        if ($this->hasModule('SiteMemcacheModule')) {
            $this->memcache->server = $config->memcache->server;
            $this->memcache->app_ns = $config->memcache->app_ns;
        }
    }
}
