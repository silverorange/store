<?
require_once('Swat/SwatApplication.php');
require_once('Store/StoreApplicationDatabaseModule.php');
require_once('Store/StoreApplicationSessionModule.php');
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
    // {{{ public function __construct()

    /**
     * Creates a new application object
     *
     * @param string $id a unique identifier for this application.
     */
    public function __construct($id)
    {
		parent::__construct($id);

		$this->addModule(new StoreApplicationSessionModule($this));
		$this->addModule(new StoreApplicationDatabaseModule($this));

		// set up convenience references
		$this->session = $this->modules['StoreApplicationSessionModule'];
		$this->database = $this->modules['StoreApplicationDatabaseModule'];
	}

    // }}}
	// {{{ public function init()

	/**
	 * Initialize the application.
	 */
	public function init()
	{
		$this->initBaseHref(3);
		$this->initModules();

		// set up convenience references
		$this->db = $this->database->mdb2;

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
