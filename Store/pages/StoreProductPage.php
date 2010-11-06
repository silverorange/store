<?php

require_once 'Numbers/Words.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatMessageDisplay.php';
require_once 'Swat/SwatYUI.php';
require_once 'Swat/SwatUI.php';
require_once 'Store/StoreItemsView.php';
require_once 'Store/pages/StorePage.php';
require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/dataobjects/StoreCategory.php';
require_once 'Store/dataobjects/StoreItemGroupWrapper.php';
require_once 'Store/dataobjects/StoreProductReview.php';
require_once 'Store/StoreProductSearchEngine.php';
require_once 'Store/StoreCartProcessor.php';
@include_once 'Services/Akismet2.php';

/**
 * A product page
 *
 * @package   Store
 * @copyright 2005-2010 silverorange
 */
class StoreProductPage extends StorePage
{
	// {{{ class constants

	const THANK_YOU_ID = 'thank-you';

	// }}}
	// {{{ public properties

	public $product_id;
	public $product = null;

	// }}}
	// {{{ protected properties

	protected $cart_processor;
	protected $items_view;
	protected $reviews_ui;
	protected $reviews_ui_xml = 'Store/pages/product-reviews.xml';
	protected $message_display;
	protected $item_removed = false;
	protected $items_added = array();
	protected $items_saved = array();
	protected $default_quantity = 0;

	/**
	 * @var StoreProductReview
	 */
	protected $review;

	/**
	 * @var SiteArticleWrapper
	 */
	protected $related_articles;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->cart_processor = StoreCartProcessor::get($this->app);

		$this->message_display = new SwatMessageDisplay();
		$this->message_display->id = 'cart_message_display';
		$this->message_display->init();

		if ($this->app->hasModule('StoreRecentModule'))
			$this->app->getModule('StoreRecentModule')->setExclusionId(
				'products', $this->product_id);

