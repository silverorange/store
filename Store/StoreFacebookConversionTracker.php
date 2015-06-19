<?php

require_once 'Swat/SwatString.php';

/**
 * Generates Facebook conversion tracking code.
 *
 * @package   Store
 * @copyright 2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      https://developers.facebook.com/docs/ads-for-websites/drive-conversions
 */
class StoreFacebookConversionTracker
{
	// {{{ protected properties

	/**
	 * @var float
	 */
	protected $tracked_value = 0.00;

	/**
	 * @var string
	 */
	protected $tracking_id;

	/**
	 * @var string
	 */
	protected $tracked_value_currency = 'USD';

	/**
	 * @var boolean
	 */
	protected $show_tracking_pixel = true;

	// }}}
	// {{{ public function setTrackedValue()

	public function setTrackedValue($tracked_value)
	{
		$this->tracked_value = $tracked_value;
	}

	// }}}
	// {{{ public function setTrackingId()

	public function setTrackingId($tracking_id)
	{
		$this->tracking_id = $tracking_id;
	}

	// }}}
	// {{{ public function setTrackedValueCurrency()

	public function setTrackedValueCurrency($tracked_value_currency)
	{
		$this->tracked_value_currency = $tracked_value_currency;
	}

	// }}}
	// {{{ public function showTrackingPixel()

	public function showTrackingPixel($show_tracking_pixel)
	{
		$this->show_tracking_pixel = show_tracking_pixel;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if ($this->tracking_id != '') {
			$this->displayJavascriptTracker();

			if ($this->show_tracking_pixel) {
				$this->displayTrackingPixel();
			}
		}
	}

	// }}}
	// {{{ protected function displayJavascriptTracker()

	protected function displayJavascriptTracker()
	{
		$javascript = <<<'JS'
(function() {
	var _fbq = window._fbq || (window._fbq = []);
	if (!_fbq.loaded) {
		var fbds = document.createElement('script');
		fbds.async = true;
		fbds.src = '//connect.facebook.net/en_US/fbds.js';
		var s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(fbds, s);
		_fbq.loaded = true;
	}
})();
window._fbq = window._fbq || [];
window._fbq.push(['track', %s, {'value':%s,'currency':%s}]);
JS;

		Swat::displayInlineJavaScript(
			sprintf(
				$javascript,
				SwatString::quoteJavaScriptString($this->tracking_id),
				SwatString::quoteJavaScriptString($this->tracked_value),
				SwatString::quoteJavaScriptString($this->tracked_value_currency)
			)
		);
	}

	// }}}
	// {{{ protected function displayTrackingPixel()

	protected function displayTrackingPixel()
	{
		$tracking_pixel = <<<'XHTML'
<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=%s&amp;cd[value]=%s&amp;cd[currency]=%s&amp;noscript=1" /></noscript>
XHTML;

		printf(
			$tracking_pixel,
			SwatString::minimizeEntities($this->tracking_id),
			SwatString::minimizeEntities($this->tracked_value),
			SwatString::minimizeEntities($this->tracked_value_currency)
		);

	}

	// }}}
}

?>
