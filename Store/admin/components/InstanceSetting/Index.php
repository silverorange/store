<?php

/**
 * @package   Store
 * @copyright 2009-2016 silverorange
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
