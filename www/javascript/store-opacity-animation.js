/**
 * Opacity animation for everyone except IE becuase IE can't do it
 *
 * The only difference from stock animation is this animation does not apply
 * the 'zoom :1' css hack when setting opacity. As a result, opacity animations
 * only work in non IE browsers.
 */
(function() {
	StoreOpacityAnimation = function(el, attributes, duration, method) {
		StoreOpacityAnimation.superclass.constructor.call(this, el,
			attributes, duration, method);
	};

	YAHOO.extend(StoreOpacityAnimation, YAHOO.util.Anim);

	// shorthand
	var superclass = StoreOpacityAnimation.superclass;
	var proto = StoreOpacityAnimation.prototype;

	/**
	 * Applies a value to an attribute
	 * @param {String} attr The name of the attribute.
	 * @param {Number} val The value to be applied to the attribute.
	 * @param {String} unit The unit ('px', '%', etc.) of the value.
	 */
	proto.setAttribute = function(attr, val, unit) {
		if (attr == 'opacity') {
			if (this.patterns.noNegatives.test(attr)) {
				val = (val > 0) ? val : 0;
			}
			this.getEl().style.opacity = val + unit;
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
		if (attr == 'opacity') {
			var val = this.getEl().style.opacity;
		} else {
			var val = superclass.getAttribute.call(this, attr);
		}

		return val;
	};

})();
