<?php

require_once 'Turing/TuringSeleniumTest.php';

/**
 * Test manipulating the cart
 *
 * @package   Store
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCartTest extends TuringSeleniumTest
{
	// {{{ abstract protected function initCartEntries()

	abstract protected function initCartEntries();

	// }}}
	// {{{ abstract protected function getPageUri()

	abstract protected function getPageUri();

	// }}}
	// {{{ protected function findQuantityEntry()

	protected function findQuantityEntry($index = 1)
	{
		$fields = $this->getAllFields();
		$count = 0;

		foreach ($fields as $field) {
			if (substr($field, 0, 15) === 'quantity_entry_') {
				$count++;
				if ($count === $index) {
					return $field;
				}
			}
		}

		throw new Exception('Quantity entry not found');
	}

	// }}}

	// tests
	// {{{ public function testPageLoad()

	public function testPageLoad()
	{
		$this->initCartEntries();

		$this->open($this->getPageUri());
		$this->assertNoErrors();

		$this->assertTrue(
			$this->isTextPresent('Shopping Cart (2 items)'),
			'Cart summary line is not present.'
		);

		$this->assertEquals(
			'2',
			$this->getValue($this->findQuantityEntry(1)),
			'First cart entry quantity incorrect.'
		);

		$this->assertEquals(
			'1',
			$this->getValue($this->findQuantityEntry(2)),
			'Second cart entry quantity incorrect.'
		);
	}

	// }}}
	// {{{ public function testUpdate()

	public function testUpdate()
	{
		$this->initCartEntries();

		$this->open($this->getPageUri());
		$this->assertNoErrors();

		$this->type($this->findQuantityEntry(1), '4');
		$this->click('header_update_button');
		$this->waitForPageToLoad('30000');

		$this->assertNoErrors();

		$this->assertEquals(
			'4',
			$this->getValue($this->findQuantityEntry(1)),
			'Value of first quantity entry does not match entered value.'
		);

		$this->assertEquals(
			'1',
			$this->getValue($this->findQuantityEntry(2)),
			'Value of second quantity entry was updated when it was '.
			'not supposed to be.'
		);
	}

	// }}}
	// {{{ public function testNegativeUpdate()

	public function testNegativeUpdate()
	{
		$this->initCartEntries();

		$this->open($this->getPageUri());
		$this->assertNoErrors();

		$this->type($this->findQuantityEntry(1), '-10');
		$this->click('header_update_button');
		$this->waitForPageToLoad('30000');

		$this->assertNoErrors();

		$this->assertTrue(
			$this->isTextPresent('field must not be less than 0'),
			'Error text is not present when entering negative quantity.'
		);
	}

	// }}}
}

?>
