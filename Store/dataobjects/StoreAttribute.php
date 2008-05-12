<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatString.php';
require_once 'Store/dataobjects/StoreAttributeType.php';

/**
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

	// }}}
	// {{{ private properties

	private static $attribute_type_cache = array();

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

		$this->registerInternalProperty('attribute_type',
			SwatDBClassMap::get('StoreAttributeType'));
	}

	// }}}
	// {{{ protected function hasSubDataObject()

	protected function hasSubDataObject($key)
	{
		$found = parent::hasSubDataObject($key);

		if ($key === 'attribute_type' && !$found) {
			$attribute_type_id = $this->getInternalValue('attribute_type');

			if ($attribute_type_id !== null &&
				array_key_exists($attribute_type_id, self::$attribute_type_cache)) {
				$this->setSubDataObject('attribute_type',
					self::$attribute_type_cache[$attribute_type_id]);

				$found = true;
			}
		}

		return $found;
	}

	// }}}
	// {{{ protected function setSubDataObject()

	protected function setSubDataObject($name, $value)
	{
		if ($name === 'attribute_type')
			self::$attribute_type_cache[$value->id] = $value;

		parent::setSubDataObject($name, $value);
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
