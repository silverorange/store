<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatString.php';

/*
 * @package   Store
 * @copyright 2008 silverorange
 */
class StoreAttribute extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Internal name
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Order to display
	 * not null default 0
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * Attribute Type
	 * not null default 0
	 *
	 * @var integer
	 */
	public $attribute_type;

	// }}}
	// {{{ public function getSearchAnchorTag()

	public function getSearchAnchorTag()
	{
		$anchor = new SwatHtmlTag('a');
		$anchor->title = sprintf('Search for items with attribute %s…', $this->title);
		$anchor->href = sprintf('search?attr=%s', $this->shortname);

		return $anchor;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Attribute';
		$this->id_field = 'integer:id';
	}

	// }}}

	// display methods
	// {{{ public function display()

	public function display($link_to_search = false)
	{
		if ($link_to_search) {
			$anchor = $this->getSearchAnchorTag();
			$span = new SwatHtmlTag('span');
			$span->setContent($this->title);
			$anchor->open();
			$span->display();
			$anchor->close();
		} else {
			echo SwatString::minimizeEntities($this->title);
		}
	}

	// }}}
}

?>
