<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Store/Store.php';
require_once 'Store/StoreCommandLineConfigModule.php';
require_once 'Store/StoreBingShoppingGenerator.php';

/**
 * Application to upload Catalog Data files to Bing Shopping.
 *
 * @package   Store
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreBingShoppingUploader extends SiteCommandLineApplication
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
		parent::run();

		$this->lock();

		$filename = $this->config->bing->filename;

		$generator = $this->getGenerator();

		$this->debug(sprintf(
			Store::_('Generating Bing Shopping Catalog for %s')."\n",
			$this->config->site->title));

 		$contents = $generator->generate();
		$file = fopen($this->path.$filename, 'w');

		if ($file === false) {
			$this->terminate(sprintf(
				Store::_('Error writing file: %s'),
				$this->path.$filename)."\n\n");
		}

 		fwrite($file, $contents);
		fclose($file);

		$this->debug(Store::_('done')."\n\n");

		if ($this->display) {
			$this->debug(Store::_('Generated Feed:')."\n\n");

			echo $contents;

			$this->debug("\n\n");
		}

		if ($this->upload) {
			$this->debug(Store::_('Logging into Bing Shopping FTP ... '));

			$ftp_connection = ftp_connect($this->config->bing->server);

			if ($ftp_connection === false) {
				throw new SwatException('Unable to connect to FTP server: '.
					$this->config->bing->server);
			}

			$login_result = ftp_login($ftp_connection,
				$this->config->bing->username,
				$this->config->bing->password);

			if ($ftp_connection == null || $login_result == null) {
				$this->terminate(Store::_('failed to log in')."\n\n");
			} else {
				$this->debug(Store::_('done')."\n\n");
			}

			$this->debug(Store::_('Uploading Bing Shopping Catalog file ... '));

			$upload_result = ftp_put($ftp_connection, $filename,
				$this->path.$filename, FTP_BINARY);

			if (!$upload_result) {
				$this->terminate(Store::_('failed uploading')."\n");
			} else {
				$this->debug(Store::_('done')."\n\n");
			}

			ftp_close($ftp_connection);
		}

		$this->unlock();

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
	// {{{ protected function getGenerator()

	protected function getGenerator()
	{
		return new StoreBingShoppingGenerator($this->db, $this->config);
	}

	// }}}
}

?>
