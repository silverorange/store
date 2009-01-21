<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Store/Store.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteMemcacheModule.php';
require_once 'Store/StoreCommandLineConfigModule.php';

/**
 * Cache table updater application
 *
 * This application checks the dirty cache table list and runs an SQL function
 * for each entry found in the dirty cache table list.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCacheTableUpdater extends SiteCommandLineApplication
{
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$this->debug(Store::_('Pass 1/2:')."\n\n", true);

		$this->updateCacheTables();

		// run twice to handle two-level dependencies
		$this->debug("\n".Store::_('Pass 2/2:')."\n\n", true);

		$this->updateCacheTables();
	}

	// }}}
	// {{{ protected function updateCacheTables()

	protected function updateCacheTables()
	{
		$sql = 'select shortname from CacheFlag where dirty = true';
		$tables = SwatDB::query($this->db, $sql);
		$total = count($tables);
		if ($total > 0) {
			$this->debug(sprintf(Store::_('Found %s dirty cache tables:')."\n",
				$total));

			foreach ($tables as $row) {
				$this->debug(sprintf('=> '.Store::_('updating %s ... '),
					$row->shortname));

				$update_function =
					$this->getCacheTableUpdateFunction($row->shortname);

				$sql = sprintf('select * from %s()', $update_function);
				SwatDB::exec($this->db, $sql);

				$this->debug(Store::_('done')."\n");
			}

			if (isset($this->memcache)) {
				$this->memcache->flushNs('product');
			}

			$this->debug(Store::_('Done.')."\n");
		} else {
			$this->debug(Store::_('No dirty cache tables found.')."\n");
		}
	}

	// }}}
	// {{{ protected function getCacheTableUpdateFunction()

	/**
	 * Gets the SQL function to run for a dirty cache table entry
	 *
	 * If the given table name does not correspond to a known cache table, this
	 * method displays an error message and terminates the application.
	 *
	 * @param string $table_name the name of the dirty cache table.
	 *
	 * @return string the SQL function to update (clean) the dirty cache table.
	 */
	protected function getCacheTableUpdateFunction($table_name)
	{
		switch ($table_name) {
		case 'CategoryVisibleProductCountByRegion':
			$update_function = 'updateCategoryVisibleProductCountByRegion';
			break;

		case 'VisibleProduct':
			$update_function = 'updateVisibleProduct';
			break;

		case 'CategoryVisibleItemCountByRegion':
			$update_function = 'updateCategoryVisibleItemCountByRegion';
			break;

		default:
			$this->terminate(sprintf(
				Store::_('Unknown dirty cache table %s.')."\n", $table_name));

			break;
		}

		return $update_function;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'StoreCommandLineConfigModule',
			'database' => 'SiteDatabaseModule',
			'memcache' => 'SiteMemcacheModule',
		);
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
		parent::addConfigDefinitions($config);
		$config->addDefinitions(Store::getConfigDefinitions());
	}

	// }}}
	// {{{ protected function configure()

	/**
	 * Configures modules of this application before they are initialized
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  use for configuration other modules.
	 */
	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		if ($this->hasModule('SiteMemcacheModule')) {
			$this->memcache->server = $config->memcache->server;
			$this->memcache->app_ns = $config->memcache->app_ns;
		}
	}

	// }}}

}

?>
