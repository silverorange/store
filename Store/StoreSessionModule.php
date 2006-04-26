<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Date.php';

/**
 * Web application module for sessions
 *
 * @package Store
 * @copyright silverorange 2006
 */
class StoreSessionModule extends SiteApplicationModule
{
    // {{{ public function init()

	public function init()
	{
		session_cache_limiter('');
		session_save_path('/so/phpsessions/'.$this->app->id);
		session_name($this->app->id);
		session_start();
	}

    // }}}
}

?>
