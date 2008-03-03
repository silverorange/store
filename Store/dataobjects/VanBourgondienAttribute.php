<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatString.php';

/*
 * @package   VanBourgondien
 * @copyright 2007 silverorange
 */
class VanBourgondienAttribute extends SwatDBDataObject
{
	// {{{ constants

	const TYPE_GENERAL = 1;
	const TYPE_LIGHT = 2;
	const TYPE_TAG = 4;

	// }}}
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

		if ($this->attribute_type === VanBourgondienAttribute::TYPE_LIGHT) {
			$anchor->title = sprintf('Search for items that require %s…', $this->title);
			$anchor->href = sprintf('search?light=%s', $this->shortname);
		} elseif ($this->attribute_type === VanBourgondienAttribute::TYPE_TAG) {
			$anchor->title = sprintf('Search for other %s items…', $this->title);
			$anchor->href = sprintf('search?attr=%s', $this->shortname);
		} else {
			$anchor->title = sprintf('Search for items with attribute %s…', $this->title);
			$anchor->href = sprintf('search?attr=%s', $this->shortname);
		}

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
		switch ($this->attribute_type) {
		case VanBourgondienAttribute::TYPE_GENERAL:
		case VanBourgondienAttribute::TYPE_LIGHT:
			$img_tag = new SwatHtmlTag('img');
			$img_tag->src = sprintf(
				'packages/van-bourgondien/images/attributes/%s.png',
				$this->shortname);

			$img_tag->alt = $this->title;
			$img_tag->class = 'product-attribute';
			$img_tag->title = $this->title;
			$img_tag->width = '16';
			$img_tag->height = '16';

			if ($link_to_search) {
				$anchor = $this->getSearchAnchorTag();
				$anchor->open();
				$img_tag->display();
				echo ' <span>', $this->title, '</span>';
				$anchor->close();
			} else {
				$img_tag->display();
				echo ' ', $this->title;
			}
			break;

		default:
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
			break;
		}
	}

	// }}}
	// {{{ public function displayIcon()

	public function displayIcon($grayed_out = false)
	{
		switch ($this->attribute_type) {
		case VanBourgondienAttribute::TYPE_GENERAL:
		case VanBourgondienAttribute::TYPE_LIGHT:
			$img_tag = new SwatHtmlTag('img');
			$img_tag->src = sprintf(
				'packages/van-bourgondien/images/attributes/%s%s.png',
				$this->shortname, ($grayed_out ? '-gray' : ''));

			$img_tag->alt = $this->title;
			$img_tag->class = 'product-attribute';
			$img_tag->title = $this->title;
			$img_tag->width = '16';
			$img_tag->height = '16';

			$img_tag->display();
			break;
		}
	}

	// }}}
}

?>
