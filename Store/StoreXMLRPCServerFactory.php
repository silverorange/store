<?php

require_once 'Store/exceptions/StoreNotFoundException.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'Store/StorePageFactory.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreXMLRPCServerFactory extends StorePageFactory
{
	// {{{ public function resolvePage()

	public function resolvePage($app, $source)
	{
		$layout = $this->resolveLayout($app, $source);
		$map = $this->getPageMap();

		if (isset($map[$source])) {
			$class = $map[$source];
			$params = array($app, $layout);
			$page = $this->instantiatePage($class, $params);
			return $page;
		}

		throw new StoreNotFoundException();
	}

	// }}}
	// {{{ protected function resolveLayout()

	protected function resolveLayout($app, $source)
	{
		return new SiteXMLRPCServerLayout($app);
	}

	// }}}
}

?>
