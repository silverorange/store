<?php

require_once 'Store/StoreProductFileFtpUploader.php';
require_once 'Store/StoreFroogleGenerator.php';

/**
 * Application to upload Froogle files to Google
 *
 * @package   Store
 * @copyright 2008-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreFroogleUploader extends StoreProductFileFtpUploader
{
	// {{{ protected function getGenerator()

	protected function getGenerator()
	{
		return new StoreFroogleGenerator($this);
	}

	// }}}
	// {{{ protected function getFilename()

	protected function getFilename()
	{
		return $this->app->config->froogle->filename;
	}

	// }}}
	// {{{ protected function getFtpServer()

	protected function getFtpServer()
	{
		return $this->app->config->froogle->server;
	}

	// }}}
	// {{{ protected function getFtpUsername()

	protected function getFtpUsername()
	{
		return $this->app->config->froogle->username;
	}

	// }}}
	// {{{ protected function getFtpPassword()

	protected function getFtpPassword()
	{
		return $this->app->config->froogle->password;
	}

	// }}}
}

?>
