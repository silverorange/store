<?php

/**
 * Generates Facebook conversion tracking code.
 *
 * @package    Store
 * @copyright  2015 silverorange
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link       https://developers.facebook.com/docs/ads-for-websites/drive-conversions
 * @deprecated Use SiteAnalyticsModule's Facebook Pixel support alongside
 *             StoreAnalyticsOrderTracker for better analytics. This style
 *             conversion tracking will be discontinued by Facebook before the
 *             end of 2016.
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
		$this->show_tracking_pixel = (boolean)$show_tracking_pixel;
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
		$javascript = <<< 'JS'
		<!-- Meta Pixel Code -->
		<script>
		!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
		n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
		document,'script','https://connect.facebook.net/en_US/fbevents.js');
		// Insert Your Meta Pixel ID below.
		_fbq.push(['track', %s, { 'value': %s, 'currency': %s }]);
		</script>
		<!-- Insert Your Meta Pixel ID below. -->
		<noscript><img height="1" width="1" style="display:none"
		src="https://www.facebook.com/tr?id=%s&amp;ev=PageView&amp;noscript=1"
		/></noscript>
		<!-- End Meta Pixel Code -->
		JS;

		Swat::displayInlineJavaScript(
			sprintf(
				$javascript,
				SwatString::quoteJavaScriptString($this->tracking_id),
				SwatString::quoteJavaScriptString($this->tracked_value),
				SwatString::quoteJavaScriptString($this->tracked_value_currency),
				SwatString::minimizeEntities($this->tracking_id)
			)
		);
	}

	// }}}
	// {{{ protected function displayTrackingPixel()

	protected function displayTrackingPixel()
	{
		// @codingStandardsIgnoreStart
		$tracking_pixel = <<<'HTML'
		<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?ev=%s&amp;cd[value]=%s&amp;cd[currency]=%s"/>
		HTML;
		// @codingStandardsIgnoreEnd

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
