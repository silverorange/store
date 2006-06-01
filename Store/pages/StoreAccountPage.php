<?php

require_once('Store/pages/StoreArticlePage.php');

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreAccountPage extends StoreArticlePage
{
	// {{{ protected properties

	protected $login_source = 'account/login';

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// redirect to login page if not logged in
		if (!$this->app->session->isLoggedIn() && $this->source != $this->login_source)
			$this->app->relocate($this->login_source);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

	}

	// }}}
}

?>
