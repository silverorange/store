<?php

require_once 'Swat/exceptions/SwatException.php';
require_once 'Swat/SwatError.php';
require_once 'Site/SiteErrorLogger.php';
require_once 'Site/SiteExceptionLogger.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Date/TimeZone.php';

/**
 * Configuration module for Store command-line applications
 *
 * Performs additional module configuration.
 *
 * @package   Store
 * @copyright 2007-2008 silverorange
 */
class StoreCommandLineConfigModule extends SiteConfigModule
{
	// {{{ protected function configure()

	/**
	 * Configures modules of the application before they are initialized
	 */
	public function configure()
	{
		parent::configure();

		if (isset($this->exceptions->log_location))
			SwatException::setLogger(new SiteExceptionLogger(
				$this->exceptions->log_location, $this->exceptions->base_uri));

		if (isset($this->errors->log_location))
			SwatError::setLogger(new SiteErrorLogger(
				$this->errors->log_location, $this->errors->base_uri));

		$this->app->database->dsn = $this->database->dsn;

		$this->app->default_time_zone =
			new Date_TimeZone($this->date->time_zone);

		$this->app->default_locale = $this->i18n->locale;

		setlocale(LC_ALL, $this->i18n->locale.'.UTF-8');
	}

	// }}}
}

?>
