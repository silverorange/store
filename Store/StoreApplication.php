<?
require_once('Swat/SwatApplication.php');
require_once('Store/exceptions/StoreNotFoundException.php');
require_once('MDB2.php');

/**
 * Web application class for a store
 *
 * @package Store
 * @copyright silverorange 2004
 */
abstract class StoreApplication extends SwatApplication
{
	// {{{ public properties

	public $db;

	public $exception_page_source = 'exception';
	// }}}
	// {{{ public function init()

	/**
	 * Initialize the application.
	 */
	public function init()
	{
		$this->initBaseHref(3);
		$this->initDatabase();

		try {
			$this->initPage();
		}
		catch (StoreException $e) {
			$this->replacePage($this->exception_page_source);
			$this->page->setException($e);
			$this->initPage();
		}
	}

	// }}}
	// {{{ public function run()

	/**
	 * Run the application.
	 */
	public function run()
	{
		try {
			$this->getPage()->process();
			$this->getPage()->build();
		}
		catch (StoreException $e) {
			$this->replacePage($this->exception_page_source);
			$this->page->setException($e);
			$this->page->build();
		}

		$this->getPage()->layout->display();
	}

	// }}}
    // {{{ abstract protected function getDSN()

	abstract protected function getDSN();

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Get the page object.
	 * Uses the $_GET variables to decide which page subclass to instantiate.
	 * @return SwatPage A subclass of SwatPage is returned.
	 */
	public function resolvePage()
	{
		$source = self::initVar('source');
		
		$page = $this->instantiatePage($source);
		$source_exp = explode('/', $source);
		$page->setSource($source_exp);

		return $page;
	}

	// }}}
	// {{{ abstract protected function instantiatePage()

	/**
	 * Instantiates a page object.
	 * @return SwatPage A subclass of SwatPage is returned.
	 */
	abstract protected function instantiatePage($source);

	// }}}
	// {{{ private function initDatabase()

	private function initDatabase()
	{
		// TODO: change to array /form of DSN and move parts to a secure include file.
		$dsn = $this->getDSN();
		$this->db = MDB2::connect($dsn);
		$this->db->options['debug'] = true;

		if (MDB2::isError($this->db))
			throw new Exception('Unable to connect to database.');
	}

	// }}}
	// {{{ public function replacePage()

	/**
	 * Replace the page object
	 *
	 * This method can be used to load another page to replace the current 
	 * page. For example, this is used to load a confirmation page when 
	 * processing an admin index page.
	 */
	public function replacePage($source)
	{
		$new_page = $this->instantiatePage($source);
		$this->setPage($new_page);
	}

	// }}}
}
?>
