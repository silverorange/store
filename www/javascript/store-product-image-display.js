var StoreProductImageDisplay = function(data, config)
{
	this.semaphore     = false;
	this.data          = data;
	this.opened        = false;
	this.current_image = 0;

	if (config) {
		this.configure(config);
	} else {
		this.configure({});
	}

	this.dimensions = {
		pinky: {}
	};

	// list of select elements to hide for IE6
	this.select_elements = [];

	// preload images and create id-to-index lookup table
	var images = [], image;
	this.image_indexes_by_id = {};
	for (var i = 0; i < this.data.images.length; i++ ) {
		// preload images
		image = new Image();
		image.src = this.data.images[i].large_uri
		images.push(image);

		// build id-to-index table
		this.image_indexes_by_id[this.data.images[i].id] = i;
	}

	YAHOO.util.Event.onDOMReady(function() {
		this.initLinks();
		this.drawOverlay();
		this.drawContainer();
		this.initMaxDimensions();
		this.initLocation();
	}, this, true);
};

StoreProductImageDisplay.ie6 = false /*@cc_on || @_jscript_version < 5.7 @*/;

StoreProductImageDisplay.close_text = 'Close';

(function() {

	var Dom    = YAHOO.util.Dom;
	var Event  = YAHOO.util.Event;
	var Anim   = YAHOO.util.Anim;
	var Motion = YAHOO.util.Motion;
	var Easing = YAHOO.util.Easing;

	StoreProductImageDisplay.prototype.configure = function(config)
	{
		this.config = {
			period: {
				open:          0.200, // in sec
				fade:          0.050, // in sec
				resize:        0.100, // in sec
				locationCheck: 0.200  // in sec
			}
		};
	};

	StoreProductImageDisplay.prototype.initMaxDimensions = function()
	{
		this.max_dimensions = [0, 0];

		for (var i = 0; i < this.data.images.length; i++) {
			this.max_dimensions[0] = Math.max(
				this.max_dimensions[0],
				this.data.images[i].large_width);

			this.max_dimensions[1] = Math.max(
				this.max_dimensions[1],
				this.data.images[i].large_height);
		}

		// Check if pinkies are taller than the tallest large image. Calculates
		// height based on collapsing margin CSS model with the first pinky
		// possibly having different margin or padding.
		if (this.pinkies.length > 1) {
			var dimensions = this.dimensions.pinky;

			var top_height = dimensions.firstMarginTop +
				dimensions.firstPaddingTop;

			var first_height = dimensions.firstPaddingBottom +
				Math.max(dimensions.marginTop, dimensions.firstMarginBottom) +
				dimensions.paddingTop;

			var mid_height = dimensions.paddingBottom +
				Math.max(dimensions.marginTop, dimensions.marginBottom) +
				dimensions.paddingTop;

			var bottom_height = dimensions.paddingBottom +
				dimensions.marginBottom;

			var pinky_height = this.data.images[0].pinky_height;

			var height = top_height + first_height +
				(this.pinkies.length - 2) * mid_height +
				bottom_height +
				this.pinkies.length * pinky_height;

			this.max_dimensions[1] = Math.max(this.max_dimensions[1], height);
		}

		return max_dimensions;
	};

	StoreProductImageDisplay.prototype.initLinks = function()
	{
		this.image_link = document.getElementById('product_image_link');

		Event.on(this.image_link, 'click', function(e) {
			Event.preventDefault(e);
			this.selectImage(0);
			this.openWithAnimation();
		}, this, true);

		var pinky_list = document.getElementById('product_secondary_images');
		if (pinky_list) {
			var that = this;
			var pinky_link;
			var pinky_items = Dom.getChildren(pinky_list);
			for (var i = 0; i < pinky_items.length; i++) {
				pinky_link = Dom.getFirstChildBy(pinky_items[i],
					function(n) { return (n.nodeName == 'A'); } );

				if (pinky_link) {
					(function() {
						var index = i + 1;
						Event.on(pinky_link, 'click', function(e) {
							Event.preventDefault(e);
							that.selectImage(index);
							that.openWithAnimation();
						}, that , true);
					}());
				}
			}
		}
	};

	StoreProductImageDisplay.prototype.drawContainer = function()
	{
		this.container = document.createElement('div');
		this.container.style.display = 'none';
		this.container.className = 'store-product-image-display-container';

		SwatZIndexManager.raiseElement(this.container);

		var wrapper = document.createElement('div');
		wrapper.className = 'store-product-image-display-wrapper';

		var pinkies = this.drawPinkies();
		if (pinkies) {
			wrapper.appendChild(pinkies);
			Dom.addClass(this.container,
				'store-product-image-display-with-pinkies');
		}

		this.image_container = document.createElement('div');
		this.image_container.className =
			'store-product-image-display-image-container';

		this.image_container.appendChild(this.drawHeader());
		this.image_container.appendChild(this.drawImage());

		wrapper.appendChild(this.image_container);
		wrapper.appendChild(this.drawClear());

		this.container.appendChild(wrapper);

		var body = document.getElementsByTagName('body')[0];
		body.appendChild(this.container);

		if (this.pinkies.length > 1) {
			this.dimensions.pinky = {
				firstPaddingTop:    parseInt(Dom.getStyle(this.pinkies[0], 'paddingTop')),
				firstPaddingBottom: parseInt(Dom.getStyle(this.pinkies[0], 'paddingBottom')),
				firstMarginTop:     parseInt(Dom.getStyle(this.pinkies[0], 'marginTop')),
				firstMarginBottom:  parseInt(Dom.getStyle(this.pinkies[0], 'marginBottom')),
				paddingTop:         parseInt(Dom.getStyle(this.pinkies[1], 'paddingTop')),
				paddingBottom:      parseInt(Dom.getStyle(this.pinkies[1], 'paddingBottom')),
				marginTop:          parseInt(Dom.getStyle(this.pinkies[1], 'marginTop')),
				marginBottom:       parseInt(Dom.getStyle(this.pinkies[1], 'marginBottom'))
			};
		}
	};

	// {{{ drawClear()

	StoreProductImageDisplay.prototype.drawClear = function()
	{
		var clear = document.createElement('div');
		clear.className = 'store-product-image-display-clear';
		return clear;
	};

	// }}}
	// {{{ drawHeader()

	StoreProductImageDisplay.prototype.drawHeader = function()
	{
		var header = document.createElement('div');
		header.className = 'store-product-image-display-header';

		SwatZIndexManager.raiseElement(header);

		header.appendChild(this.drawCloseLink());
		header.appendChild(this.drawTitle());

		return header;
	};

	// }}}
	// {{{ drawTitle()

	StoreProductImageDisplay.prototype.drawTitle= function()
	{
		this.title = document.createElement('div');
		this.title.className = 'store-product-image-display-title';
		return this.title;
	};

	// }}}
	// {{{ drawCloseLink()

	StoreProductImageDisplay.prototype.drawCloseLink = function()
	{
		var close_link = document.createElement('a');
		close_link.className = 'store-product-image-display-close';
		close_link.href = '#close';

		close_link.appendChild(document.createTextNode(
			StoreProductImageDisplay.close_text));

		Event.on(close_link, 'click', function(e) {
			Event.preventDefault(e);
			this.close();
		}, this, true);

		return close_link;
	};

	// }}}
	// {{{ drawImage()

	StoreProductImageDisplay.prototype.drawImage = function()
	{
		this.image = document.createElement('img');
		this.image.className = 'store-product-image-display-image';
		return this.image;
	};

	// }}}

	StoreProductImageDisplay.prototype.drawPinkies = function()
	{
		var pinky_list;

		this.pinkies = [];

		if (this.data.images.length > 1) {

			var pinky, image, link;
			pinky_list = document.createElement('ul');
			pinky_list.className = 'store-product-image-display-pinkies';
			for (var i = 0; i < this.data.images.length; i++) {

				image = document.createElement('img');
				image.src = this.data.images[i].pinky_uri;
				image.width = this.data.images[i].pinky_width;
				image.height = this.data.images[i].pinky_height;

				link = document.createElement('a');
				link.href = '#image' + this.data.images[i].id;
				link.appendChild(image);

				var that = this;
				(function() {
					var index = i;
					Event.on(link, 'click', function(e) {
						Event.preventDefault(e);
						that.selectImageWithAnimation(index);
					}, that, true);
				}());

				pinky = document.createElement('li');
				if (i == 0) {
					pinky.className = 'store-product-image-display-pinky-first';
				} else {
				}
				pinky.appendChild(link);

				this.pinkies.push(pinky);

				pinky_list.appendChild(pinky);
			}

		}

		return pinky_list;
	};

	// {{{ drawOverlay()

	StoreProductImageDisplay.prototype.drawOverlay = function()
	{
		this.overlay = document.createElement('div');

		this.overlay.className = 'store-product-image-display-overlay';
		this.overlay.style.display = 'none';

		SwatZIndexManager.raiseElement(this.overlay);

		Event.on(this.overlay, 'click', this.close, this, true);

		var body = document.getElementsByTagName('body')[0];
		body.appendChild(this.overlay);
	};

	// }}}

	StoreProductImageDisplay.prototype.selectImage = function(index)
	{
		if (!this.data.images[index]) {
			return false;
		}

		var data = this.data.images[index];

		this.image.style.width = data.large_width + 'px';
		this.image.style.height = data.large_height + 'px';
		this.image.src = data.large_uri;

		this.setTitle(data, this.data.product);

		Dom.removeClass(
			this.pinkies[this.current_image],
			'store-product-image-display-pinky-selected');

		Dom.addClass(
			this.pinkies[index],
			'store-product-image-display-pinky-selected');

		this.current_image = index;

		// set address bar to current image
		var baseLocation = location.href.split('#')[0];
		location.href = baseLocation + '#image' + data.id;

		return true;

	};

	StoreProductImageDisplay.prototype.selectImageWithAnimation =
		function(index)
	{
		if (this.semaphore || !this.data.images[index] ||
			this.current_image == index) {
			return false;
		}

		this.semaphore = true;

		var data = this.data.images[index];

		var anim = new Anim(this.image_container, { opacity: { to: 0 } },
			0.0500);

		anim.onComplete.subscribe(function() {

			this.setTitle(data, this.data.product);

			this.image.src = data.large_uri;

			var anim = new Anim(this.image, {
					width:  { to: data.large_width  },
					height: { to: data.large_height }
				}, this.config.period.resize, Easing.easeIn);

			anim.onComplete.subscribe(function() {

				var anim = new Anim(this.image_container,
					{ opacity: { to: 1 } }, 0.0500);

				anim.onComplete.subscribe(function() {
					this.semaphore = false;
				}, this, true);

				anim.animate();
			}, this, true);

			anim.animate();
		}, this, true);

		anim.animate();

		Dom.removeClass(
			this.pinkies[this.current_image],
			'store-product-image-display-pinky-selected');

		Dom.addClass(
			this.pinkies[index],
			'store-product-image-display-pinky-selected');

		this.current_image = index;

		// set address bar to current image
		var baseLocation = location.href.split('#')[0];
		location.href = baseLocation + '#image' + data.id;

		return true;

	};

	StoreProductImageDisplay.prototype.setTitle = function(image, product)
	{
		if (image.title) {
			this.title.innerHTML = product.title + ' - ' + image.title;
		} else {
			this.title.innerHTML = product.title;
		}
	};

	StoreProductImageDisplay.prototype.initLocation = function()
	{
		var hash = location.hash;
		hash = (hash.substring(0, 1) == '#') ? hash.substring(1) : hash;
		var image_id = hash.replace(/[^0-9]/g, '');

		if (image_id) {
			if (typeof this.image_indexes_by_id[image_id] != 'undefined') {
				this.selectImage(this.image_indexes_by_id[image_id]);
				this.open();
			}
		}

		// check if window location changes from back/forward button use
		// this doesn't matter in IE and Opera but is nice for Firefox and
		// recent Safari users.
		var that = this;
		setInterval(function() {
			that.checkLocation();
		}, this.config.period.locationCheck * 1000);
	};

	StoreProductImageDisplay.prototype.checkLocation = function()
	{
		if (this.semaphore) {
			return;
		}

		var current_image_id = this.data.images[this.current_image].id;

		var hash = location.hash;
		hash = (hash.substring(0, 1) == '#') ? hash.substring(1) : hash;
		var image_id = hash.replace(/[^0-9]/g, '');

		if (image_id && image_id != current_image_id) {
			if (typeof this.image_indexes_by_id[image_id] != 'undefined') {
				this.selectImage(this.image_indexes_by_id[image_id]);
			}
		}

		if (image_id == '' && current_image_id && this.opened) {
			this.close();
		} else if (image_id && !this.opened) {
			this.open();
		}
	};

	StoreProductImageDisplay.prototype.open = function()
	{
		this.selectImage(this.current_image);

		this.showOverlay();

		var scroll_top = Dom.getDocumentScrollTop();

		if (this.pinkies.length) {
			var w = this.max_dimensions[0] + 110;
		} else {
			var w = this.max_dimensions[0] + 12;
		}

		var h = this.max_dimensions[1] + 12;
		var x = Math.floor((Dom.getViewportWidth() - w) / 2);
		var y = Math.max(0, Math.floor((Dom.getViewportHeight() - h) / 2) + scroll_top);

		this.container.style.display = 'block';
		this.container.style.width = w + 'px';
		this.container.style.height = h + 'px';
		Dom.setXY(this.container, [x, y]);

		this.opened = true;
	};

	StoreProductImageDisplay.prototype.openWithAnimation = function()
	{
		if (this.semaphore || this.opened) {
			return;
		}

		this.selectImage(this.current_image);

		this.semaphore = true;

		this.showOverlay();
		this.container.style.display = 'block';

		var region = Dom.getRegion(this.image_link);

		Dom.setXY(this.container, [region.left, region.top]);
		this.container.style.width = region.width + 'px';
		this.container.style.height = region.height + 'px';

		var scroll_top = Dom.getDocumentScrollTop();

		if (this.pinkies.length) {
			var w = this.max_dimensions[0] + 110;
		} else {
			var w = this.max_dimensions[0] + 12;
		}

		var h = this.max_dimensions[1] + 12;
		var x = Math.floor((Dom.getViewportWidth() - w) / 2);
		var y = Math.max(0, Math.floor((Dom.getViewportHeight() - h) / 2) + scroll_top);

		var anim = new Motion(this.container, {
			'points': { to: [x, y] },
			'width':  { to: w },
			'height': { to: h }
		}, this.config.period.open, Easing.easeOutStrong);

		anim.onComplete.subscribe(function() {
			this.opened = true;
			this.semaphore = false;
		}, this, true);

		anim.animate();
	};

	StoreProductImageDisplay.prototype.scaleImage = function(max_width, max_height)
	{
		// if preview image is larger than viewport width, scale down
		if (this.preview_image.width > max_width) {
			this.preview_image.width = max_width;
			this.preview_image.height = (this.preview_image.height *
				(max_width / this.preview_image.width));
		}

		// if preview image is larger than viewport height, scale down
		if (this.preview_image.height > max_height) {
			this.preview_image.width = (this.preview_image.width *
				(max_height / this.preview_image.height));

			this.preview_image.height = max_height;
		}
	};

	StoreProductImageDisplay.prototype.showOverlay = function()
	{
		// init keyup handler for escape key to close
		Event.on(document, 'keyup', this.handleKeyUp, this, true);

		if (StoreProductImageDisplay.ie6) {
			this.select_elements = document.getElementsByTagName('select');
			for (var i = 0; i < this.select_elements.length; i++) {
				this.select_elements[i].style._visibility =
					this.select_elements[i].style.visibility;

				this.select_elements[i].style.visibility = 'hidden';
			}
		}
		this.overlay.style.height = Dom.getDocumentHeight() + 'px';
		this.overlay.style.display = 'block';
	}

	StoreProductImageDisplay.prototype.hideOverlay = function()
	{
		this.overlay.style.display = 'none';
		if (StoreProductImageDisplay.ie6) {
			for (var i = 0; i < this.select_elements.length; i++) {
				this.select_elements[i].style.visibility =
					this.select_elements[i].style._visibility;
			}
		}
	};

	StoreProductImageDisplay.prototype.close = function()
	{
		if (this.semaphore) {
			return;
		}

		this.hideOverlay();

		this.container.style.display = 'none';

		// remove image from address bar
		var baseLocation = location.href.split('#')[0];
		location.href = baseLocation + '#closed';

		// unset keyup handler
		Event.removeListener(document, 'keyup', this.handleKeyUp);

		this.opened = false;
	};

	StoreProductImageDisplay.prototype.handleKeyUp = function(e)
	{
		// close preview on backspace or escape
		if (e.keyCode == 8 || e.keyCode == 27) {
			Event.preventDefault(e);
			this.close();
		}
	};

}());
