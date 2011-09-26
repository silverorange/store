<?php

require_once 'Store/StoreProductFileFtpUploader.php';
require_once 'Store/StoreFroogleGenerator.php';

/**
 * Application to upload Froogle files to Google
 *
 * @package   Store
 * @copyright 2008-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreFroogleUploader extends StoreProductFileFtpUploader
{
	// {{{ protected function getGenerator()

	protected function getGenerator()
	{
		return new StoreFroogleGenerator($this->db, $this->config);
	}

	// }}}
	// {{{ protected function getFilename()

	protected function getFilename()
	{
		return $this->config->froogle->filename;
	}

	// }}}
	// {{{ protected function getFtpServer()

	protected function getFtpServer()
	{
		return $this->config->froogle->server;
	}

	// }}}
	// {{{ protected function getFtpUsername()

	protected function getFtpUsername()
	{
		return $this->config->froogle->username;
	}

	// }}}
	// {{{ protected function getFtpPassword()

	protected function getFtpPassword()
	{
		return $this->config->froogle->password;
	}

	// }}}
}

?>
