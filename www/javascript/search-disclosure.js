function StoreSearchDisclosure(id, open, entry, keywords_id, panel_height)
{
	this.search_controls = document.getElementById(id + '_search_controls');
	this.fade_animation = null;
	this.entry = entry;
	this.keywords_id = keywords_id;
	this.initial_open = open;
	if (panel_height) {
		this.panel_height = panel_height;
	} else {
		this.panel_height = 13; // default height in ems
	}
	StoreSearchDisclosure.superclass.constructor.call(this, id, open);
	YAHOO.util.Dom.removeClass(this.div.firstChild, 'no-js');
}

/**
 * Panel height in ems
 *
 * @var Number
 */
StoreSearchDisclosure.panel_height = 14;

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
	if (this.initial_open) {
		this.loading_container = null;
	} else {
		this.drawLoadingContainer();
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
	this.img = document.createElement('img');
	this.img.src = StoreSearchDisclosure.down_image.src;
	this.anchor.appendChild(this.img);

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

	this.img.src = StoreSearchDisclosure.up_image.src;

	if (this.loading_container)
		this.loadSearchPanel();

	StoreSearchDisclosure.superclass.open.call(this);
	this.closeSearchControls();
},

close: function()
{
	this.img.src = StoreSearchDisclosure.down_image.src;
	StoreSearchDisclosure.superclass.close.call(this);
	this.openSearchControls();
},

openWithAnimation: function()
{
	if (this.semaphore)
		return;


	if (this.loading_container)
		this.loadSearchPanel();

	var time = 0.5;

	this.img.src = StoreSearchDisclosure.up_image.src;
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
	this.animate_div.firstChild.style.height = this.panel_height + 'em';

	var attributes = { height: {
		to: this.panel_height,
		from: 0,
		unit: 'em'
	}};

	var animation = new YAHOO.util.Anim(this.animate_div, attributes, time,
		YAHOO.util.Easing.easeOut);

	var attributes = { top: {
		from: -this.panel_height,
		to: 0,
		unit: 'em'
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

	this.img.src = StoreSearchDisclosure.down_image.src;
	YAHOO.util.Dom.removeClass(this.anchor, 'swat-disclosure-anchor-opened');
	YAHOO.util.Dom.addClass(this.anchor, 'swat-disclosure-anchor-closed');

	this.animate_div.firstChild.style.position = 'relative';
	this.animate_div.style.overflow = 'hidden';
	this.animate_div.style.height = 'auto';
	this.animate_div.style.position = 'relative';

	var attributes = { height: {
		from: this.panel_height,
		to: 0,
		unit: 'em'
	}};

	var animation = new YAHOO.util.Anim(this.animate_div, attributes, time,
		YAHOO.util.Easing.easeOut);

	var attributes = { top: {
		from: 0,
		to: -this.panel_height,
		unit: 'em'
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

	var image = document.createElement('img');
	image.src = 'packages/swat/images/swat-button-throbber.gif';

	this.loading_container.appendChild(image);
}

StoreSearchDisclosure.prototype.loadSearchPanel = function()
{
	var client = new XML_RPC_Client('xml-rpc/search-panel');
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

	client.callProcedure('getContent', callback, [query], ['string']);
}

/**
 * Pushes search entry value down into keywords field
 */
StoreSearchDisclosure.prototype.pushDownKeywords = function()
{
	var keywords = this.getKeywords();
	if (keywords && !this.entry.isLabelTextShown()) {
		keywords.value = this.entry.input.value;
	}
}

/**
 * Pulls keywords field value up into search entry value
 */
StoreSearchDisclosure.prototype.pullUpKeywords = function()
{
	var keywords = this.getKeywords();
	if (keywords) {
		if (keywords.value == '') {
			this.entry.showLabelText();
		} else {
			this.entry.hideLabelText();
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
	this.search_controls.style.opacity = '0';
	this.search_controls.style.MozOpacity = '0';
	this.search_controls.style.filter = 'alpha(opacity=0)';
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

	this.fade_animation = new YAHOO.util.Anim(this.search_controls,
		{ opacity: { from: from, to: 0.999999 } }, 0.5,
		YAHOO.util.Easing.easeOut);

	this.fade_animation.animate();
}