		$this->initProduct();
		$this->initItemsView();
	}

	// }}}
	// {{{ public function isVisibleInRegion()

	public function isVisibleInRegion(StoreRegion $region)
	{
		$key = 'StoreProductPage.isVisibleInRegion.'.$region->id.
			'.'.$this->product_id;

		$product = $this->app->getCacheValue($key, 'product');
		if ($product !== false)
			return ($product !== null);

		$sql = sprintf('select product from VisibleProductCache
			where product = %s and region = %s',
			$this->app->db->quote($this->product_id, 'integer'),
			$this->app->db->quote($region->id, 'integer'));

		$product = SwatDB::queryOne($this->app->db, $sql);
		$this->app->addCacheValue($product, $key, 'product');
		return ($product !== null);
	}

	// }}}
	// {{{ protected function initProduct()

	protected function initProduct()
	{
		$this->loadProduct($this->product_id);
	}

	// }}}
	// {{{ protected function initItemsView()

	protected function initItemsView()
	{
		$this->items_view = $this->getItemsView();

		if ($this->items_view instanceof StoreItemsView) {
			$this->items_view->setProduct($this->product);
			$this->items_view->setSource($this->source);
			$this->items_view->init();
		}
	}

	// }}}
	// {{{ protected function initReviews()

	protected function initReviews()
	{
		if ($this->product->reviewable) {
			$this->reviews_ui = new SwatUI();
			$this->reviews_ui->loadFromXML($this->reviews_ui_xml);

			$reviews = $this->product->getVisibleProductReviews(
				$this->app->getInstance(),
				$this->getMaxProductReviews());

			$review_ids = array();
			foreach ($reviews as $review) {
				$review_ids[] = $review->id;
			}

			// set view replicator ids
			$this->reviews_ui->getWidget('reviews_replicator')
				->replication_ids = $review_ids;

			$this->reviews_ui->getWidget('review')->app = $this->app;

			$this->reviews_ui->init();

			$this->initReviewsInternal();

			// set reviews on replicated views
			foreach ($reviews as $review) {
				$view = $this->reviews_ui
					->getWidget('reviews_replicator')
					->getWidget('review', $review->id);

				$view->review = $review;
			}

			if (count($reviews) == 0) {
				$disclosure =
					$this->reviews_ui->getWidget('product_review_disclosure');

				$disclosure->title =
					Store::_('Be the first to review this product');
			}
		}
	}

	// }}}
	// {{{ protected function initReviewsInternal()

	protected function initReviewsInternal()
	{
	}

	// }}}
	// {{{ protected function getItemsView()

	protected function getItemsView()
	{
		return new StoreItemsView();
	}

	// }}}
	// {{{ protected function loadProduct()

	protected function loadProduct($id)
	{
		$key = 'StoreProductPage.product.'.$this->app->getRegion()->id.$id;
		$product = $this->app->getCacheValue($key, 'product');
		if ($product !== false) {
			$this->product = $product;
			$this->product->setDatabase($this->app->db);
			$this->product->setRegion($this->app->getRegion());
			return;
		}

		$product_class = SwatDBClassMap::get('StoreProduct');
		$this->product = new $product_class();
		$this->product->setDatabase($this->app->db);
		$this->product->setRegion($this->app->getRegion());
		$this->product->load($id);

		$sql = 'select * from ItemGroup where product = %s';
		$sql = sprintf($sql,
			$this->app->db->quote($this->product->id, 'integer'));

		$wrapper = SwatDBClassMap::get('StoreItemGroupWrapper');
		$this->product->items->loadAllSubDataObjects('item_group',
			$this->app->db, $sql, $wrapper);

		$this->app->addCacheValue($this->product, $key, 'product');
	}

	// }}}
	// {{{ protected function getMaxProductReviews()

	protected function getMaxProductReviews()
	{
		return 3;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->message_display->process();
		$this->processProduct();
		$this->processReviewUi();
	}

	// }}}
	// {{{ protected function processProduct()

	protected function processProduct()
	{
		if ($this->items_view instanceof StoreItemsView) {
			$this->items_view->process();

			if ($this->items_view->hasMessage()) {
				$message = new SwatMessage(Store::_('There is a problem with '.
					'one or more of the items you requested.'), 'error');

				$message->secondary_content = Store::_('Please address the '.
					'fields highlighted below and re-submit the form.');

				$this->message_display->add($message);
			} else {
				$entries = $this->items_view->getCartEntries();
				foreach ($entries as $entry) {
					$status = $this->cart_processor->addEntryToCart($entry);
				}

				$this->app->cart->save();

				$message = $this->cart_processor->getUpdatedCartMessage();
				if ($message !== null) {
					$this->app->messages->add($message);
					$this->app->relocate('cart');
				}

				// add cart messages
				// TODO: what does this do anyway?
				/*
				$messages = $this->app->cart->checkout->getMessages();
				foreach ($messages as $message)
					$this->message_display->add($message);
				*/
			}
		}
	}

	// }}}
	// {{{ protected function processReview()

	protected function processReviewUi()
	{
		if ($this->reviews_ui instanceof SwatUI) {
			$form = $this->reviews_ui->getWidget('product_reviews_form');

			// wrap form processing in try/catch to catch bad input from
			// spambots
			try {
				$form->process();
			} catch (SwatInvalidSerializedDataException $e) {
				$this->app->replacePage('httperror');
				$this->app->getPage()->setStatus(400);
				return;
			}

			if ($form->isProcessed() && !$form->hasMessage()) {
				$this->processReview();

				$button = $this->reviews_ui->getWidget('product_review_add');
				if ($button->hasBeenClicked()) {
					$this->saveReview();

					$this->app->relocate($this->source.'?'.self::THANK_YOU_ID.
						'#submit_review');
				}
			}

			if ($form->hasMessage()) {
				$disclosure =
					$this->reviews_ui->getWidget('product_review_disclosure');

				$disclosure->open = true;
			}
		}
	}

	// }}}
	// {{{ protected function processReview()

	protected function processReview()
	{
		$now = new SwatDate();
		$now->toUTC();

		$fullname   = $this->reviews_ui->getWidget('product_review_fullname');
		$email      = $this->reviews_ui->getWidget('product_review_email');
		$bodytext   = $this->reviews_ui->getWidget('product_review_bodytext');

		if (isset($_SERVER['REMOTE_ADDR'])) {
			$ip_address = substr($_SERVER['REMOTE_ADDR'], 0, 15);
		} else {
			$ip_address = null;
		}

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
		} else {
			$user_agent = null;
		}

		$class_name = SwatDBClassMap::get('StoreProductReview');
		$this->review = new $class_name();

		$this->review->instance   = $this->app->getInstance();
		$this->review->fullname   = $fullname->value;
		$this->review->email      = $email->value;
		$this->review->bodytext   = $bodytext->value;
		$this->review->createdate = $now;
		$this->review->ip_address = $ip_address;
		$this->review->user_agent = $user_agent;
		$this->review->status     = SiteComment::STATUS_PENDING;
		$this->review->product    = $this->product;
	}

	// }}}
	// {{{ protected function saveReview()

	protected function saveReview()
	{
		$this->review->spam = $this->isReviewSpam($this->review);
		$this->review->setDatabase($this->app->db);
		$this->review->save();
	}

	// }}}
	// {{{ protected function isReviewSpam()

	protected function isReviewSpam(StoreProductReview $review)
	{
		$is_spam = false;

		if ($this->app->config->store->akismet_key !== null &&
			class_exists('Services_Akismet2')) {
			try {
				$akismet = new Services_Akismet2($this->app->getBaseHref(),
					$this->app->config->store->akismet_key);

				$akismet_review = new Services_Akismet2_Comment(
					array(
						'comment_author'       => $review->fullname,
						'comment_author_email' => $review->email,
						'comment_content'      => $review->bodytext,
						'permalink'            =>
							$this->app->getBaseHref().$this->source,
					)
				);

				$is_spam = $akismet->isSpam($akismet_review, true);
			} catch (Exception $e) {
			}
		}

		return $is_spam;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildProduct();
		$this->buildReviewsUi();

		$this->layout->startCapture('content');
		$this->message_display->display();
		$this->displayProduct();

		Swat::displayInlineJavaScript($this->getProductInlineJavaScript());
		if ($this->reviews_ui instanceof SwatUI) {
			Swat::displayInlineJavaScript($this->getReviewsInlineJavaScript());
		}

		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildProduct()

	protected function buildProduct()
	{
		$this->layout->data->title =
			SwatString::minimizeEntities($this->product->title);

		$this->layout->data->meta_description =
			SwatString::minimizeEntities(SwatString::stripXHTMLTags(
			SwatString::condense($this->product->bodytext, 400)));

		$image = $this->product->primary_image;
		if ($image !== null) {
			$this->layout->data->extra_headers.= sprintf(
				'<link rel="image_src" href="%s" />',
				$this->app->getBaseHref().$image->getUri('small'));
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if (!property_exists($this->layout, 'navbar'))
			return;

		$link = 'store';

		foreach ($this->path as $path_entry) {
			$link .= '/'.$path_entry->shortname;
			$this->layout->navbar->createEntry($path_entry->title, $link);
		}

		if ($this->product !== null)
			$this->layout->navbar->createEntry($this->product->title);
	}

	// }}}
	// {{{ protected function displayProduct()

	protected function displayProduct()
	{
		$this->displayImages();

		echo '<div id="product_contents">';

		$this->displayBodyText();

		$this->displayRelatedArticleLinks();

		$this->displayCartMessage();
		$this->displayItemMinimumQuantityGroupNotes();
		$this->displayItems();

		$this->displayProductCollections();
		$this->displayCollectionProducts();

		$this->displayRelatedProducts();

		$this->displayPopularProducts();

		$this->displayReviews();

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayBodyText()

	protected function displayBodyText()
	{
		$div = new SwatHtmlTag('div');
		$div->id = 'product_bodytext';
		$div->setContent(
			SwatString::toXHTML($this->product->bodytext), 'text/xml');

		$div->display();
	}

	// }}}
	// {{{ protected function displayCartMessage()

	protected function displayCartMessage()
	{
		echo '<div id="product_page_cart">';

		$message = $this->cart_processor->getProductCartMessage($this->product);
		if ($message !== null) {
			echo $message;
		}

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayItems()

	protected function displayItems()
	{
		if ($this->items_view instanceof StoreItemsView)
			$this->items_view->display();
	}

	// }}}
	// {{{ protected function displayItemMinimumQuantityGroupNotes()

	protected function displayItemMinimumQuantityGroupNotes()
	{
		// it's possible, but unlikely that a product can contain items
		// from two different groups. This handles that case.

		$count = 0;
		$groups = array();

		foreach ($this->product->items as $item) {
			$group = $item->getInternalValue('minimum_quantity_group');

			if ($item->isAvailableInRegion($this->app->getRegion())
				&& $group !== null) {

				$groups[$group][] = $item;
				$count++;
			}
		}

		if (count($groups) > 0) {
			echo '<div class="store-item-minimum-quantity-groups">';

			foreach ($groups as $items) {
				$this->displayItemMinimumQuantityGroupNote($items, $count);
			}

			echo '</div>';
		}
	}

	// }}}
	// {{{ protected function displayItemMinimumQuantityGroupNote()

	protected function displayItemMinimumQuantityGroupNote($items,
		$total_item_count)
	{
		$group = current($items)->minimum_quantity_group;
		$locale = SwatI18NLocale::get();
		$content = '';

		if (count($items) < $total_item_count) {
			$skus = array();
			foreach ($items as $item) {
				$skus[] = $item->sku;
			}

			$content.= sprintf(Store::ngettext('%s belongs to %s.',
				'%s are %s.', count($items)),
				SwatString::toList($skus),
				$group->getSearchLink());

			$content.= ' ';
		}

		// all of the product's items belong to the group
		if ($group->description != '')
			$content.= $group->description.' ';

		$content.= sprintf(Store::_(
			'You must purchase %sa minimum of %s %s%s in order to check out.'),
			'<strong>',
			$locale->formatNumber($group->minimum_quantity),
			$group->getSearchLink(),
			'</strong>');

		$p_tag = new SwatHtmlTag('p');
		$p_tag->class = 'store-item-minimum-quantity-group-note';
		$p_tag->setContent($content, 'text/xml');
		$p_tag->display();
	}

	// }}}
	// {{{ protected function getProductSearchEngine()

	protected function getProductSearchEngine($context = null)
	{
		return new StoreProductSearchEngine($this->app);
	}

	// }}}
	// {{{ protected function getProductInlineJavaScript()

	protected function getProductInlineJavaScript()
	{
		static $translations_displayed = false;

		$item_ids = array();
		foreach ($this->product->items as $item)
			if ($item->isEnabled())
				$item_ids[] = $item->id;

		$item_ids = "'".implode("', '", $item_ids)."'";

		$javascript = '';
		if (!$translations_displayed) {
			// TODO: some of these classes aren't correct
			$javascript.= sprintf(
				"StoreProductPage.enter_quantity_message = %s;\n",
					SwatString::quoteJavascriptString(
						Store::_('Please enter a quantity.')));

			$translations_displayed = true;
		}

		$path_entry = $this->getPath()->getLast();
		$category_id = ($path_entry instanceof SitePathEntry) ?
			$path_entry->id : 'null';

		$javascript.= sprintf("var product_page = new %s(%d, [%s], %d, %d);",
			$this->getProductJavaScriptClass(),
			$this->product->id,
			$item_ids,
			StoreCartEntry::SOURCE_PRODUCT_PAGE,
			$category_id);

		$lightbox = $this->getCartLightboxJavaScriptClass();
		if ($lightbox !== null) {
			$javascript.= sprintf('product_page.setCart(%s.getInstance());',
				$lightbox);
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function getProductJavaScriptClass()

	protected function getProductJavaScriptClass()
	{
		return 'StoreProductPage';
	}

	// }}}
	// {{{ protected function getCartLightboxJavaScriptClass()

	protected function getCartLightboxJavaScriptClass()
	{
		return 'StoreCartLightBox';
	}

	// }}}

	// build - related articles
	// {{{ protected function displayRelatedArticles()

	/**
	 * Displays related articles from the parent category on this product page
	 */
	protected function displayRelatedArticles()
	{
		$related_articles = $this->getRelatedArticles();
		if (count($related_articles) > 0) {
			$dl_tag = new SwatHtmlTag('dl');
			$dl_tag->id = 'related_articles';
			$dl_tag->open();

			$anchor_tag = new SwatHtmlTag('a');
			$dd_tag = new SwatHtmlTag('dd');
			$dt_tag = new SwatHtmlTag('dt');
			foreach ($related_articles as $article) {
				$dt_tag->open();

				$this->displayRelatedArticlesTitle();
				$anchor_tag->href = $article->path;
				$anchor_tag->setContent($article->title);
				$anchor_tag->display();

				$dt_tag->close();

				// spaces are non-breaking
				$anchor_tag->setContent('read more »');

				$bodytext = SwatString::condense($article->bodytext, null);

				// spaces are non-breaking
				$bodytext = SwatString::ellipsizeRight($bodytext, 200,
					' … '.$anchor_tag->toString());

				$dd_tag->setContent($bodytext, 'text/xml');

				$dd_tag->display();
			}

			$dl_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayRelatedArticlesAsUnorderedList()

	/**
	 * Displays related articles from the parent category on this product page
	 */
	protected function displayRelatedArticlesAsUnorderedList()
	{
		$related_articles = $this->getRelatedArticles();
		if (count($related_articles) > 0) {
			$ul_tag = new SwatHtmlTag('ul');
			$ul_tag->id = 'related_articles';
			$ul_tag->open();

			$anchor_tag = new SwatHtmlTag('a');
			$li_tag = new SwatHtmlTag('li');
			foreach ($related_articles as $article) {
				$li_tag->open();

				$anchor_tag->href = $article->path;
				$anchor_tag->setContent($article->title);
				$anchor_tag->display();

				$li_tag->close();
			}

			$ul_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayRelatedArticleLinks()

	/**
	 * Displays related articles links from the parent category on this product
	 * page
	 */
	protected function displayRelatedArticleLinks()
	{
		$related_articles = $this->getRelatedArticles();
		if (count($related_articles) > 0) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->id = 'related_article_links';
			$div_tag->open();

			$this->displayRelatedArticlesTitle();

			$anchor_tag = new SwatHtmlTag('a');
			$first = true;
			foreach ($related_articles as $article) {
				if ($first)
					$first = false;
				else
					echo ', ';

				$anchor_tag->href = $article->path;
				$anchor_tag->setContent($article->title);
				$anchor_tag->display();
			}

			$div_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayRelatedArticlesTitle()

	protected function displayRelatedArticlesTitle()
	{
		echo Store::_('Related Articles: ');
	}

	// }}}
	// {{{ protected function getRelatedArticles()

	/**
	 * Gets related articles from the product, and combines them with the
	 * related articles from the direct parent category of the product
	 * on this page, as well as any twigs.
	 */
	protected function getRelatedArticles()
	{
		if ($this->related_articles === null) {
			// product related articles
			$related_articles = array();
			foreach ($this->product->related_articles as $article)
				$related_articles[$article->id] = $article;

			// add category and twig related articles
			$last_entry = $this->path->getLast();
			$entries_to_relate = array();
			foreach ($this->path as $entry) {
				if ($entry->twig && $entry !== $last_entry)
					$entries_to_relate[] = $entry;
			}

			if ($last_entry !== null)
				$entries_to_relate[] = $last_entry;

			foreach ($entries_to_relate as $entry) {
				$category_class = SwatDBClassMap::get('StoreCategory');
				$category = new $category_class();
				$category->id = $entry->id;
				$category->setDatabase($this->app->db);
				$category->setRegion($this->app->getRegion());

				foreach ($category->related_articles as $article)
					$related_articles[$article->id] = $article;
			}

			$this->related_articles = $related_articles;
		}
		return $this->related_articles;
	}

	// }}}

	// build - popular products
	// {{{ protected function displayPopularProducts()

	protected function displayPopularProducts()
	{
		$popular_products = $this->getPopularProducts();
		if (count($popular_products) == 0)
			return;

		$div = new SwatHtmlTag('div');
		$div->id = 'popular_products';

		$header_tag = new SwatHtmlTag('h4');
		$header_tag->setContent(
			sprintf(Store::_('Customers who bought %s also bought…'),
				$this->product->title));

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'store-product-list clearfix';

		$li_tag = new SwatHtmlTag('li');
		$li_tag->class = 'store-product-icon';

		$div->open();
		$header_tag->display();
		$ul_tag->open();

		foreach ($popular_products as $product) {
			$li_tag->open();
			$this->displayPopularProduct($product);
			$li_tag->close();
		}

		$ul_tag->close();
		$div->close();
	}

	// }}}
	// {{{ protected function displayPopularProduct()

	protected function displayPopularProduct(StoreProduct $product)
	{
		$path = 'store/'.$product->path.'?link=popular-product';
		$product->displayAsIcon($path, 'pinky');
	}

	// }}}
	// {{{ protected function getPopularProducts()

	protected function getPopularProducts()
	{
		$engine = $this->getProductSearchEngine('popular-products');
		$engine->popular_only = true;
		$engine->available_only = true;
		$engine->popular_source_product = $this->product;
		$engine->popular_threshold = 2;
		$engine->addOrderByField('ProductPopularProductBinding.order_count
			desc');

		$products = $engine->search(3);

		return $products;
	}

	// }}}

	// build - related products
	// {{{ protected function displayRelatedProducts()

	protected function displayRelatedProducts()
	{
		$engine = $this->getProductSearchEngine('related-products');
		$engine->related_source_product = $this->product;
		$engine->addOrderByField('is_available desc');
		$products = $engine->search();

		if (count($products) == 0)
			return;

		$div = new SwatHtmlTag('div');
		$div->id = 'related_products';

		$header_tag = new SwatHtmlTag('h4');
		$header_tag->setContent(Store::_('You might also be interested in…'));

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'store-product-list clearfix';

		$li_tag = new SwatHtmlTag('li');
		$li_tag->class = 'store-product-icon';

		$div->open();
		$header_tag->display();
		$ul_tag->open();

		foreach ($products as $product) {
			$li_tag->open();
			$this->displayRelatedProduct($product);
			$li_tag->close();
		}

		$ul_tag->close();
		$div->close();
	}

	// }}}
	// {{{ protected function displayRelatedProduct()

	protected function displayRelatedProduct(StoreProduct $product)
	{
		$path = 'store/'.$product->path.'?link=related-product';
		$product->displayAsIcon($path, 'pinky');
	}

	// }}}

	// build - collections
	// {{{ protected function displayProductCollections()

	/**
	 * Displays all collections this product belongs to.
	 */
	protected function displayProductCollections()
	{
		$engine = $this->getProductSearchEngine('product-collections');
		$engine->collection_member_product = $this->product;
		$engine->addOrderByField('is_available desc');
		$products = $engine->search();

		if (count($products) == 0)
			return;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'product_collection';
		$div_tag->open();

		$title =  SwatString::minimizeEntities(Store::ngettext(
			'This item is also available in a collection: ',
			'This item is also available in collections: ',
			count($products)));

		$header_tag = new SwatHtmlTag('h4');
		$header_tag->setContent($title);
		$header_tag->display();

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'store-product-list clearfix';
		$ul_tag->open();

		$li_tag = new SwatHtmlTag('li');
		$li_tag->class = 'store-product-icon';

		foreach ($products as $product) {
			$li_tag->open();
			$this->displayProductCollection($product);
			$li_tag->close();
			echo ' ';
		}

		$ul_tag->close();
		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayProductCollection()

	protected function displayProductCollection(StoreProduct $product)
	{
		$path = 'store/'.$product->path.'?link=product-collection';
		$product->displayAsIcon($path, 'pinky');
	}

	// }}}
	// {{{ protected function displayCollectionProducts()

	/**
	 * Displays all member products of this collection.
	 */
	protected function displayCollectionProducts()
	{
		$engine = $this->getProductSearchEngine('collection-products');
		$engine->collection_source_product = $this->product;
		$engine->addOrderByField('is_available desc');
		$products = $engine->search();

		if (count($products) == 0)
			return;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'collection_products';
		$div_tag->open();

		$h4_tag = new SwatHtmlTag('h4');
		$h4_tag->setContent(Store::ngettext(
			'This collection contains the following item: ',
			'This collection contains the following items: ',
			count($products)));

		$h4_tag->display();

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'store-product-list clearfix';

		$li_tag = new SwatHtmlTag('li');
		$li_tag->class = 'store-product-icon';

		$ul_tag->open();

		foreach ($products as $product) {
			$li_tag->open();
			$this->displayCollectionProduct($product);
			$li_tag->close();
			echo ' ';
		}

		$ul_tag->close();
		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayCollectionProduct()

	protected function displayCollectionProduct(StoreProduct $product)
	{
		$path = 'store/'.$product->path.'?link=collection-product';
		$product->displayAsIcon($path, 'pinky');
	}

	// }}}

	// build - reviews
	// {{{ protected function buildReviewsUi()

	protected function buildReviewsUi()
	{
		if ($this->reviews_ui instanceof SwatUI) {
			$ui             = $this->reviews_ui;
			$form           = $ui->getWidget('product_reviews_form');
			$show_thank_you = array_key_exists(self::THANK_YOU_ID, $_GET);

			$form->action = $this->source.'#submit_review';

			if ($show_thank_you) {
				$message = new SwatMessage(
					Store::_('Your comment has been submitted.'));

				$message->secondary_content =
					Store::_('Your comment will be published after being '.
						'approved by the site moderator.');

				$this->reviews_ui->getWidget('product_review_message_display')
					->add($message, SwatMessageDisplay::DISMISS_OFF);
			}

			$this->buildReviewPreview();
		}
	}

	// }}}
	// {{{ protected function buildReviewPreview()

	protected function buildReviewPreview()
	{
		if ($this->review instanceof StoreProductReview &&
			$this->reviews_ui->getWidget('product_review_preview')
				->hasBeenClicked()) {

			$button_tag = new SwatHtmlTag('input');
			$button_tag->type = 'submit';
			$button_tag->name = 'product_review_add';
			$button_tag->value = Store::_('Add Comment');

			$message = new SwatMessage(
				Store::_('Your comment has not yet been published.'));

			$message->secondary_content = sprintf(Store::_(
				'Please review your comment and press the '.
				'<em>Add Comment</em> button when it’s ready to '.
				'publish. %s'), $button_tag);

			$message->content_type = 'text/xml';

			$message_display =
				$this->reviews_ui->getWidget('product_review_message_display');

			$message_display->add($message, SwatMessageDisplay::DISMISS_OFF);

			$review_preview = $this->reviews_ui->getWidget('review_preview');
			$review_preview->review = $this->review;
			$review_preview->app    = $this->app;
			$container = $this->reviews_ui->getWidget(
				'product_review_preview_container');

			$container->visible = true;
			$this->reviews_ui->getWidget('product_review_disclosure')->open =
				true;
		}
	}

	// }}}
	// {{{ protected function displayReviews()

	protected function displayReviews()
	{
		if ($this->reviews_ui instanceof SwatUI) {
			echo '<div id="submit_review"></div>';

			$this->reviews_ui->display();
		}
	}

	// }}}
	// {{{ protected function getReviewsInlineJavaScript()

	protected function getReviewsInlineJavaScript()
	{
		$locale = SwatI18NLocale::get();

		$review_count = $this->product->getVisibleProductReviewCount(
			$this->app->getInstance());

		$message = sprintf(Store::_('Read All %s Comments'),
			$locale->formatNumber($review_count));

		$message       = SwatString::quoteJavaScriptString($message);
		$replicator_id = "'reviews_replicator'";
		$disclosure_id = "'product_review_disclosure'";

		$show_more = ($review_count > $this->getMaxProductReviews()) ?
			'true' : 'false';

		return sprintf("var product_review_page = ".
			"new StoreProductReviewPage(%s, %s, %s, %s, %s, %s);",
			$this->product->id,
			$this->getMaxProductReviews(),
			$replicator_id,
			$disclosure_id,
			$message,
			$show_more);
	}

	// }}}

	// build - images
	// {{{ protected function displayImages()

	protected function displayImages()
	{
		if ($this->product->primary_image !== null) {
			echo '<div id="product_images">';
			$this->displayImage();

			if (count($this->product->images) > 1)
				$this->displaySecondaryImages();

			echo '</div>';

			Swat::displayInlineJavaScript($this->getImageInlineJavaScript());
		}

	}

	// }}}
	// {{{ protected function displayImage()

	protected function displayImage()
	{
		$image = $this->product->primary_image;
		$div = new SwatHtmlTag('div');
		$div->id = 'product_image';

		$div->open();

		$link_to_large = $this->isImageLinked();

		if ($link_to_large) {
			$anchor = new SwatHtmlTag('a');
			$anchor->id = 'product_image_link';
			$anchor->href = $this->source.'/image';
			$anchor->class = 'large-image-wrapper';
			$anchor->title = Store::_('View Larger Image');
			$anchor->open();
		} else {
			echo '<span class="large-image-wrapper">';
		}

		$img_tag = $image->getImgTag('small');

		if ($img_tag->alt == '') {
			$img_tag->alt = sprintf(
				Store::_('Image of %s'),
				$this->product->title);
		}

		echo '<span class="large-image">';
		$img_tag->display();
		echo '</span>';

		if ($image->title != '') {
			echo '<span class="large-image-title">';
			echo SwatString::minimizeEntities($image->title);
			echo '</span> ';
		}

		if ($link_to_large) {
			echo '<span>', Store::_('View Larger Image'), '</span>';
			$anchor->close();
		} else {
			echo '</span>';
		}

		$div->close();
	}

	// }}}
	// {{{ protected function displaySecondaryImages()

	protected function displaySecondaryImages()
	{
		echo '<ul id="product_secondary_images" class="clearfix">';

		foreach ($this->product->images as $image)
			if ($this->product->primary_image->id !== $image->id)
				$this->displaySecondaryImage($image);

		echo '</ul>';
	}

	// }}}
	// {{{ protected function displaySecondaryImage()

	protected function displaySecondaryImage($image)
	{
		$li_tag = new SwatHtmlTag('li');
		$img_tag = new SwatHtmlTag('img');
		$img_tag = $image->getImgTag('pinky');

		if ($img_tag->alt == '')
			$img_tag->alt = sprintf(Store::_('Additional Image of %s'),
				$this->product->title);

		$anchor = new SwatHtmlTag('a');
		$anchor->href = sprintf('%s/image%s', $this->source, $image->id);
		$anchor->title = Store::_('View Larger Image');

		$li_tag->open();
		$anchor->open();
		$img_tag->display();

		if ($image->title != '') {
			echo '<span class="image-title">';
			echo SwatString::minimizeEntities($image->title);
			echo '</span> ';
		}

		$anchor->close();
		$li_tag->close();
	}

	// }}}
	// {{{ protected function isImageLinked()

	protected function isImageLinked($large_ratio_limit = 20)
	{
		$is_linked = false;

		if (count($this->product->images) > 1) {
			$is_linked = true;
		} else {
			$small_width = $this->product->primary_image->getWidth('small');
			$large_width = $this->product->primary_image->getWidth('large');

			if ($small_width > 0) {
				$percentage_larger = (($large_width / $small_width) - 1) * 100;
			} else {
				$percentage_larger = 100;
			}

			$is_linked = ($percentage_larger >= $large_ratio_limit);
		}

		return $is_linked;
	}

	// }}}
	// {{{ protected function getImageInlineJavaScript()

	protected function getImageInlineJavaScript()
	{
		static $shown = false;

		if (!$shown) {
			$javascript = $this->getInlineJavaScriptTranslations();
			$shown = true;
		} else {
			$javascript = '';
		}

		$data = array(
			'product'   => array(
				'id'    => $this->product->id,
				'title' => $this->product->title,
			),
			'images'    => array(),
		);

		$key = 0;
		foreach ($this->product->images as $image) {
			$datum = array(
				'id'           => $image->id,
				'title'        => $image->title,
				'description'  => $image->description,
				'large_width'  => $image->getWidth('large'),
				'large_height' => $image->getHeight('large'),
				'large_uri'    => $image->getUri('large'),
				'pinky_width'  => $image->getWidth('pinky'),
				'pinky_height' => $image->getHeight('pinky'),
				'pinky_uri'    => $image->getUri('pinky'),
			);

			// put primary image first
			if ($this->product->primary_image->id === $image->id) {
				$data['images'][0] = $datum;
			} else {
				$data['images'][$key + 1] = $datum;
			}

			$key++;
		}

		// order and strip array keys from PHP so serialization to JSON
		// results is a JSON array instead of a key-value hash.
		ksort($data['images']);
		$data['images'] = array_values($data['images']);

		$javascript.= sprintf(
			'var product_image_display = new StoreProductImageDisplay(%s);',
			json_encode($data));

		return $javascript;
	}

	// }}}
	// {{{ protected function getImageInlineJavaScriptTranslations()

	protected function getInlineJavaScriptTranslations()
	{
		$close_text  = Store::_('Close');
		return sprintf(
			"StoreProductImageDisplay.close_text = %s;\n",
			SwatString::quoteJavaScriptString($close_text));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$yui = new SwatYUI(array('event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/swat/javascript/swat-z-index-manager.js',
			Swat::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-product-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-product-review-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-product-image-display.js',
			Store::PACKAGE_ID));

		if ($this->items_view instanceof StoreItemsView) {
			$this->layout->addHtmlHeadEntrySet(
				$this->items_view->getHtmlHeadEntrySet());
		}

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/swat/styles/swat-message.css',
			Swat::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/swat/styles/swat-message-display.css',
			Swat::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-product-page.css',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-product-image-display.css',
			Store::PACKAGE_ID));

		// TODO: only include this if there are cart items on this page
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-cart.css', Store::PACKAGE_ID));

		if ($this->message_display !== null)
			$this->layout->addHtmlHeadEntrySet(
				$this->message_display->getHtmlHeadEntrySet());

		if ($this->reviews_ui instanceof SwatUI) {
			require_once 'XML/RPCAjax.php';

			$this->layout->addHtmlHeadEntrySet(
				XML_RPCAjax::getHtmlHeadEntrySet());

			$this->layout->addHtmlHeadEntrySet(
				$this->reviews_ui->getRoot()->getHtmlHeadEntrySet());
		}
	}

	// }}}
}

?>
