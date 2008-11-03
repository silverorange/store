<?php

require_once 'Site/SitePathEntry.php';

/**
 * @package Store
 * @copyright silverorange 2008
 */
class StoreCategoryPathEntry extends SitePathEntry
{
	// {{{ public properties

	public $twig;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new category path entry
	 *
	 * @param integer $id the database id of this entry.
	 * @param integer $parent the database id of the parent of this entry or
	 *                         null if this entry does not have a parent.
	 * @param string $shortname the shortname of this entry.
	 * @param string $title the title of this entry.
	 * @param boolean $twig whether this is a twig category.
	 */
	public function __construct($id, $parent, $shortname, $title, $twig)
	{
		parent::__construct($id, $parent, $shortname, $title);
		$this->twig = $twig;
	}

	// }}}
}
?>
