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
		$session_name = $this->app->id;

		session_cache_limiter('');
		session_save_path('/so/phpsessions/'.$this->app->id);
		session_name($session_name);

		if (isset($_GET[$session_name]) ||
			isset($_POST[$session_name]) ||
			isset($_COOKIE[$session_name]))
				$this->activate();
	}

    // }}}
    // {{{ public function activate()

	public function activate()
	{
		if ($this->isActive())
			return;

		session_start();

		if (!isset($_SESSION['account_id']))
			$_SESSION['account_id'] = 0;
	}

    // }}}
    // {{{ public function isLoggedIn()

	/**
	 * Check the current user's logged-in status
	 *
	 * @return boolean true if user is logged in, false if the user is not
	 *                  logged in.
	 */
	public function isLoggedIn()
	{
		if (isset($_SESSION['account_id']))
			return ($_SESSION['account_id'] != 0);

		return false;
	}

    // }}}
    // {{{ public function isActive()

	/**
	 * Check if there is an active session
	 * @return bool True if session is active. 
	 */
	public function isActive()
	{
		return (strlen(session_id()) > 0);
	}

    // }}}
    // {{{ public function getAccountID()

	/**
	 * Retrieve the current account ID
	 * @return integer current account ID, or null if not logged in.
	 */
	public function getAccountID()
	{
		if (!$this->isLoggedIn())
			return null;

		return $_SESSION['account_id'];
	}

    // }}}
    // {{{ public function getSessionID()

	/**
	 * Retrieve the current session ID
	 * @return integer current session ID, or null if no active session.
	 */
	public function getSessionID()
	{
		if (!$this->isActive())
			return null;

		return session_id();
	}

    // }}}
    // {{{ public function isDefined()

	/**
	 * Check existence of a session variable.
	 */
	public function isDefined($name)
	{
		if (!$this->isActive())
			throw new StoreException('Session is not active.');

		return isset($_SESSION[$name]);
	}

    // }}}
    // {{{ private function __set()

	/**
	 * Set a session variable.
	 */
	private function __set($name, $value)
	{
		if (!$this->isActive())
			throw new StoreException('Session is  not active.');

		$_SESSION[$name] = $value;
	}

    // }}}
    // {{{ private function &__get()

	/**
	 * Get a session variable.
	 */
	private function &__get($name)
	{
		if (!$this->isActive())
			throw new StoreException('Session is not active.');

		if (!isset($_SESSION[$name]))
			throw new StoreException("Session variable '$name' is not set.");

		return $_SESSION[$name];
	}

    // }}}
}

?>
