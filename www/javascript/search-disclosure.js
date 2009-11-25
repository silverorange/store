function StoreSearchDisclosure(id, open, entry, options)
{
	this.search_controls = document.getElementById(id + '_search_controls');
	this.fade_animation  = null;
	this.entry           = entry;
	this.initial_open    = open;

	this.title           = (options.title)        ? options.title        : '';
	this.keywords_id     = (options.keywords_id)  ? options.keywords_id  : '';
	this.panel_height    = (options.panel_height) ? options.panel_height : 13;
	this.panel_units     = (options.panel_units)  ? options.panel_units  : 'em';
	this.xml_rpc_server  = options.xml_rpc_server;

	this.loading_image   = (options.loading_image) ?
		options.loading_image :
		'packages/swat/images/swat-button-throbber.gif';

	this.custom_query_string = null;

	StoreSearchDisclosure.superclass.constructor.call(this, id, open);
	YAHOO.util.Dom.removeClass(this.div.firstChild, 'no-js');
}

/**
 * Preload images
 */
StoreSearchDisclosure.down_image = new Image();
StoreSearchDisclosure.down_image.src =
	'packages/store/images/search-disclosure-arrow-down.png';

StoreSearchDisclosure.up_image = new Image();
StoreSearchDisclosure.up_image.src =
	'packages/store/images/search-disclosure-arrow-up.png';

YAHOO.lang.extend(StoreSearchDisclosure, SwatDisclosure, {

init: function()
{
	if (this.initial_open || this.xml_rpc_server === null) {
		this.loading_container = null;
	} else {
		this.drawLoadingContainer();
	}

	/*
	 * Workaround for case when the disclosure is initialized open and the
	 * form inside the disclosure is processed. In this case, the keywords
	 * field could be populated with search data. We pull up the keywords from
	 * the form. Later on during initialization, the keywords are pushed down.
	 * Pulling them up here prevents losing the search keywords on the form.
	 */
	if (this.initial_open) {
		this.pullUpKeywords();
	} else if (this.xml_rpc_server === null) {
		this.pushDownKeywords();
	}

	this.search_input_elements =
		this.search_controls.getElementsByTagName('input');

	StoreSearchDisclosure.superclass.init.call(this);
},

getSpan: function()
{
	return document.getElementById(this.id + '_span');
},

getAnimateDiv: function()
{
	return document.getElementById(this.id + '_container').firstChild;
},

drawDisclosureLink: function()
{
	var span = this.getSpan();

	this.anchor = document.createElement('a');
	this.anchor.href = '#';

	if (this.title.length > 0) {
		this.anchor.appendChild(document.createTextNode(this.title));
	} else {
		this.img = document.createElement('img');
		this.img.src = StoreSearchDisclosure.down_image.src;
		this.anchor.appendChild(this.img);
	}

	if (this.opened)
		YAHOO.util.Dom.addClass(this.anchor, 'swat-disclosure-anchor-opened');
	else
		YAHOO.util.Dom.addClass(this.anchor, 'swat-disclosure-anchor-closed');

	YAHOO.util.Event.addListener(this.anchor, 'click',
		function(e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.toggle();
		}, this, true);

	span.parentNode.replaceChild(this.anchor, span);
},

open: function()
{
	if (this.semaphore)
		return;

	if (this.img) {
		this.img.src = StoreSearchDisclosure.up_image.src;
	}

	if (this.loading_container) {
		this.loadSearchPanel();
	}

	StoreSearchDisclosure.superclass.open.call(this);
	this.closeSearchControls();
},

close: function()
{
	if (this.img) {
		this.img.src = StoreSearchDisclosure.down_image.src;
	}

	StoreSearchDisclosure.superclass.close.call(this);
	this.openSearchControls();
},

openWithAnimation: function()
{
	if (this.semaphore)
		return;


	if (this.loading_container) {
		this.loadSearchPanel();
	}

	var time = 0.5;

	if (this.img) {
		this.img.src = StoreSearchDisclosure.up_image.src;
	}

	YAHOO.util.Dom.removeClass(this.div, 'swat-disclosure-control-closed');
	YAHOO.util.Dom.addClass(this.div, 'swat-disclosure-control-opened');

	YAHOO.util.Dom.removeClass(this.anchor, 'swat-disclosure-anchor-closed');
	YAHOO.util.Dom.addClass(this.anchor, 'swat-disclosure-anchor-opened');

	this.animate_div.style.height = '0';
	this.animate_div.style.overflow = 'hidden';
	this.animate_div.style.visibility = 'visible';
	this.animate_div.style.position = 'relative';
	this.animate_div.parentNode.style.overflow = 'visible';
	this.animate_div.firstChild.style.position = 'relative';
	this.animate_div.firstChild.style.height = this.panel_height +
		this.panel_units;

	var attributes = { height: {
		to:   this.panel_height,
		from: 0,
		unit: this.panel_units
	}};

	var animation = new YAHOO.util.Anim(this.animate_div, attributes, time,
		YAHOO.util.Easing.easeOut);

	var attributes = { top: {
		to:   0,
		from: -this.panel_height,
		unit: this.panel_units
	}};

	var slide_animation = new YAHOO.util.Anim(this.animate_div.firstChild,
		attributes, time,
		YAHOO.util.Easing.easeOut);

	this.semaphore = true;
	animation.onComplete.subscribe(this.handleOpen, this, true);
	animation.animate();
	slide_animation.animate();

	this.input.value = 'opened';
	this.opened = true;

	this.closeSearchControlsWithAnimation();
},

closeWithAnimation: function()
{
	if (this.semaphore)
		return;

	var time = 0.25

	if (this.img) {
		this.img.src = StoreSearchDisclosure.down_image.src;
	}

	YAHOO.util.Dom.removeClass(this.anchor, 'swat-disclosure-anchor-opened');
	YAHOO.util.Dom.addClass(this.anchor, 'swat-disclosure-anchor-closed');

	this.animate_div.firstChild.style.position = 'relative';
	this.animate_div.style.overflow = 'hidden';
	this.animate_div.style.height = 'auto';
	this.animate_div.style.position = 'relative';

	var attributes = { height: {
		to:   0,
		from: this.panel_height,
		unit: this.panel_units
	}};

	var animation = new YAHOO.util.Anim(this.animate_div, attributes, time,
		YAHOO.util.Easing.easeOut);

	var attributes = { top: {
		to:   -this.panel_height,
		from: 0,
		unit: this.panel_units
	}};

	var slide_animation = new YAHOO.util.Anim(this.animate_div.firstChild,
		attributes, time,
		YAHOO.util.Easing.easeOut);

	this.semaphore = true;
	animation.onComplete.subscribe(this.handleClose, this, true);
	animation.animate();
	slide_animation.animate();

	this.input.value = 'closed';
	this.opened = false;

	this.openSearchControlsWithAnimation();
},

handleOpen: function()
{
	this.semaphore = false;
}

});

