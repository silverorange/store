<?php

/**
 * @package   Store
 * @copyright 2009-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreRecentModule
 */
class StoreRecentStack
{
	// {{{ public properties

	public $max_size = 10;

	// }}}
	// {{{ protected properties

	protected $stack = array();

	// }}}
	// {{{ public function add()

	public function add($id)
	{
		// if it's already on the stack, remove it
		$key = array_search($id, $this->stack);
		if ($key !== false) {
			unset($this->stack[$key]);
		}

		// add to start of stack
		array_unshift($this->stack, $id);

		// ensure stack didn't grow too big
		while (count($this->stack) > $this->max_size) {
			array_pop($this->stack);
		}
	}

	// }}}
	// {{{ public function get()

	public function get($count = null, $exclude_id = null)
	{
		if ($count === null) {
			$exclude_id = ($exclude_id === null) ? array() : array($exclude_id);
			$out = array_diff($this->stack, $exclude_id);
		} else {
			$out = array();
			reset($this->stack);
			for ($i = 0; $i < $count; $i++) {
				$value = current($this->stack);

				if ($value === $exclude_id) {
					$value = next($this->stack);
				}

				if ($value === false) {
					break;
				}

				$out[] = $value;
				next($this->stack);
			}
		}

		return $out;
	}

	// }}}
}

?>
