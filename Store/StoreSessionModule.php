<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Store/StoreDataObjectClassMap.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Date.php';

/**
 * Web application module for sessions
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreSessionModule extends SiteApplicationModule
{
	// {{{ private properties

	private $data_object_classes = array();

	// }}}
	// {{{ protected properties

	protected $login_callbacks = array();

	// }}}
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

		// load the dataobject classes before starting the session
		if (count($this->data_object_classes)) {
			$class_map = StoreDataObjectClassMap::instance();

			foreach ($this->data_object_classes as $name => $class)
				$class_map->resolveClass($class);
		}

		session_start();

		foreach ($this->data_object_classes as $name => $class) {
			if (isset($this->$name) && $this->$name !== null)
				$this->$name->setDatabase($this->app->database->getConnection());
			else
				$this->$name = null;
		}
	}

	// }}}
	// {{{ public function login()

	/**
	 * Logs in the current user
	 *
	 * @param string $email the email address of the user to login.
	 * @param string $password the password of the user to login.
	 *
	 * @return boolean true if the user was successfully logged in and false if
	 *                       the email/password pair did not match an account.
	 */
	public function login($email, $password)
	{
		if ($this->isLoggedIn())
			$this->logout();

		$class_mapper = StoreDataObjectClassMap::instance();
		$class_name = $class_mapper->resolveClass('StoreAccount');
		$account = new $class_name();
		$account->setDatabase($this->app->db);

		if ($account->loadWithCredentials($email, $password)) {
			$this->activate();
			$this->account = $account;

			foreach ($this->login_callbacks as $login_callback) {
				$callback = $login_callback['callback'];
				$parameters = $login_callback['parameters'];
				call_user_func_array($callback, $parameters);
			}
		}

		return $this->isLoggedIn();
	}

	// }}}
	// {{{ public function logout()

	/**
	 * Logs the current user out
	 */
	public function logout()
	{
		if (!$this->isLoggedIn())
			return;

		$this->account = null;
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
		if (!$this->isActive())
			return false;

		if (!isset($this->account))
			return false;

		if ($this->account === null)
			return false;

		if ($this->account->id === null)
			return false;

		return true;
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

		return $this->account->id;
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
	// {{{ public function registerDataObject()

	/**
	 * Register a dataobject class for a session variable
	 *
	 * @param string $name the name of the session variable
	 * @param string $class the dataobject class name
	 */
	public function registerDataObject($name, $class)
	{
		$this->data_object_classes[$name] = $class;
	}

	// }}}
	// {{{ public function registerLoginCallback()

	/**
	 * Registers a callback function that is executed when a successful session
	 * login is performed
	 *
	 * @param callback $callback the callback to call when a successful login
	 *                            is performed.
	 * @param array $parameters the paramaters to pass to the callback. Use an
	 *                           empty array for no parameters.
	 */
	public function registerLoginCallback($callback, $parameters = array())
	{
		if (!is_callable($callback))
			throw new StoreException('Cannot register invalid callback.');

		if (!is_array($parameters))
			throw new StoreException('Callback parameters must be specified '.
				'in an array.');

		$this->login_callbacks[] = array(
			'callback' => $callback,
			'parameters' => $parameters
		);
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
	// {{{ private function __isset()

	/**
	 * Checks the existence of a session variable
	 *
	 * @param string $name the name of the session variable to check.
	 */
	private function __isset($name)
	{
		if (!$this->isActive())
			throw new StoreException('Session is  not active.');

		return isset($_SESSION[$name]);
	}

	// }}}
	// {{{ private function __unset()

	/**
	 * Removes a session variable
	 *
	 * @param string $name the name of the session variable to set.
	 */
	private function __unset($name)
	{
		if (!$this->isActive())
			throw new StoreException('Session is  not active.');

		if (!isset($_SESSION[$name]))
			throw new StoreException("Session variable '$name' is not set.");

		unset($_SESSION[$name]);
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
