YAHOO.util.Event.onDOMReady(function() {

	var Dom    = YAHOO.util.Dom;
	var Event  = YAHOO.util.Event;
	var Anim   = YAHOO.util.Anim;
	var Motion = YAHOO.util.Motion;

	var data = StoreProductPageImages;
	if (!data) {
		return;
	}

	var StoreProductPageImageController = function(data)
	{
		this.semaphore     = false;
		this.data          = data;
		this.opened        = false;
		this.current_image = 0;

		// list of select elements to hide for IE6
		this.select_elements = [];

		this.max_dimensions = this.getMaxDimensions();

		this.initLinks();
		this.drawOverlay();
		this.drawContainer();
		this.initLocation();
	};

	StoreProductPageImageController.prototype.getMaxDimensions = function()
	{
		var max_dimensions = [0, 0];

		for (var i = 0; i < this.data.images.length; i++) {
			max_dimensions[0] = Math.max(max_dimensions[0],
				this.data.images[i].large_width);

			max_dimensions[1] = Math.max(max_dimensions[1],
				this.data.images[i].large_height);
		}

		return max_dimensions;
	};

	StoreProductPageImageController.prototype.initLinks = function()
	{
		this.image_link = document.getElementById('product_image_link');

		Event.on(this.image_link, 'click', function(e) {
			Event.preventDefault(e);
			this.openWithAnimation();
		}, this, true);
	};

	StoreProductPageImageController.prototype.drawContainer = function()
	{
		this.container = document.createElement('div');
		this.container.style.visibililty = 'hidden';
		this.container.className = 'store-product-image-container';

		var wrapper = document.createElement('div');
		wrapper.className = 'store-product-image-wrapper';

		var pinkies = this.drawPinkies();
		if (pinkies) {
			wrapper.appendChild(pinkies);
			Dom.addClass(this.container, 'store-product-image-with-pinkies');
		}

		var image_wrapper = document.createElement('div');
		image_wrapper.className = 'store-product-image-image-wrapper';

		image_wrapper.appendChild(this.drawHeader());
		image_wrapper.appendChild(this.drawImage());

		wrapper.appendChild(image_wrapper);
		wrapper.appendChild(this.drawClear());

		this.container.appendChild(wrapper);

		var body = document.getElementsByTagName('body')[0];
		body.appendChild(this.container);
	};

	StoreProductPageImageController.prototype.drawClear = function()
	{
		var clear = document.createElement('div');
		clear.style.clear = 'both';
		return clear;
	};

	StoreProductPageImageController.prototype.drawHeader = function()
	{
		var header = document.createElement('div');
		header.className = 'store-product-image-header';

		header.appendChild(this.drawCloseLink());
		header.appendChild(this.drawTitle());

		return header;
	};

	StoreProductPageImageController.prototype.drawTitle= function()
	{
		this.title = document.createElement('div');
		this.title.className = 'store-product-image-title';
		return this.title;
	};

	StoreProductPageImageController.prototype.drawCloseLink = function()
	{
		var close_link = document.createElement('a');
		close_link.className = 'store-product-image-close';
		close_link.href = '#close';

		close_link.appendChild(document.createTextNode('Close'));

		Event.on(close_link, 'click', function(e) {
			Event.preventDefault(e);
			this.close();
		}, this, true);

		return close_link;
	};

	StoreProductPageImageController.prototype.drawImage = function()
	{
		this.image = document.createElement('img');
		this.image.className = 'store-product-image-image';
		return this.image;
	};

	StoreProductPageImageController.prototype.drawPinkies = function()
	{
		var pinky_list;

		this.pinkies = [];

		if (this.data.images.length > 1) {

			var pinky, image, link;
			pinky_list = document.createElement('ul');
			pinky_list.className = 'store-product-image-pinkies';
			for (var i = 0; i < this.data.images.length; i++) {

				image = document.createElement('img');
				image.src = data.images[i].pinky_uri;
				image.width = data.images[i].pinky_width;
				image.height = data.images[i].pinky_height;

				link = document.createElement('a');
				link.href = '#image' + data.images[i].id;
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
				pinky.appendChild(link);

				this.pinkies.push(pinky);

				pinky_list.appendChild(pinky);
			}

		}

		return pinky_list;
	};

	StoreProductPageImageController.prototype.drawOverlay = function()
	{
		this.overlay = document.createElement('div');

		this.overlay.className = 'store-product-image-overlay';
		this.overlay.style.display = 'none';

		Event.on(this.overlay, 'click', this.close, this, true);

		var body = document.getElementsByTagName('body')[0];
		body.appendChild(this.overlay);
	};

	StoreProductPageImageController.prototype.selectImage = function(index)
	{
		if (!this.data.images[index]) {
			return false;
		}

		var data = this.data.images[index];

		this.image.width = data.large_width;
		this.image.height = data.large_height;
		this.image.src = data.large_uri;

		this.setTitle(data, this.data.product);

		Dom.removeClass(
			this.pinkies[this.current_image],
			'store-product-image-pinky-selected');

		Dom.addClass(
			this.pinkies[index],
			'store-product-image-pinky-selected');

		this.current_image = index;

		// set address bar to current image
		var baseLocation = location.href.split('#')[0];
		location.href = baseLocation + '#image' + data.id;

		return true;

	};

	StoreProductPageImageController.prototype.selectImageWithAnimation =
		function(index)
	{
		if (this.semaphore || !this.data.images[index] ||
			this.current_image == index) {
			return false;
		}

		var data = this.data.images[index];

		var anim = new Anim(this.image, { opacity: { to: 0 } },
			0.200, YAHOO.util.Easing.easeIn);

		anim.onComplete.subscribe(function() {

			this.setTitle(data, this.data.product);

			this.image.width = data.large_width;
			this.image.height = data.large_height;
			this.image.src = data.large_uri;

			var anim = new Anim(this.image, { opacity: { to: 1 } },
				0.200, YAHOO.util.Easing.easeOut);

			anim.onComplete.subscribe(function() {
				this.semaphore = false;
			}, this, true);

			anim.animate();
		}, this, true);

		anim.animate();

		Dom.removeClass(
			this.pinkies[this.current_image],
			'store-product-image-pinky-selected');

		Dom.addClass(
			this.pinkies[index],
			'store-product-image-pinky-selected');

		this.current_image = index;

		// set address bar to current image
		var baseLocation = location.href.split('#')[0];
		location.href = baseLocation + '#image' + data.id;

		return true;

	};

	StoreProductPageImageController.prototype.setTitle =
		function(image, product)
	{
		if (image.title) {
			this.title.innerHTML = product.title + ' - ' + image.title +
				' (Large Image)';
		} else {
			this.title.innerHTML = product.title + ' (Large Image)';
		}
	};

	StoreProductPageImageController.prototype.initLocation = function()
	{
		var hash = location.hash;
		hash = (hash.substring(0, 1) == '#') ? hash.substring(1) : hash;
		var image_id = hash.replace('image', '');

		if (image_id) {
			var index = null;
			for (var i = 0; i < this.data.images.length; i++) {
				if (this.data.images[i].id == image_id) {
					index = i;
					break;
				}
			}
			if (index !== null) {
				this.selectImage(index);
				this.open();
			}
		}

		// check if window location changes from back/forward button use
		// this doesn't matter in IE and Opera but is nice for Firefox and
		// recent Safari users.
		var that = this;
		setInterval(function() {
			that.updateLocation();
		}, StoreProductPageImageController.LOCATION_INTERVAL);
	};

	StoreProductPageImageController.prototype.updateLocation = function()
	{
		var current_image_id = this.data.images[this.current_image].id;

		var hash = location.hash;
		hash = (hash.substring(0, 1) == '#') ? hash.substring(1) : hash;
		var image_id = hash.replace('image', '');

		if (image_id && image_id != current_image_id) {
			var index = null;
			for (var i = 0; i < this.data.images.length; i++) {
				if (this.data.images[i].id == image_id) {
					index = i;
					break;
				}
			}
			if (index !== null) {
				this.selectImage(index);
			}
		}
	};

	StoreProductPageImageController.prototype.open = function()
	{
		this.showOverlay();
		/*

		// get approximate max height and width excluding close text
		var padding = StoreProductPageImageController.padding;
		var max_width = YAHOO.util.Dom.getViewportWidth() - (padding * 2);
		var max_height = YAHOO.util.Dom.getViewportHeight() - (padding * 2);

		this.scaleImage(max_width, max_height);

		this.preview_container.style.visibility = 'hidden';
		this.preview_container.style.display = 'block';

		// now that is it displayed, adjust height for the close text
		var region = YAHOO.util.Dom.getRegion(this.preview_close_text);
		max_height -= (region.bottom - region.top);
		this.scaleImage(max_width, max_height);

		this.preview_container.style.visibility = 'visible';

		// x is relative to center of page
		var scroll_top = YAHOO.util.Dom.getDocumentScrollTop();
		var x = -Math.round((this.preview_image.width  + padding) / 2);
		var y = Math.round((max_height - this.preview_image.height + padding) / 2) +
			scroll_top;

		YAHOO.util.Dom.setY(this.preview_container, y);

		// set x
		this.preview_container.style.left = '50%';
		this.preview_container.style.marginLeft = x + 'px';

		// focus link to capture keyboard events
		this.preview_link.focus();
		*/

		this.opened = true;
	};

	StoreProductPageImageController.prototype.openWithAnimation = function()
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

		var scroll_top = YAHOO.util.Dom.getDocumentScrollTop();

		if (this.pinkies.length) {
			var w = this.max_dimensions[0] + 114;
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
		}, 0.200, YAHOO.util.Easing.easeOutStrong);

		anim.onComplete.subscribe(function() {
			this.opened = true;
			this.semaphore = false;
		}, this, true);

		anim.animate();
	};

	StoreProductPageImageController.prototype.scaleImage = function(max_width, max_height)
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

	StoreProductPageImageController.prototype.showOverlay = function()
	{
		if (StoreProductPageImageController.ie6) {
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

	StoreProductPageImageController.prototype.hideOverlay = function()
	{
		this.overlay.style.display = 'none';
		if (StoreProductPageImageController.ie6) {
			for (var i = 0; i < this.select_elements.length; i++) {
				this.select_elements[i].style.visibility =
					this.select_elements[i].style._visibility;
			}
		}
	};

	StoreProductPageImageController.prototype.close = function()
	{
		this.hideOverlay();

		this.container.style.display = 'none';

		// remove image from address bar
		var baseLocation = location.href.split('#')[0];
		location.href = baseLocation + '#';

		this.opened = false;
	};

	StoreProductPageImageController.LOCATION_INTERVAL = 200; // in ms
	StoreProductPageImageController.RESIZE_PERIOD = 200; // in ms
	StoreProductPageImageController.ie6 = false /*@cc_on || @_jscript_version < 5.7 @*/;

	new StoreProductPageImageController(data);

});
