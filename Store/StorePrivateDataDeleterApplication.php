<?php

require_once 'Site/SitePrivateDataDeleterApplication.php';
require_once 'Store/Store.php';
require_once 'Store/StoreCommandLineConfigModule.php';
require_once 'Store/StorePrivateDataDeleter.php';

/**
 * Framework for a command line application to remove personal data.
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 */
class StorePrivateDataDeleterApplication extends
	SitePrivateDataDeleterApplication
{
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		$modules = parent::getDefaultModuleList();
		$modules['config'] = 'StoreCommandLineConfigModule';

		return $modules;
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
}

?>
