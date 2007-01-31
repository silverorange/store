/**
 * @class StoreBackgroundImageAnim subclass for animating the background image
 * of an element with a series of frames.
 *
 * <p>Usage: <code>var myAnim = new StoreBackgroundImageAnim(el, { frames: { from: 0, to: 50 }, 1, Y.Easing.e
 * <p>Frames are arbitrary numbers corresponding to the number of animation frame images you want to cycle through
 * @requires YAHOO.util.Anim
 * @requires YAHOO.util.AnimMgr
 * @requires YAHOO.util.Easing
 * @requires YAHOO.util.Dom
 * @requires YAHOO.util.Event
 * @constructor
 * @param {HTMLElement | String} el Reference to the element that will be animated
 * @param {Object} attributes The attribute(s) to be animated. Each attribute
 * is an object with at minimum a "to" or "by" member defined. Additional
 * optional members are "from" (defaults to current value), "units" (defaults
 * to "px"). All attribute names use camelCase.
 * @param {Number} duration (optional, defaults to 1 second) Length of animation (frames or seconds), defaults to time-bas
 * @param {Function} method (optional, defaults to YAHOO.util.Easing.easeNone) Computes the values that are applied to the
 */
(function() {
	StoreBackgroundImageAnim = function(el, attributes, duration, method) {
		StoreBackgroundImageAnim.superclass.constructor.call(
			this, el, attributes, duration, method);

		StoreBackgroundImageAnim.frame_images = [];
	};

	YAHOO.extend(StoreBackgroundImageAnim, YAHOO.util.Anim);

	// shorthand
	var superclass = StoreBackgroundImageAnim.superclass;
	var proto = StoreBackgroundImageAnim.prototype;

	/**
	 * Adds image frames to this animation
	 * @param {String | Array} images Either a single image filename or an Array of image filenames to add to this animation.
	 */
	proto.addFrameImages = function(images) {
		if (!(images instanceof Array))
			images = [images];

		// preload images
		for (var i = 0; i <= images.length; i++) {
			image = new Image();
			image.src = images[i]; 
			StoreBackgroundImageAnim.frame_images.push(image);
		}
	}

	/**
	 * toString method
	 * @return {String} string represenation of anim obj
	 */
	proto.toString = function() {
		var el = this.getEl();
		var id = el.id || el.tagName;
		return ("StoreBackgroundImage " + id);
	};

	proto.patterns.frames = /^frames$/i;

	/**
	 * Applies a value to an attribute
	 * @param {String} attr The name of the attribute.
	 * @param {Number} val The value to be applied to the attribute.
	 * @param {String} unit The unit ('px', '%', etc.) of the value.
	 */
	proto.setAttribute = function(attr, val, unit) {
		if ( this.patterns.frames.test(attr) ) {
			// apply frame
			var frame_image = StoreBackgroundImageAnim.frame_images[val].src;
			superclass.setAttribute.call(this, 'backgroundImage',
				'url(' + frame_image + ')', unit);
		} else {
			superclass.setAttribute.call(this, attr, val, unit);
		}
	};

	/**
	 * Sets the default value to be used when "from" is not supplied.
	 * @param {String} attr The attribute being set.
	 * @param {Number} val The default value to be applied to the attribute.
	 */
	proto.getAttribute = function(attr) {
		if ( this.patterns.frames.test(attr) ) {
			// start at frame 0 by default
			var val = 0;
		} else {
			val = superclass.getAttribute.call(this, attr);
		}

		return val;
	};

	/**
	 * Returns the value computed by the animation's "method".
	 * @param {String} attr The name of the attribute.
	 * @param {Number} start The value this attribute should start from for this 
	 * @param {Number} end  The value this attribute should end at for this anima
	 * @return {Number} The Value to be applied to the attribute.
	 */
	proto.doMethod = function(attr, start, end) {
		var val = null;

		if ( this.patterns.frames.test(attr) ) {
			// get frame number
			var percent = this.method(this.currentFrame, 0, 100, this.totalFrames) / 100;
			val = start + Math.floor(percent * (end - start));
		} else {
			val = superclass.doMethod.call(this, attr, start, end);
		}
		return val;
	};

})();
