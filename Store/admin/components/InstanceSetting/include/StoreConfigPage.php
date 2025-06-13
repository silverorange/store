<?php

/**
 * Config page used for displaying and saving config settings for the Store
 * package.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreConfigPage extends SiteAbstractConfigPage
{
    // {{{ public function getPageTitle()

    public function getPageTitle()
    {
        return 'Store Settings';
    }

    // }}}
    // {{{ public function getConfigSettings()

    public function getConfigSettings()
    {
        return [
            'froogle' => [
                'filename',
                'server',
                'username',
                'password',
            ],
            'strikeiron' => [
                'verify_address_usa_key',
                'verify_address_canada_key',
            ],
        ];
    }

    // }}}
    // {{{ protected function getUiXml()

    protected function getUiXml()
    {
        return __DIR__ . '/config-page.xml';
    }

    // }}}
}
