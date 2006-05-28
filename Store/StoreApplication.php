<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteServerConfigModule.php';
require_once 'Store/StoreSessionModule.php';

/**
 * Web application class for a store
 *
 * @package Store
 * @copyright silverorange 2004
 */
abstract class StoreApplication extends SiteApplication
{
	// {{{ public properties

	public $db;

	// }}}
	// {{{ protected function initModules()

	protected function initModules()
	{
		parent::initModules();
		// set up convenience references
		$this->db = $this->database->getConnection();

		if ($this->session->isLoggedIn())
			$this->session->account->setDatabase($this->db);
	}

	// }}}
	// {{{ public function relocate()

	/**
	 * Relocates to another URI
	 *
	 * Appends the session identifier (SID) to the URL if necessary.
	 * Then call parent method Site::relocate to do the actual relocating.
	 * This function does not return and in fact calls the PHP exit()
	 * function just to be sure execution does not continue.
	 *
	 * @param string $uri the URI to relocate to.
	 * @param boolean $append_sid whether to append the SID to the URI
	 */
	public function relocate($uri, $append_sid = true)
	{
		if ($append_sid && $this->session->isActive()) {
			if (strpos($url, '?') === FALSE)
				$uri.= '?';
			else
				$uri.= '&';

			$uri.= sprintf('%s=%s', session_name(), session_id());
		}

		parent::relocate($uri);
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'session'  => 'StoreSessionModule',
			'database' => 'SiteDatabaseModule',
			'config'   => 'SiteServerConfigModule');
	}

	// }}}
}

?>
