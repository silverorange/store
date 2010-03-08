if (typeof Store == 'undefined') {
	var Store = {};
}

if (typeof Store.widget == 'undefined') {
	Store.widget = {};
}

/**
 * Initializes pagers on this page after the document has been loaded
 */
YAHOO.util.Event.onDOMReady(function ()
{
	var pagers = YAHOO.util.Dom.getElementsByClassName('pager');
	for (var i = 0; i < pagers.length; i++) {
		new Store.widget.Pager(pagers[i]);
	}
});

(function () {

	var Dom    = YAHOO.util.Dom;
	var Event  = YAHOO.util.Event;
	var Anim   = YAHOO.util.Anim;
	var Easing = YAHOO.util.Easing;

	/**
	 * Pager widget
	 *
	 * @param DOMElement container
	 */
	Store.widget.Pager = function(container)
	{
		this.container = container;

		var region = Dom.getRegion(this.container);
		var height = region.bottom - region.top - 1;

		Dom.setStyle(this.container, 'position',  'relative');
		Dom.setStyle(this.container, 'overflowY', 'hidden');
		Dom.setStyle(this.container, 'height',    height + 'px')

		if (!this.container.id) {
			Dom.generateId(this.container, 'pager_');
		}

		var pagerContentNodes = Dom.getElementsByClassName(
			'pager-content',
			'div',
			this.container
		);

		this.id            = this.container.id;
		this.pageContainer = pagerContentNodes[0];
		this.pagesById     = {};
		this.pages         = [];
		this.currentPage   = null;

		var randomStartPage = (Dom.hasClass(this.container, 'pager-random'));

		if (Dom.hasClass(this.container, 'pager-with-tabs')) {
			var pagerTabNodes = Dom.getElementsByClassName(
				'pager-tabs', 'div', this.container);

			this.tabs = pagerTabNodes[0];
		} else {
			this.tabs = null;
		}

		if (Dom.hasClass(this.container, 'pager-with-nav')) {
			this.drawNav();
		} else {
			this.nav = null;
		}

		// add pages
		var pageNodes = Dom.getChildrenBy(
			this.pageContainer,
			function (n) { return (n.nodeName == 'DIV'); }
		);

		if (this.tabs) {

			// initialize pages with tabs
			var tabNodes = Dom.getChildrenBy(
				this.tabs,
				function (n) { return (!Dom.hasClass(n, 'pager-not-tab')); }
			);

			var index = 0;
			for (var i = 0; i < pageNodes.length; i++) {
				if (i < tabNodes.length) {
					var tabNode = Dom.getFirstChildBy(
						tabNodes[i],
						function(n) { return (n.nodeName == 'A'); }
					);

					if (tabNode) {
						this.addPage(
							new Store.widget.Page(
								pageNodes[i],
								index,
								tabNode
							)
						);
						index++;
					}
				}
			}
		} else {
			// initialize pages without tabs
			for (var i = 0; i < pageNodes.length; i++) {
				this.addPage(new Store.widget.Page(pageNodes[i], i));
			}
		}

		// initialize current page
		if (this.pages.length > 0) {
			var page;

			if (randomStartPage) {
				page = this.getPseudoRandomPage();
			} else {
				var defaultPage = Dom.getFirstChildBy(
					this.pageContainer,
					function (n) { return Dom.hasClass(n, 'default-page') }
				);
				if (defaultPage) {
					var defaultId;
					if (defaultPage.id.substring(0, 5) == 'page_') {
						defaultId = defaultPage.id.substring(5);
					} else {
						defaultId = defaultPage.id;
					}
					page = this.pagesById[defaultId];
				} else {
					page = this.pages[0];
				}
			}

			this.setPage(page);
		}

		this.setInterval();
	};

	Store.widget.Pager.PAGE_DURATION = 0.25; // seconds
	Store.widget.Pager.PAGE_INTERVAL = 15.0; // seconds

	Store.widget.Pager.TEXT_PREV = 'Previous';
	Store.widget.Pager.TEXT_NEXT = 'Next';

	var _interval = null;

	var proto = Store.widget.Pager.prototype;

	proto.setInterval = function()
	{
		var that = this;
		_interval = setInterval(
			function ()
			{
				that.nextPageWithAnimation();
			},
			Store.widget.Pager.PAGE_INTERVAL * 1000
		);
	};

	proto.clearInterval = function()
	{
		if (_interval) {
			clearInterval(_interval);
		}
	}

	proto.getPseudoRandomPage = function()
	{
		var page = null;

		if (this.pages.length > 0) {
			var now = new Date();
			page = this.pages[now.getSeconds() % this.pages.length];
		}

		return page;
	};

	proto.prevPageWithAnimation = function()
	{
		var index = this.currentPage.index - 1;
		if (index < 0) {
			index = this.pages.length - 1;
		}

		this.setPageWithAnimation(this.pages[index]);
	};

	proto.nextPageWithAnimation = function()
	{
		var index = this.currentPage.index + 1;
		if (index >= this.pages.length) {
			index = 0;
		}

		this.setPageWithAnimation(this.pages[index]);
	};

	proto.drawNav = function()
	{
		// create previous link
		this.prev = document.createElement('a');
		this.prev.href = '#previous-page';
		YAHOO.util.Dom.addClass(this.prev, 'pager-prev');
		this.prev.appendChild(
			document.createTextNode(Store.widget.Pager.TEXT_PREV)
		);

		this.prevInsensitive = document.createElement('span');
		this.prevInsensitive.style.display = 'none';
		YAHOO.util.Dom.addClass(this.prevInsensitive, 'pager-prev-insensitive');
		this.prevInsensitive.appendChild(
			document.createTextNode(Store.widget.Pager.TEXT_PREV)
		);

		YAHOO.util.Event.on(this.prev, 'click',
			function (e)
			{
				YAHOO.util.Event.preventDefault(e);
				this.clearInterval();
				this.prevPageWithAnimation();
			},
			this, true);

		YAHOO.util.Event.on(this.prev, 'dblclick',
			function (e)
			{
				YAHOO.util.Event.preventDefault(e);
			},
			this, true);

		// create next link
		this.next = document.createElement('a');
		this.next.href = '#next-page';
		YAHOO.util.Dom.addClass(this.next, 'pager-next');
		this.next.appendChild(
			document.createTextNode(Store.widget.Pager.TEXT_NEXT)
		);

		this.nextInsensitive = document.createElement('span');
		this.nextInsensitive.style.display = 'none';
		YAHOO.util.Dom.addClass(this.nextInsensitive, 'pager-next-insensitive');
		this.nextInsensitive.appendChild(
			document.createTextNode(Store.widget.Pager.TEXT_NEXT)
		);

		YAHOO.util.Event.on(this.next, 'click',
			function (e)
			{
				YAHOO.util.Event.preventDefault(e);
				this.clearInterval();
				this.nextPageWithAnimation();
			},
			this, true);

		YAHOO.util.Event.on(this.next, 'dblclick',
			function (e)
			{
				YAHOO.util.Event.preventDefault(e);
			},
			this, true);

		// create navigation element
		this.nav = document.createElement('div');
		YAHOO.util.Dom.addClass(this.nav, 'pager-nav');
		this.nav.appendChild(this.prevInsensitive);
		this.nav.appendChild(this.prev);
		this.nav.appendChild(this.next);
		this.nav.appendChild(this.nextInsensitive);

		this.container.insertBefore(this.nav, this.page_container);
	};

	proto.updateNav = function()
	{
		var pageNumber = this.currentPage.index + 1;
		var pageCount  = this.pages.length;

		this.setPrevSensitivity(pageNumber != 1);
		this.setNextSensitivity(pageNumber != pageCount);
	};

	proto.setPrevSensitivity = function(sensitive)
	{
		if (this.prev) {
			if (sensitive) {
				this.prevInsensitive.style.display = 'none';
				this.prev.style.display = 'block';
			} else {
				this.prevInsensitive.style.display = 'block';
				this.prev.style.display = 'none';
			}
		}
	};

	proto.setNextSensitivity = function(sensitive)
	{
		if (this.next) {
			if (sensitive) {
				this.nextInsensitive.style.display = 'none';
				this.next.style.display = 'block';
			} else {
				this.nextInsensitive.style.display = 'block';
				this.next.style.display = 'none';
			}
		}
	};

	proto.addPage = function(page)
	{
		this.pagesById[page.id] = page;
		this.pages.push(page);
		if (page.tab) {
			Event.on(page.tab, 'click',
				function (e)
				{
					Event.preventDefault(e);
					this.clearInterval();
					this.setPageWithAnimation(page);
				},
				this, true);
		}
	};

	proto.update = function()
	{
		if (this.tabs) {
			this.updateTabs();
		}

		if (this.nav) {
			this.updateNav();
		}
	};

	proto.updateTabs = function()
	{
		var className = this.tabs.className;
		className = className.replace(/pager-selected-[\w-]+/g, '');
		className = className.replace(/^\s+|\s+$/g,'');
		this.tabs.className = className;

		this.currentPage.selectTab();
		Dom.addClass(this.tabs, 'pager-selected-' + this.currentPage.id);
	};

	proto.setPage = function(page)
	{
		if (this.currentPage !== page) {
			if (this.currentPage) {
				this.currentPage.deselectTab();
			}

			this.currentPage = page;
			this.update();
		}
	};

	proto.setPageWithAnimation = function(page)
	{
		if (this.currentPage !== page) {

			// deselect last selected page (not necessarily previous page)
			if (this.currentPage) {
				this.currentPage.deselectTab();
			}

			var zIndex = Dom.getStyle(this.currentPage.element, 'zIndex');
			zIndex = (!zIndex || zIndex == 'auto') ?
				1000 :
				parseInt(zIndex) + 1;

			// start opacity is zero
			Dom.setStyle(page.element, 'opacity', '0');
			Dom.setStyle(page.element, 'z-index', zIndex);

			// fade in page
			var anim = new Anim(
				page.element,
				{ opacity: { from: 0, to: 1 } },
				Store.widget.Pager.PAGE_DURATION,
				Easing.easeIn
			);

			// when animation is complete, reduce all z-indexes by one to
			// prevent them from ever getting greater than the tab z-index
			anim.onComplete.subscribe(
				function()
				{
					var index;
					for (var i = 0; i < this.pages.length; i++) {
						index = (this.pages.length - i + page.index) %
							this.pages.length;

						Dom.setStyle(
							this.pages[index].element,
							'z-index',
							zIndex - 1 - i
						);
					}
				},
				this,
				true
			);

			// when animation is complete, remove opacity so IE7+8 cleartype
			// rendering goes back to normal
			anim.onComplete.subscribe(
				function()
				{
					if (typeof page.element.style.filter != 'undefined') {
						// Don't remove filters for IE8 because Cleartype
						// rendering shifts font position in IE8. WTF.
						if (YAHOO.env.ua.ie < 8) {
							page.element.style.removeAttribute('filter');
						}
					}
				}
			);

			anim.animate();

			// always set current page
			this.currentPage = page;
			this.update();
		}
	};

	/**
	 * Page in a pager
	 *
	 * @param DOMElement element
	 * @param DOMElement tabElement
	 */
	Store.widget.Page = function(element, index, tabElement)
	{
		this.element = element;

		if (!this.element.id) {
			Dom.generateId(this.element, 'pager_page_');
		}

		this.index = index;

		if (tabElement) {
			this.tab = tabElement;
			// generate href to use for Google analytics virtual page view
			this.tab.href = '?link=' + this.element.id;
		} else {
			this.tab = null;
		}

		this.show();

		var parentRegion = Dom.getRegion(this.element.parentNode);
		var region       = Dom.getRegion(this.element);
		var relativeTop  = -(region.top - parentRegion.top);

		// position page at the top of the overflow container
		Dom.setStyle(this.element, 'position', 'relative');
		Dom.setStyle(this.element, 'top',      relativeTop + 'px');

		// set z-index in reverse order of index so first page gets displayed
		// on top
		Dom.setStyle(this.element, 'z-index', 1000 - this.index);

		// Turn off Cleartype for IE8, otherwise the fonts get messed up during
		// opacity animation.
		if (YAHOO.env.ua.ie == 8) {
			Dom.setStyle(this.element, 'opacity', 100);
		}

	};

	var proto = Store.widget.Page.prototype;

	proto.selectTab = function()
	{
		if (this.tab) {
			Dom.addClass(this.tab, 'selected');
		}
	};

	proto.deselectTab = function()
	{
		if (this.tab) {
			Dom.removeClass(this.tab, 'selected');
		}
	};

	proto.focusTab = function()
	{
		if (this.tab) {
			this.tab.focus();
		}
	};

	proto.hide = function()
	{
		this.element.style.display = 'none';
	};

	proto.show = function()
	{
		this.element.style.display = 'block';
	};

})();
