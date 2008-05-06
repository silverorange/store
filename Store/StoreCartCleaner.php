<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Store/Store.php';
require_once 'Store/StoreCommandLineConfigModule.php';
require_once 'SwatDB/SwatDB.php';

class StoreCartCleaner extends SiteCommandLineApplication
{
	// {{{ class constants

	/**
	 * Verbosity level for showing nothing.
	 */
	const VERBOSITY_NONE = 0;

	/**
	 * Verbosity level for showing errors.
	 */
	const VERBOSITY_ERRORS = 1;

	/**
	 * Verbosity level for showing normal messages.
	 */
	const VERBOSITY_MESSAGES = 2;

	/**
	 * Verbosity level for showing all output.
	 */
	const VERBOSITY_ALL = 3;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $config_filename, $title, $documentation)
	{
		parent::__construct($id, $config_filename, $title, $documentation);

		$verbosity = new SiteCommandLineArgument(array('-v', '--verbose'),
			'setVerbosity', 'Sets the level of verbosity of the cleaner. '.
			'Pass 0 to turn off all output.');

		$verbosity->addParameter('integer',
			'--verbose expects a level between 0 and 3.',
			self::VERBOSITY_ALL);

		$this->addCommandLineArgument($verbosity);
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->session_save_path = session_save_path();
		$this->initModules();
		$this->db->loadModule('Datatype', null, true);
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->init();
		$this->parseCommandLineArguments();

		if (strpos($this->session_save_path, ';') !== false) {
			$this->output("Cannot automatically clean cart entries " .
				"when using multiple levels of session files. See " .
				"session.save_path documentation.\n",
				self::VERBOSITY_ERRORS);

			exit(1);
		}

		if (ini_get('session.save_handler') !== 'files') {
			$this->output("Cannot automatically clean cart entries " .
				"when not using the files session backend. See " .
				"session.save_handler documentation.\n",
				self::VERBOSITY_ERRORS);

			exit(1);
		}

		$this->output("Finding expired sessions:\n", self::VERBOSITY_MESSAGES);
		$expired_sessions = array();
		foreach ($this->getSessionIds() as $session_id) {
			if (!$this->validateSessionId($session_id)) {
				$this->output("=> session {$session_id} is expired\n",
					self::VERBOSITY_ALL);

				$expired_sessions[] = $session_id;
			}
		}
		$this->output(sprintf("Found %s expired sessions.\n\n",
			count($expired_sessions)), self::VERBOSITY_MESSAGES);

		$expired_sessions_sql =
			$this->db->datatype->implodeArray($expired_sessions, 'text');

		$sql = sprintf('select count(id) from CartEntry
			where sessionid in (%s)',
			$expired_sessions_sql);

		$total = SwatDB::queryOne($this->db, $sql);
		$this->output(sprintf("Deleting %s expired cart entries ... ",
			$total), self::VERBOSITY_MESSAGES);

		$sql = sprintf('delete from CartEntry
			where sessionid in (%s)',
			$expired_sessions_sql);

		SwatDB::exec($this->db, $sql);

		$this->output("done\n", self::VERBOSITY_MESSAGES);
	}

	// }}}
	// {{{ protected function getSessionIds()

	protected function getSessionIds()
	{
		$session_ids = array();
		$sql = 'select distinct sessionid from CartEntry
			where sessionid is not null';

		$entries = SwatDB::query($this->db, $sql);
		foreach ($entries as $entry)
			$session_ids[] = $entry->sessionid;

		return $session_ids;
	}

	// }}}
	// {{{ protected function validateSessionId()

	protected function validateSessionId($session_id)
	{
		$valid = true;
		if (!file_exists($this->session_save_path.'sess_'.$session_id))
			$valid = false;

		return $valid;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'StoreCommandLineConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
		parent::addConfigDefinitions($config);
		$config->addDefinitions(Store::getConfigDefinitions());
	}

	// }}}
}

?>
