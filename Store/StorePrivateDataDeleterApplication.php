<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Store/StorePrivateDataDeleter.php';

/**
 * Framework for a command line application to remove personal data.
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePrivateDataDeleterApplication extends SiteApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database object
	 *
	 * @var MDB2_Driver
	 */
	public $db;

	// }}}
	// {{{ protected properties

	/**
	 * @var array of StorePrivateDataDeleter
	 */
	protected $deleters = array();

	/**
	 * @var boolean 
	 */
	protected $debug = false;

	/**
	 * @var boolean 
	 */
	protected $dry_run = false;

	// }}}
	// {{{ public function initModules()

	/**
	 * Initializes the modules of this application and sets up the database
	 * convenience reference
	 */
	public function initModules()
	{
		parent::initModules();
		$this->db = $this->database->getConnection();
		$this->db->loadModule('Datatype', null, true);
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		if ($this->dry_run)
			$this->debug("Dry Run: ".
				"No data will actually be deleted.\n");

		foreach ($this->deleters as $deleter)
			$deleter->run();

		if ($this->dry_run)
			$this->debug("\nDry Run: ".
				"No data was actually deleted.\n\n");
	}

	// }}}
	// {{{ public function setDebug()

	public function setDebug($debug)
	{
		$this->debug = (boolean)$debug;
	}

	// }}}
	// {{{ public function setDryRun()

	public function setDryRun($dry_run)
	{
		$this->dry_run = (boolean)$dry_run;
	}

	// }}}
	// {{{ public function isDryRun()

	public function isDryRun()
	{
		return $this->dry_run;
	}

	// }}}
	// {{{ public function addDeleter()

	public function addDeleter(StorePrivateDataDeleter $deleter)
	{
		$deleter->app = $this;
		$this->deleters[] = $deleter;
	}

	// }}}
	// {{{ public function debug()

	public function debug($string)
	{
		if ($this->debug)
			echo $string;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
}

?>
