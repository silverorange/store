<?php

require_once 'Site/SiteApplicationModule.php';
require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StoreFeedback.php';

/**
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackModule extends SiteApplicationModule
{
	// {{{ public function showPanel()

	public function showPanel()
	{
		$show = false;

		if (isset($_GET['feedback'])) {
			$show = true;
		} elseif (in_array($this->app->getPage()->getSource(), $this->getSources())) {
			$show = true;
		} else {
			$show = ($this->getSearchReferrer() !== null);
		}

		return $show;
	}

	// }}}
	// {{{ public function getSearchReferrer()

	public function getSearchReferrer()
	{
		$referrer = null;

		$session = $this->app->getModule('SiteSessionModule');
		if (isset($session->feedback_referrer)) {
			$referrer = $session->feedback_referrer;
		}

		return $referrer;
	}

	// }}}
	// {{{ public function clearSearchReferrer()

	public function clearSearchReferrer()
	{
		$session = $this->app->getModule('SiteSessionModule');
		if (isset($session->feedback_referrer)) {
			unset($session->feedback_referrer);
		}
	}

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this analytics module
	 *
	 * Stores ad object in the session module and initializes ad database
	 * reference.
	 */
	public function init()
	{
		if ($this->isSearchReferrer()) {
			$session = $this->app->getModule('SiteSessionModule');
			$session->activate();
			$session->feedback_referrer = SiteApplication::initVar(
				'HTTP_REFERER',
				null,
				SiteApplication::VAR_SERVER
			);
		}
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The store feedback module depends on the SiteSessionModule and
	 * SiteDatabaseModule features.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');
		$depends[] = new SiteApplicationModuleDependency('SiteSessionModule');
		return $depends;
	}

	// }}}
	// {{{ protected function isSearchReferrer()

	protected function isSearchReferrer()
	{
		$referrer = SiteApplication::initVar(
			'HTTP_REFERER',
			null,
			SiteApplication::VAR_SERVER
		);

		$patterns = array(
			'\.baidu\.',
			'\.google\.',
			'\.bing\.com',
			'\.altavista\.com',
			'\.alltheweb\.com',
			'\.ask\.com',
			'a9\.com',
			'search\.aol\.com',
			'search\.yahoo\.com',
			'search\.earthlink\.net',
		);

		$exp = '/(?:'.implode('|', $patterns).')/ui';

		return (preg_match($exp, $referrer) === 1);
	}

	// }}}
	// {{{ protected function getSources()

	protected function getSources()
	{
		return array(
			'checkout/thankyou',
		);
	}

	// }}}
}

?>
