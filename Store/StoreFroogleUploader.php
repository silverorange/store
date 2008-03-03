<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Store/Store.php';
require_once 'Store/StoreFroogleGenerator.php';
require_once 'Store/StoreCommandLineConfigModule.php';
require_once 'VanBourgondien/VanBourgondienCommandLineApplication.php';

/**
 * Application to upload Froogle files to Google
 *
 * @package   Store
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreFroogleUploader extends SiteCommandLineApplication
{
	// {{{ private property

	private $path;

	// }}}
	// {{{ class constants

	/**
	 * Verbosity level for showing nothing.
	 */
	const VERBOSITY_NONE   = 0;

	/**
	 * Verbosity level for showing errors.
	 */
	const VERBOSITY_ERRORS = 1;

	/**
	 * Verbosity level for all messages.
	 */
	const VERBOSITY_ALL    = 2;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$verbosity = new SiteCommandLineArgument(array('-v', '--verbose'),
			'setVerbosity', 'Sets the level of verbosity of the uploader. '.
			'Pass 0 to turn off all output.');

		$verbosity->addParameter('integer',
			'--verbose expects a level between 0 and 2.',
			self::VERBOSITY_ALL);

		$this->addCommandLineArgument($verbosity);
	}

	// }}}
	// {{{ public function setPath()

	public function setPath($path)
	{
		$this->path = $path;
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		parent::run();

		$filename = $this->config->froogle->filename;

		$generator = $this->getGenerator();

		$this->output(sprintf(
			Store::_('Generating Froogle feed for %s ... ')."\n",
			$this->config->site->title), self::VERBOSITY_ALL);

 		$xml = $generator->generate();
		$file = fopen($this->path.$filename, 'w');
 		fwrite($file, $xml);
		fclose($file);

		$this->output(Store::_('done')."\n\n", self::VERBOSITY_ALL);

		$this->output(Store::_('Logging into Froogle FTP ... '),
			self::VERBOSITY_ALL);

		$ftp_connection = ftp_connect($this->config->froogle->server);
		$login_result = ftp_login($ftp_connection,
			$this->config->froogle->username,
			$this->config->froogle->password);

		if ($ftp_connection == null || $login_result == null) {
			$this->terminate(Store::_('failed to log in')."\n\n",
				self::VERBOSITY_ERRORS);
		} else {
			$this->output(Store::_('done')."\n\n", self::VERBOSITY_ALL);
		}

		$this->output(Store::_('Uploading Froogle file ... '),
			self::VERBOSITY_ALL);

		$upload_result = ftp_put($ftp_connection, $filename,
			$this->path.$filename, FTP_BINARY);

		if (!$upload_result) {
			$this->terminate(Store::_('failed uploading')."\n",
				self::VERBOSITY_ERRORS);
		} else {
			$this->output(Store::_('done')."\n\n", self::VERBOSITY_ALL);
		}

		ftp_close($ftp_connection);
		$this->output(Store::('All done.')."\n", self::VERBOSITY_ALL);
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
	// {{{ abstract protected function getGenerator()

	abstract protected function getGenerator();

	// }}}
}

?>
