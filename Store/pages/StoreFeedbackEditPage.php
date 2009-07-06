<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'Store/dataobjects/StoreFeedback.php';

/**
 * Page to allow submitting customer feedback
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreFeedback
 */
class StoreFeedbackEditPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var StoreFeedback
	 */
	protected $feedback;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/feedback-panel.xml';
	}

	// }}}
	// {{{ protected function isNew()

	protected function isNew(SwatForm $form)
	{
		return true;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$message_display = new SwatMessageDisplay('message_display');

		$root = $this->ui->getRoot();
		$root->packStart($message_display);

		$thank_you = SiteApplication::initVar(
			'thank-you',
			false,
			SiteApplication::VAR_GET
		);

		if ($thank_you !== false) {
			$message = new SwatMessage(
				Store::_('Thank you for your feedback!')
			);
			$message_display->add($message);
		}

		$this->initFeedback();
	}

	// }}}
	// {{{ protected function initFeedback()

	protected function initFeedback()
	{
		$class = SwatDBClassMap::get('StoreFeedback');
		$this->feedback = new $class();
		$this->feedback->setDatabase($this->app->db);
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		$this->assignUiValuesToObject(
			$this->feedback,
			array(
				'email',
				'bodytext',
			)
		);

		if ($this->app->hasModule('StoreFeedbackModule')) {
			$module = $this->app->getModule('StoreFeedbackModule');
			$feedback->http_referrer = $module->getSearchReferrer();
			$module->clearSearchReferrer();
		}

		$this->feedback->createdate = new SwatDate();
		$this->feedback->createdate->toUTC();

		$this->feedback->save();
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate('feedback?thank-you');
	}

	// }}}

	// build phase
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-account-payment-method-page.js',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
