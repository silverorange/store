<?php

/**
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreRecentStack
{
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
	}

	// }}}
	// {{{ public function get()

	public function get($count = null, $exclude_id = null)
	{
		if ($count === null) {
			$out = $this->stack;
		} else {
			$out = array();
			for ($i = 0; $i < $count; $i++) {
				if (isset($this->stack[$i]))
					if ($this->stack[$i] !== $exclude_id)
						$out[] = $this->stack[$i];
					else
						$count++;
				else
					break;
			}
		}

		return $out;
	}

	// }}}
}

?>