StoreSearchDisclosure.prototype.drawLoadingContainer = function()
{
	this.loading_container =
		document.getElementById(this.id + '_loading_container');

	if (this.panel_units == 'px') {
		var top_padding = Math.round((this.panel_height - 16) / 2);
		var height      = this.panel_height - top_padding;

		this.loading_container.style.padding = top_padding + 'px 0 0 0';
		this.loading_container.style.height  = height + 'px';
	}

	var image = document.createElement('img');
	image.src = this.loading_image;

	this.loading_container.appendChild(image);
}

StoreSearchDisclosure.prototype.loadSearchPanel = function()
{
	var content = document.getElementById(this.id + '_content');
	if (content) {
		this.loading_container.parentNode.innerHTML = content.innerHTML;
	} else {
		var client = new XML_RPC_Client(this.xml_rpc_server);
		var that = this;

		var callback = function(response)
		{
			that.loading_container.parentNode.innerHTML = response;
			that.loading_container = null;
			that.pushDownKeywords();
		};

		var query = location.search;
		if (query.length > 0)
			query = query.substr(1);

		if (this.custom_query_string) {
			if (query.length)
				query += '&';

			query += this.custom_query_string;
		}

		var uri = location.href;

		client.callProcedure('getContent', callback,
			[query, uri], ['string', 'string']);
	}
}

/**
 * Pushes search entry value down into keywords field
 */
