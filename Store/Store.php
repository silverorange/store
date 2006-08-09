<?php

/**
 * Container for package wide static methods
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Store 
{
	// {{{ constants

	const GETTEXT_DOMAIN = 'store';

	// }}}
	// {{{ public static function _()

	public static function _($message)
	{
		return Store::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	public static function gettext($message)
	{
		return dgettext(Store::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	public static function ngettext($singular_message,
		$plural_message, $number)
	{
		return dngettext(Store::GETTEXT_DOMAIN,
			$singular_message, $plural_message, $number);
	}

	// }}}
}

?>
