<?
/**
 * @package Store
 * @copyright silverorange 2005
 */
require_once('Swat/SwatPage.php');

abstract class StorePage extends SwatPage
{

	public $found = false;

	protected $source = array();

	public function setSource($source)
	{
		$this->source = $source;
	}

	public function init()
	{
		$this->initInternal();
	}

	public function process()
	{
		$this->processInternal();
	}

	public function build()
	{
		$this->buildInternal();
	}

	protected function initInternal()
	{
	}

	protected function processInternal()
	{
	}

	protected function buildInternal()
	{
	}
}

?>
