<?php

/**
 * Test the product image display overlay
 *
 * @package   Store
 * @copyright 2012-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreProductImageDisplayTest extends TuringSeleniumTest
{
	// {{{ abstract protected function getPageUri()

	abstract protected function getPageUri();

	// }}}
	// {{{ testOpenUsingPrimaryImage()

	public function testOpenUsingPrimaryImage()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		$this->click('id=product_image_link');

		$this->assertEquals(
			1,
			preg_match(
				'/'.preg_quote($this->getPageUri(), '/').'#image[0-9]+$/',
				$this->getLocation()
			),
			'Window location was not updated when primary image was clicked.'
		);

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Product image display overlay is missing or not visible.'
		);
	}

	// }}}
	// {{{ testOpenUsingSecondaryImage()

	public function testOpenUsingSecondaryImage()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		if (!$this->isElementPresent('id=product_secondary_images')) {
			$this->markTestSkipped(
				sprintf(
					'No secondary product images available to test at %s.'.
					$this->getPageUri()
				)
			);
		}

		$this->click("xpath=//ul[@id='product_secondary_images']/li/a");

		$this->assertEquals(
			1,
			preg_match(
				'/'.preg_quote($this->getPageUri(), '/').'#image[0-9]+$/',
				$this->getLocation()
			),
			'Window location was not updated when secondary image was clicked.'
		);

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Clicking secondary product image did not open product image '.
			'display.'
		);
	}

	// }}}
	// {{{ testNavigateUsingKeyboard()

	public function testNavigateUsingKeyboard()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		$this->click('id=product_image_link');

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Clicking primary product image did not open product image '.
			'display.'
		);

		$this->assertEquals(
			1,
			preg_match(
				'/'.preg_quote($this->getPageUri(), '/').'#image[0-9]+$/',
				$this->getLocation()
			),
			'Window location was not updated when primary image was clicked.'
		);

		$primary_location = $this->getLocation();

		$keycodes = array(
			'up'    => 38,
			'down'  => 40,
			'left'  => 37,
			'right' => 39,
		);

		foreach ($keycodes as $name => $keycode) {
			$current_location = $this->getLocation();
			do {
				$this->keyPressNative($keycode);

				usleep(100000);

				$this->assertNotEquals(
					$current_location,
					$this->getLocation(),
					sprintf(
						'Location was not updated when "%s" key navigation '.
						'was used.',
						$name
					)
				);

				$this->assertEquals(
					1,
					preg_match(
						'/'.preg_quote($this->getPageUri(), '/').'#image[0-9]+$/',
						$this->getLocation()
					),
					sprintf(
						'Updated window location not valid when "%s" key '.
						'navigation was used.',
						$name
					)
				);

				$current_location = $this->getLocation();

			} while ($this->getLocation() != $primary_location);
		}
	}

	// }}}
	// {{{ testCloseUsingLink()

	public function testCloseUsingLink()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		$this->click('id=product_image_link');

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Unable to open product image display.'
		);

		// close via close link
		$this->click(
			"xpath=//a[contains(@class, 'store-product-image-display-close')]"
		);

		// make sure it closed
		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: none;')]"
			),
			'Product image display did not close properly when close button '.
			'was clicked.'
		);
	}

	// }}}
	// {{{ testCloseUsingOverlay()

	public function testCloseUsingOverlay()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		$this->click('id=product_image_link');

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Unable to open product image display.'
		);

		// close via overlay mask
		$this->click(
			"xpath=//a[@class='store-product-image-display-overlay-mask']"
		);

		// make sure it closed
		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: none;')]"
			),
			'Product image display did not close properly when overlay mask '.
			'was clicked.'
		);
	}

	// }}}
	// {{{ testCloseUsingImageContainer()

	public function testCloseUsingImageContainer()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		$this->click('id=product_image_link');

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Unable to open product image display.'
		);

		// close via image container
		$this->click(
			"xpath=//a[@class='store-product-image-display-container']"
		);

		// make sure it closed
		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: none;')]"
			),
			'Product image display did not close properly when image '.
			'container was clicked.'
		);
	}

	// }}}
	// {{{ testCloseUsingKeyboardEscape()

	public function testCloseUsingKeyboardEscape()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		$this->click('id=product_image_link');

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Unable to open product image display.'
		);

		// close via keyboard escape
		$this->keyPressNative(27);

		// make sure it closed
		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: none;')]"
			),
			'Product image display did not close properly when escape key '.
			'was pressed.'
		);
	}

	// }}}
	// {{{ testCloseUsingKeyboardBackspace()

	public function testCloseUsingKeyboardBackspace()
	{
		$this->open($this->getPageUri());

		$this->assertNoErrors();

		$this->waitForElementPresent(
			"xpath=//div[@class='store-product-image-display-title']"
		);

		$this->click('id=product_image_link');

		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: block;')]"
			),
			'Unable to open product image display.'
		);

		// close via keyboard backspace
		$this->keyPressNative(8);

		// make sure it closed
		$this->assertTrue(
			$this->isElementPresent(
				"xpath=//div[@class='store-product-image-display-overlay' ".
				"and contains(@style, 'display: none;')]"
			),
			'Product image display did not close properly when backspace key '.
			'was pressed.'
		);
	}

	// }}}
}

?>
