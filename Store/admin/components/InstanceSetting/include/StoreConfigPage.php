<?php

require_once 'Site/admin/components/InstanceSetting/include/SiteAbstractConfigPage.php';

/**
 * Config page used for displaying and saving config settings for the Store
 * package.
 *
 * @package   Store
 * @copyright 2009-2010 silverorange
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
		return array(
			'froogle' => array(
				'filename',
				'server',
				'username',
				'password',
			),
			'strikeiron' => array(
				'verify_address_usa_key',
				'verify_address_canada_key',
			),
		);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return dirname(__FILE__).'/config-page.xml';
	}

	// }}}
}

?>
