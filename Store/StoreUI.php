<?php

require_once 'Swat/SwatUI.php';

/**
 * This is a deprecated equivalent of {@link SwatUI}.
 *
 * UI manager for the store package
 *
 * Subclass of {@link SwatUI} for use with the Store package.  This can be used
 * as a central place to add {@link SwatUI::$class_map class maps} and 
 * {@link SwatUI::registerHandler() UI handlers} that are specific to the Store
 * package.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @deprecated The parent of this class, {@link SwatUI}, can now be used directly.
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreUI extends SwatUI
{
}

?>
