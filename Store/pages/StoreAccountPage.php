<?php

require_once 'Site/pages/SiteArticlePage.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
abstract class StoreAccountPage extends SiteArticlePage
{
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// redirect to login page if not logged in
		if (!$this->app->session->isLoggedIn() &&
			$this->source != 'account/login' &&
			$this->source != 'account/forgotpassword' &&
			$this->source != 'account/resetpassword' &&
			$this->source != 'account/edit')
				$this->app->relocate('account/login');
	}

	// }}}
}

?>
