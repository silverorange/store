<?php

require_once 'Site/SiteXMLRPCServerFactory.php';

/**
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreXMLRPCServerFactory extends SiteXMLRPCServerFactory
{
	// {{{ public static function instance()

	public static function instance()
	{
		static $instance = null;

		if ($instance === null)
			$instance = new self();

		return $instance;
	}

	// }}}
	// {{{ protected function __construct()

	protected function __construct()
	{
		parent::__construct();

		// set location to load Store page classes from
		$this->class_map['Store'] = 'Store/pages';
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array(
			'quickorder' => 'StoreQuickOrderServer',
		);
	}

	// }}}
}

?>
