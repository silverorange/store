<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/dataobjects/StorePriceRange.php';
require_once 'Store/StoreSearchPanel.php';

/**
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreSearchPanelServer extends SiteXMLRPCServer
{
	// {{{ public function getContent()

	/**
	 * Returns the XHTML required to display the search panel for the
	 * Van Bourgondien advanced search
	 *
	 * @param string $query_string the query string containg the state of the
	 *                              search panel.
	 * @param string $uri the URI of the page making the request.
	 *
	 * @return string the XHTML required to display the search panel.
	 */
	public function getContent($query_string, $uri)
	{
		$query_string_exp = explode('&', $query_string);
		$args = array();
		foreach ($query_string_exp as $parameter) {
			if (strpos($parameter, '=')) {
				list($key, $value) = explode('=', $parameter, 2);
			} else {
				$key = $parameter;
				$value = null;
			}

			$key = urldecode($key);
			$value = urldecode($value);

			$regs = array();
			if (preg_match('/^(.+)\[(.*)\]$/', $key, $regs)) {
				$key = $regs[1];
				$array_key = ($regs[2] == '') ? null : $regs[2];
				if (!isset($args[$key]))
					$args[$key] = array();

				if ($array_key === null) {
					$args[$key][] = $value;
				} else {
					$args[$key][$array_key] = $value;
				}
			} else {
				$args[$key] = $value;
			}
		}

		foreach ($args as $key => $value) {
			$_GET[$key] = $value;
		}

		// parse uri components from special seach pages into GET vars
		$uri = substr($uri, strlen($this->app->getBaseHref()));
		$uri_exp = explode('/', $uri);
		if (count($uri_exp) == 3) {
			$key = $uri_exp[1];
			$value = $uri_exp[2];
			$_GET[$key] = $value;
		}

		ob_start();

		$panel = $this->getPanel();
		$panel->init();
		$panel->process();
		$this->setValues($panel);
		$panel->display();

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function getPanel()

	protected function getPanel()
	{
		return new StoreSearchPanel(
			$this->app->db, $this->app->getRegion());
	}

	// }}}
	// {{{ protected function setValues()

	protected function setValues(StoreSearchPanel $panel)
	{
		$panel->setPriceRange($this->getPriceRange());
		$panel->setCategory($this->getCategory());
	}

	// }}}
	// {{{ protected function getPriceRange()

	/**
	 * @xmlrpc.hidden
	 */
	protected function getPriceRange()
	{
		$range = null;

		if (isset($_GET['price'])) {
			$range = new StorePriceRange($_GET['price']);
			$range->normalize();
		}

		return $range;
	}

	// }}}
	// {{{ protected function getCategory()

	/**
	 * @xmlrpc.hidden
	 */
	protected function getCategory()
	{
		$category = null;

		if (isset($_GET['category'])) {
			$sql = 'select id, shortname, title from Category
				where id = findCategory(%s) and id in
					(select category from VisibleCategoryView
					where region = %s or region is null)';

			$sql = sprintf($sql,
				$this->app->db->quote($_GET['category'], 'text'),
				$this->app->db->quote($this->app->getRegion()->id, 'integer'));

			$category = SwatDB::query($this->app->db, $sql,
				'StoreCategoryWrapper')->getFirst();
		}

		return $category;
	}

	// }}}
}

?>
