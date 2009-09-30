<?php

require_once 'Site/admin/components/InstanceSetting/Index.php';
require_once dirname(__FILE__).'/include/StoreConfigPage.php';

/**
 * @package   Store
 * @copyright 2009 silverorange
 */
class StoreInstanceSettingIndex extends SiteInstanceSettingIndex
{
	// {{{ protected function initConfigPages()

	protected function initConfigPages()
	{
		parent::initConfigPages();
		$this->config_pages[] = new StoreConfigPage();
	}

	// }}}
}

?>
