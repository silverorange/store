<?php

require_once 'VanBourgondien/VanBourgondienFroogleGenerator.php';
require_once 'VanBourgondien/VanBourgondienCommandLineApplication.php';

/**
 * Application to upload Froogle files to Google
 *
 * @package   VanBourgondien
 * @copyright 2008 silverorange
 */
class VanBourgondienFroogleUploader
	extends VanBourgondienCommandLineApplication
{
	// {{{ private property

	private $path;

	// }}}
	// {{{ public function setPath()

	public function setPath($path)
	{
		$this->path = $path;
	}

	// }}}
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
	 * Verbosity level for all messages.
	 */
	const VERBOSITY_ALL = 2;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$verbosity = new SiteCommandLineArgument(array('-v', '--verbose'),
			'setVerbosity', 'Sets the level of verbosity of the exporter. '.
			'Pass 0 to turn off all output.');

		$verbosity->addParameter('integer',
			'--verbose expects a level between 0 and 2.',
			self::VERBOSITY_ALL);

		$this->addCommandLineArgument($verbosity);
	}

	// }}}
	// {{{ public function run()

	public function run()
	{
		parent::run();

		$filename = $this->config->froogle->filename;

		$generator = new VanBourgondienFroogleGenerator(
			$this->db, $this->config);

		$this->output(sprintf("Generating froogle feed for %s ... \n",
			$this->config->site->title), self::VERBOSITY_ALL);

 		$xml = $generator->generate();
		$file = fopen($this->path.$filename, 'w');
 		fwrite($file, $xml);
		fclose($file);

		$this->output("done\n\n", self::VERBOSITY_ALL);

		$this->output('Logging into Froogle FTP ... ', self::VERBOSITY_ALL);

		$ftp_connection = ftp_connect($this->config->froogle->server);
		$login_result = ftp_login($ftp_connection,
			$this->config->froogle->username, $this->config->froogle->password);

		if ($ftp_connection == null || $login_result == null) {
			$this->output("unable to connect\n", self::VERBOSITY_ERRORS);
			exit(1);
		} else {
			$this->output("done\n\n", self::VERBOSITY_ALL);
		}

		$this->output('Uploading Froogle file ... ', self::VERBOSITY_ALL);

		$upload_result = ftp_put($ftp_connection, $filename,
			$this->path.$filename, FTP_BINARY);

		if (!$upload_result) {
			$this->output("upload failed\n", self::VERBOSITY_ERRORS);
			exit(1);
		} else {
			$this->output("done\n\n", self::VERBOSITY_ALL);
		}

		ftp_close($ftp_connection);
		$this->output("All done.\n", self::VERBOSITY_ALL);
	}

	// }}}
}

?>
