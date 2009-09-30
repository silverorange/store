<?php

require_once 'Blorg/views/BlorgPostView.php';

/**
 * @package   Store
 * @copyright 2008 silverorange
 */
class StoreBlorgPostSearchView extends BlorgPostView
{
	// {{{ protected function define()

	protected function define()
	{
		parent::define();
		$this->microblog_length        = 100;
		$this->bodytext_summary_length = 100;
	}

	// }}}
}

?>
