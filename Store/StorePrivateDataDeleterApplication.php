<?php

/**
 * Framework for a command line application to remove personal data.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property StoreCommandLineConfigModule $config
 */
class StorePrivateDataDeleterApplication extends SitePrivateDataDeleterApplication
{
    /**
     * Gets the list of modules to load for this search indexer.
     *
     * @return array the list of modules to load for this application
     *
     * @see SiteApplication::getDefaultModuleList()
     */
    protected function getDefaultModuleList()
    {
        return array_merge(
            parent::getDefaultModuleList(),
            [
                'config' => StoreCommandLineConfigModule::class,
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
}
