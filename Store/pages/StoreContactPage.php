<?php

require_once 'Site/pages/SiteContactPage.php';

/**
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreContactPage extends SiteContactPage
{
	// {{{ protected function getSubjects()

	protected function getSubjects()
	{
		$subjects = array(
			'general'  => Store::_('General Question'),
			'order'    => Store::_('My Order'),
			'website'  => Store::_('Website'),
			'products' => Store::_('Products'),
			'privacy'  => Store::_('Privacy'),
		);

		return $subjects;
	}

	// }}}
}

?>
