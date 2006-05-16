<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteServerConfigModule.php';
require_once 'Store/StoreSessionModule.php';
require_once 'Store/StoreCartModule.php';
require_once 'Store/exceptions/StoreNotFoundException.php';
require_once 'MDB2.php';

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

	public $exception_page_source = 'exception';

	// }}}
	// {{{ public function init()

	/**
	 * Initialize the application.
	 */
	public function init()
	{
		$this->initBaseHref(3);
		$this->initModules();

		// set up convenience references
		$this->db = $this->database->getConnection();

		try {
			$this->initPage();
		}
		catch (StoreException $e) {
			$this->replacePage($this->exception_page_source);
			$this->page->setException($e);
			$this->initPage();
		}
	}

	// }}}
	// {{{ public function run()

	/**
	 * Run the application.
	 */
	public function run()
	{
		try {
			$this->getPage()->process();
			$this->getPage()->build();
		}
		catch (StoreException $e) {
			$this->replacePage($this->exception_page_source);
			$this->page->setException($e);
			$this->page->build();
		}

		$this->getPage()->layout->display();
	}

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Get the page object.
	 * Uses the $_GET variables to decide which page subclass to instantiate.
	 * @return SitePage A subclass of SitePage is returned.
	 */
	public function resolvePage()
	{
		$source = self::initVar('source');
		$page = $this->instantiatePage($source);
		return $page;
	}

	// }}}
	// {{{ public function replacePage()

	/**
	 * Replace the page object
	 *
	 * This method can be used to load another page to replace the current 
	 * page. For example, this is used to load a confirmation page when 
	 * processing an admin index page.
	 */
	public function replacePage($source)
	{
		$new_page = $this->instantiatePage($source);
		$this->setPage($new_page);
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
