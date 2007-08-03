<?php

require_once 'Swat/SwatObject.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * This is a deprecated equivalent of {@link SwatDBClassMap}.
 *
 * Maps Store package class names to site-specific overridden class-names
 *
 * @package    Store
 * @copyright 2007 silverorange
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @deprecated This class was incorportated into SwatDB. Use
 *             {@link SwatDBClassMap} instead.
 * @see        SwatDBClassMap
 */
class StoreClassMap extends SwatObject
{
	public static function instance()
	{
		return SwatDBClassMap::instance();
	}
}

?>
