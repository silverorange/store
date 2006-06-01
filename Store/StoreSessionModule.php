<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/StoreDataObjectClassMap.php';
require_once 'Date.php';

/**
 * Web application module for sessions
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreSessionModule extends SiteApplicationModule
{
	// {{{ public function init()

	/**
	 * Initializes this session module
	 */
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

	/**
	 * Activates the current user's session
	 *
	 * Subsequent calls to the {@link isActive()} method will return true.
	 */
	public function activate()
	{
		if ($this->isActive())
			return;

		$class_map = StoreDataObjectClassMap::instance();
		// load the Account dataobject class before starting the session
		$class_map->resolveClass('StoreAccount');

		session_start();

		if (!isset($_SESSION['account_id']))
			$_SESSION['account_id'] = 0;
	}

	// }}}
	// {{{ public function logIn()

	/**
	 * Logs in the current user
	 *
	 * @param integer $account_id the account ID to log the current user in
	 *                             with.
	 */
	public function logIn($account_id)
	{
		if ($this->isLoggedIn())
			throw new SwatException('User is already logged in.');

		$_SESSION['account_id'] = (integer)$account_id;
	}

	// }}}
	// {{{ public function logOut()

	/**
	 * Logs the current user out
	 */
	public function logOut()
	{
		if (!$this->isLoggedIn())
			return;

		$_SESSION['account_id'] = 0;
	}

	// }}}
	// {{{ public function isLoggedIn()

	/**
	 * Checks the current user's logged-in status
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
	 * Checks if there is an active session
	 *
	 * @return boolean true if session is active, false if the session is
	 *                  inactive.
	 */
	public function isActive()
	{
		return (strlen(session_id()) > 0);
	}

	// }}}
	// {{{ public function getAccountID()

	/**
	 * Retrieves the current account ID
	 *
	 * @return integer the current account ID, or null if not logged in.
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
	 * Retrieves the current session ID
	 *
	 * @return integer the current session ID, or null if no active session.
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
	 * Checks the existence of a session variable
	 *
	 * @param string $name the name of the session variable to check for.
	 *
	 * @return boolean true if a session variable with the given name exists.
	 *                  False if it does not.
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
	 * Sets a session variable
	 *
	 * @param string $name the name of the session variable to set.
	 * @param mixed $value the value to set the variable to.
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
	 * Gets a session variable
	 *
	 * @param string $name the name of the session variable to get.
	 *
	 * @return mixed the session variable value. This is returned by reference.
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
