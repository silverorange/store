<?php

/**
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreRecentStack
{
	// {{{ public properties

	public $max_size = 10;

	// }}}
	// {{{ private properties

	private $stack = array();

	// }}}
	// {{{ public function add()

	public function add($id)
	{
		$key = array_search($id, $this->stack);

		if ($key !== false)
			unset($this->stack[$key]);

		array_unshift($this->stack, $id);

		while (count($this->stack) > $this->max_size)
			array_pop($this->stack);
	}

	// }}}
	// {{{ public function get()

	public function get($count = null, $exclude_id = null)
	{
		if ($count === null) {
			$out = $this->stack;
		} else {
			$out = array();
			reset($this->stack);
			for ($i = 0; $i < $count; $i++) {
				$value = current($this->stack);

				if ($value === $exclude_id)
					$value = next($this->stack);

				if ($value === false)
					break;

				$out[] = $value;
				next($this->stack);
			}
		}

		return $out;
	}

	// }}}
}

?>