StoreSearchDisclosure.prototype.pushDownKeywords = function()
{
	var keywords = this.getKeywords();

	// only push down keywords if a keyword entry exists in the main UI
	if (keywords) {
		if (this.entry.isLabelTextShown()) {

			// push down empty state of search entry
			if (keywords._search_entry) {
				if (!keywords._search_entry.isLabelTextShown()) {
					// push down keywords value to main UI, push saved value,
					// not label
					keywords.value = this.entry.input_value;

					// push down label state
					keywords._search_entry.showLabelText();
				}
			} else {
				// push down keywords value to main UI, push saved value, not
				// label
				keywords.value = this.entry.input_value;
			}

		} else {

			// if the main UI uses a search entry, hide its label text before
			// pushing the keywords value down
			if (keywords._search_entry) {
				keywords._search_entry.hideLabelText();
			}

			// push down keywords value to main UI
			keywords.value = this.entry.input.value;
		}
	}
}

/**
 * Pulls keywords field value up into search entry value
 */
StoreSearchDisclosure.prototype.pullUpKeywords = function()
{
	var keywords = this.getKeywords();

	// only pull up keywords is a keyword entry exists in the main UI
	if (keywords) {
		if (keywords._search_entry &&
			keywords._search_entry.isLabelTextShown()) {

			if (!this.entry.isLabelTextShown()) {
				// pull up saved value, not label value
				this.entry.input.value = keywords._search_entry.input_value;

				// pull up label state
				this.entry.showLabelText();
			}

		} else {

			// hide label before pulling up keywords value
			this.entry.hideLabelText();

			// pull up keywords value to search entry
			this.entry.input.value = keywords.value;

		}
	}
}

StoreSearchDisclosure.prototype.getKeywords = function()
{
	var keywords = document.getElementById(this.keywords_id);

	// Workaround for IE. The madness will never end.
	if (keywords && keywords.id !== this.keywords_id) {
		keywords = null;
		var input_tags = this.animate_div.getElementsByTagName('input');
		for (var i = 0; i < input_tags.length; i++) {
			if (input_tags[i].type == 'text' &&
				input_tags[i].id == this.keywords_id) {
				keywords = input_tags[i];
				break;
			}
		}
	}

	return keywords;
}

StoreSearchDisclosure.prototype.closeSearchControls = function()
{
	this.pushDownKeywords();
	this.search_controls.style.display = 'none';
	YAHOO.util.Dom.setStyle(this.search_controls, 'opacity', 0);
}

StoreSearchDisclosure.prototype.openSearchControls = function()
{
	this.pullUpKeywords();
	this.search_controls.style.display = 'inline';
}

StoreSearchDisclosure.prototype.closeSearchControlsWithAnimation =
	function()
{
	for (var i = 0; i < this.search_input_elements.length; i++)
		this.search_input_elements[i].disabled = true;

	this.pushDownKeywords();

	// opacities are not quite 1.0 because of a weird Firefox on OS X
	// font-rendering issue.
	if (this.fade_animation && this.fade_animation.isAnimated()) {
		var from = this.fade_animation.getAttribute('opacity');
		this.fade_animation.stop();
	} else {
		var from = 0.999999;
	}

	this.fade_animation = new YAHOO.util.Anim(this.search_controls,
		{ opacity: { from: from, to: 0 } }, 0.5, YAHOO.util.Easing.easeOut);

	var that = this;
	this.fade_animation.onComplete.subscribe(
		function() { that.search_controls.style.display = 'none'; });

	this.fade_animation.animate();
}

StoreSearchDisclosure.prototype.openSearchControlsWithAnimation =
	function()
{

	for (var i = 0; i < this.search_input_elements.length; i++)
		this.search_input_elements[i].disabled = false;

	this.pullUpKeywords();

	if (this.fade_animation && this.fade_animation.isAnimated()) {
		var from = this.fade_animation.getAttribute('opacity');
		this.fade_animation.onComplete.unsubscribeAll();
		this.fade_animation.stop();
	} else {
		this.search_controls.style.display = 'inline';
		var from = 0;
	}

	// opacities are not quite 1.0 because of a weird Firefox on OS X
	// font-rendering issue.
	this.fade_animation = new YAHOO.util.Anim(this.search_controls,
		{ opacity: { from: from, to: 0.999999 } }, 0.5,
		YAHOO.util.Easing.easeOut);

	this.fade_animation.animate();
}
