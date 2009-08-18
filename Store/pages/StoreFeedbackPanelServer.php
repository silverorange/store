<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Store/dataobjects/StoreFeedback.php';
require_once 'Store/dataobjects/StoreArticle.php';
require_once 'Swat/SwatUI.php';

/**
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackPanelServer extends SiteXMLRPCServer
{
	// {{{ protected properties

	protected $ui;

	// }}}
	// {{{ public function getContent()

	/**
	 * Returns the XHTML required to display the feedback panel
	 *
	 * @param string $method the HTTP method. 'GET or 'POST'.
	 * @param string $data the raw HTTP POST data of the form if the method is
	 *                     'POST'.
	 * @param string $uri the URI of the page making the request (referrer).
	 *
	 * @return array an array containing the following elements:
	 *               - <kbd>content</kbd>      - the XHTML required to display
	 *                                           the feedback panel.
	 *               - <kbd>head_entries</kbd> - a list of required external
	 *                                           JS and CSS files.
	 *               - <kbd>success</kbd>      - if post data was provided,
	 *                                           this contains true if the data
	 *                                           submitted successfully.
	 *                                           otherwise, false.
	 */
	public function getContent($method, $data, $uri)
	{
		if (strtolower($method) === 'post') {
			$this->initPostData($data);
		}

		$return = array();

		ob_start();

		$this->initUi();
		$this->ui->process();

		if ($this->ui->getWidget('feedback_form')->isSubmitted() &&
			!$this->ui->getRoot()->hasMessage()) {
			$this->processFeedback();
		}

		$this->ui->display();

		$return['content'] = ob_get_clean();
		$return['head_entries'] = '';
		$return['success'] = (!$this->ui->getRoot()->hasMessage());

		return $return;
	}

	// }}}
	// {{{ protected function initPostData()

	protected function initPostData($data)
	{
		$data_exp = explode('&', $data);
		$args = array();
		foreach ($data_exp as $parameter) {
			if (strpos($parameter, '=')) {
				list($key, $value) = explode('=', $parameter, 2);
			} else {
				$key   = $parameter;
				$value = null;
			}

			$key   = urldecode($key);
			$value = urldecode($value);

			$regs = array();
			if (preg_match('/^(.+)\[(.*)\]$/', $key, $regs)) {
				$key = $regs[1];
				$array_key = ($regs[2] == '') ? null : $regs[2];
				if (!isset($args[$key]))
					$args[$key] = array();

				if ($array_key === null) {
					$args[$key][] = $value;
				} else {
					$args[$key][$array_key] = $value;
				}
			} else {
				$args[$key] = $value;
			}
		}

		foreach ($args as $key => $value) {
			$_POST[$key] = $value;
		}
	}

	// }}}
	// {{{ protected function processFeedback()

	protected function processFeedback()
	{
		$feedback = $this->createFeedback();

		$feedback->email    = $this->ui->getWidget('email')->value;
		$feedback->bodytext = $this->ui->getWidget('bodytext')->value;

		$feedback->createdate = new SwatDate();
		$feedback->createdate->toUTC();

		if ($this->app->hasModule('StoreFeedbackModule')) {
			$module = $this->app->getModule('StoreFeedbackModule');
			$feedback->http_referrer = $module->getSearchReferrer();
			$module->clearSearchReferrer();
		}

		$feedback->save();
	}

	// }}}
	// {{{ protected function createFeedback()

	protected function createFeedback()
	{
		$class = SwatDBClassMap::get('StoreFeedback');
		$feedback = new $class();
		$feedback->setDatabase($this->app->db);
		return $feedback;
	}

	// }}}
	// {{{ protected function initUi()

	protected function initUi()
	{
		$this->ui = new SwatUI();
		$this->ui->loadFromXml($this->getXmlUi());
		$description = $this->ui->getwidget('feedback_description');

		$class = SwatDBClassMap::get('StoreArticle');
		$article = new $class();
		$article->setDatabase($this->app->db);

		if ($article->loadByShortname('feedback'))
			$description->content = $article->bodytext;

		if (strlen($description->content) == 0)
			$description->content =
				'<p>Can’t find what you’re looking for? Having trouble '.
				'with the website? Please let us know so we can improve '.
				'our website:</p>';

		$this->ui->init();
	}

	// }}}
	// {{{ protected function getXmlUi()

	protected function getXmlUi()
	{
		return 'Store/feedback-panel.xml';
	}

	// }}}
}

?>
