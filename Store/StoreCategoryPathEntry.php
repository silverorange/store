<?php

/**
 * @package   Store
 * @copyright 2004-2007 silverorange
 */
class StoreCategoryPathEntry
{
	public $id;
	public $parent;
	public $shortname;
	public $title;

	public function __construct($row)
	{
		$this->id = $row->id;
		$this->parent = $row->parent;
		$this->shortname = $row->shortname;
		$this->title = $row->title;
	}
}

?>
