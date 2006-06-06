<?php

require_once('Store/pages/StoreArticlePage.php');

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreAccountPage extends StoreArticlePage
{
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// redirect to login page if not logged in
		if (!$this->app->session->isLoggedIn() &&
			$this->source != 'account/login' &&
			$this->source != 'account/edit')
				$this->app->relocate('account/login');
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
