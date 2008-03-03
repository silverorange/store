<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'VanBourgondien/dataobjects/VanBourgondienAttribute.php';

/**
 * A recordset wrapper class for VanBourgondienAttribute objects
 *
 * @package   VanBourgondien
 * @copyright 2007 silverorange
 * @see       VanBourgondienAttribute
 */
class VanBourgondienAttributeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('VanBourgondienAttribute');
	}

	// }}}
	// {{{ public function getByType()

	public function getByType($type)
	{
		$attributes = new VanBourgondienAttributeWrapper();

		foreach ($this as $attribute)
			if (($attribute->attribute_type & $type) > 0)
				$attributes->add($attribute);

		return $attributes;
	}

	// }}}

	// display methods
	// {{{ public function display()

	public function display($link_to_search = false)
	{
		if (count($this)) {
			echo '<ul class="attributes">';

			foreach ($this as $attribute) {
				echo '<li>';
				$attribute->display($link_to_search);
				echo '</li>';
			}

			echo '</ul>';
		}
	}

	// }}}
	// {{{ public function displayInline()

	public function displayInline($link_to_search = false)
	{
		$count = count($this);
		if ($count > 0) {
			echo '<span class="tag-attributes">';
			$first = true;

			foreach ($this as $attribute) {
				if ($first)
					$first = false;
				else
					echo ($count === 2) ? ' &amp; ' : ', ';

				$attribute->display($link_to_search);
			}

			echo '</span>';
		}
	}

	// }}}

	// {{{ public static function getLightRanges()

	public static function getLightRanges()
	{
		$ranges = array();

		$ranges[] = array(
			'title' => 'Full Sun',
			'attributes' => array('sun'));

		$ranges[] = array(
			'title' => 'Partial Sun',
			'attributes' => array('sunshade'));

		$ranges[] = array(
			'title' => 'Full Shade',
			'attributes' => array('shade'));

		$ranges[] = array(
			'title' => 'Full Sun to Partial Shade',
			'attributes' => array('sun', 'sunshade'));

		$ranges[] = array(
			'title' => 'Partial Sun to Full Shade',
			'attributes' => array('sunshade', 'shade'));

		$ranges[] = array(
			'title' => 'Full Sun to Full Shade',
			'attributes' => array('sun', 'sunshade', 'shade'));

		return $ranges;
	}

	// }}}
	// {{{ public function getLightRange()

	public function getLightRange()
	{
		$shortnames = array();

		foreach ($this->getByType(VanBourgondienAttribute::TYPE_LIGHT) as $attribute)
			$shortnames[] = $attribute->shortname;

		$ranges = self::getLightRanges();
		$ranges = array_reverse($ranges);
		$range = null;

		foreach ($ranges as $light_range) {
			$difference = array_diff($light_range['attributes'], $shortnames);
			if (count($difference) === 0) {
				$range = $light_range;
				break;
			}
		}

		return $range;
	}

	// }}}
}

?>
