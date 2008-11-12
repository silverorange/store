<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Store/Store.php';
require_once 'Store/StoreFroogleGenerator.php';
require_once 'Store/StoreCommandLineConfigModule.php';

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

	private $path    = '';
	private $upload  = true;
	private $display = false;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		// add display argument
		$display = new SiteCommandLineArgument(
			array('-d', '--display'), 'setDisplay',
			Store::_('Display the generated XML.'));

		$this->addCommandLineArgument($display);

		// add no-upload argument
		$no_upload = new SiteCommandLineArgument(
			array('-n', '--no-upload'), 'setNoUpload',
			Store::_('Do not upload the generated XML to Google.'));

		$this->addCommandLineArgument($no_upload);
	}

	// }}}
	// {{{ public function setPath()

	public function setPath($path)
	{
		$this->path = $path;
	}

	// }}}
	// {{{ public function setDisplay()

	public function setDisplay()
	{
		$this->display = true;
	}

	// }}}
	// {{{ public function setNoUpload()

	public function setNoUpload()
	{
		$this->upload = false;
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$filename = $this->config->froogle->filename;

		$generator = $this->getGenerator();

		$this->debug(sprintf(
			Store::_('Generating Froogle feed for %s')."\n",
			$this->config->site->title));

 		$xml = $generator->generate();
		$file = fopen($this->path.$filename, 'w');
 		fwrite($file, $xml);
		fclose($file);

		$this->debug(Store::_('done')."\n\n");

		if ($this->display) {
			$this->debug(Store::_('Generated XML:')."\n\n");

			echo $xml;

			$this->debug("\n\n");
		}

		if ($this->upload) {
			$this->debug(Store::_('Logging into Froogle FTP ... '));

			$ftp_connection = ftp_connect($this->config->froogle->server);

			if ($ftp_connection === false) {
				throw new SwatException('Unable to connect to FTP server: '.
					$this->config->froogle->server);
			}

			$login_result = ftp_login($ftp_connection,
				$this->config->froogle->username,
				$this->config->froogle->password);

			if ($ftp_connection == null || $login_result == null) {
				$this->terminate(Store::_('failed to log in')."\n\n");
			} else {
				$this->debug(Store::_('done')."\n\n");
			}

			$this->debug(Store::_('Uploading Froogle file ... '));

			$upload_result = ftp_put($ftp_connection, $filename,
				$this->path.$filename, FTP_BINARY);

			if (!$upload_result) {
				$this->terminate(Store::_('failed uploading')."\n");
			} else {
				$this->debug(Store::_('done')."\n\n");
			}

			ftp_close($ftp_connection);
		}

		$this->debug(Store::_('All done.')."\n");
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
