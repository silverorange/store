<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Store/StoreQuickOrderItemView.php';
require_once 'Store/StoreClassMap.php';

/**
 * Handles XML-RPC requests from the quick order page
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreQuickOrderServer extends SiteXMLRPCServer
{
	// {{{ public function init()

	/**
	 * @xmlrpc.hidden
	 */
	public function init()
	{
		if (!isset($GLOBALS['HTTP_RAW_POST_DATA']))
			throw new SiteException('Page not found.');
	}

	// }}}
	// {{{ public function getItemDescription()

	/**
	 * Returns the XHTML required to display a textual description of the item
	 *
	 * @param string $sku the item number of the item descriptions to get.
	 *                     In Veseys, multiple items may have the same item
	 *                     number.
	 * @param string $replicator_id the id to be appended to the widget id
	 *                                   returned by this procedure.
	 * @param integer $sequence the sequence id of this request to prevent
	 *                           race conditions.
	 *
	 * @return string the XHTML required to display an item description.
	 */
	public function getItemDescription($sku, $replicator_id, $sequence)
	{
		$view = new StoreQuickOrderItemView('item_'.$replicator_id);
		$view->db = $this->app->db;
		$view->region = $this->app->getRegion();
		$view->sku = $sku;
		$view->init();
		$view->display();

		$obj = array();
		$obj['description'] = ob_get_clean();
		$obj['sequence'] = $sequence;

		return $obj;
	}

	// }}}
}

?>
