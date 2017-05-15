<?php

/**
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreContactMessage extends SiteContactMessage
{
	// {{{ public static function getSubjects()

	public static function getSubjects()
	{
		return array(
			'general'  => Store::_('General Question'),
			'order'    => Store::_('My Order'),
			'website'  => Store::_('Website'),
			'products' => Store::_('Products'),
			'privacy'  => Store::_('Privacy'),
		);
	}

	// }}}
}

?>
