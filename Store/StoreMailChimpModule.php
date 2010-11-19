<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/SiteApplicationModule.php';
require_once 'Site/SiteApplicationModuleDependency.php';
require_once 'Store/dataobjects/StoreOrder.php';
require_once 'Store/dataobjects/StoreMailChimpOrder.php';

/**
 * Web application module for handling MailChimp order tracking.
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreMailChimpOrder
 */
class StoreMailChimpModule extends SiteApplicationModule
{
	// {{{ public function init()

	/**
	 * Initializes this module
	 *
	 * Checks to see if MailChimp get vars are set and if they are sets the
	 * appropriate cookies on the user. The cookies will expire in 30 days.
	 */
	public function init()
	{
		$cookie = $this->app->cookie;
		$expiry = strtotime('30 days');

		$email_id    = SiteApplication::initVar('mc_eid', '');
		$campaign_id = SiteApplication::initVar('mc_cid', '');

		// Save the campaign id and email id or just the email id
		if ($email_id != '' && $campaign_id != '') {
			$cookie->setCookie('mc_eid',    $email_id, $expiry);
			$cookie->setCookie('mc_cid', $campaign_id, $expiry);
		} else if ($email_id != '') {
			$cookie->setCookie('mc_eid', $email_id, $expiry);

			// Make sure we don't falsely report a campaign
			$cookie->removeCookie('mc_cid');
		}
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();

		$depends[] = new SiteApplicationModuleDependency('SiteCookieModule');
		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');

		return $depends;
	}

	// }}}
	// {{{ public function queueOrder()

	/**
	 * Adds a store order to the MailChimpOrderQueue
	 *
	 * If the mc_eid cookie is set then the following order, along with the
	 * mc_eid and mc_cid, is saved to the MailChimpOrderQueue.
	 *
	 * @param StoreOrder $order the order to save.
	 */
	public function queueOrder(StoreOrder $order)
	{
		$cookie = $this->app->cookie;

		$class = SwatDBClassMap::get('StoreMailChimpOrder');
		$chimp_order = new $class();
		$chimp_order->setDatabase($this->app->db);

		if (isset($cookie->mc_eid) && isset($cookie->mc_cid)) {
			$chimp_order->email_id    = $cookie->mc_eid;
			$chimp_order->campaign_id = $cookie->mc_cid;
			$chimp_order->ordernum    = $order->id;

			$chimp_order->save();
		} else if (isset($cookie->mc_eid)) {
			$chimp_order->email_id = $cookie->mc_eid;
			$chimp_order->ordernum = $order->id;

			$chimp_order->save();
		}
	}

	// }}}
}

?>
