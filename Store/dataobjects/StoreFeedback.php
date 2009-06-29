<?php

require_once 'Site/dataobjects/SiteComment.php';

/**
 * Freeform feedback about the website
 *
 * This class is extended from SiteComment. The following fields from
 * SiteComment are not exposed in the UI by default:
 *
 * - link
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedback extends SiteComment
{
	// {{{ public properties

	/**
	 * HTTP referrer that customer used to reach the site
	 *
	 * For example, this could contain a search referrer.
	 *
	 * @var string
	 */
	public $http_referrer;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'Feedback';
	}

	// }}}
}

?>
