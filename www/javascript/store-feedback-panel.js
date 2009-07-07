function StoreFeedbackPanel(id)
{
	this.id             = id;
	this.form_loaded    = false;
	this.form_submitted = false;
	this.opened         = false;
	this.semaphore      = false;
	this.container      = null;
	this.form           = null;

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

StoreFeedbackPanel.sending_text   = 'Sending…';
StoreFeedbackPanel.loading_text   = 'Loading…';
StoreFeedbackPanel.cancel_text    = 'Cancel';
StoreFeedbackPanel.thank_you_text = 'Thank you for your feedback!';

StoreFeedbackPanel.prototype.init = function()
{
	this.element = document.getElementById(this.id);
	this.link = document.getElementById(this.id + '_link');
	YAHOO.util.Event.on(this.link, 'click', function(e) {
		YAHOO.util.Event.preventDefault(e);
		this.toggle();
	}, this, true);
}

StoreFeedbackPanel.prototype.toggle = function()
{
	if (this.opened) {
		this.close();
	} else {
		this.open();
	}
}

StoreFeedbackPanel.prototype.open = function()
{
	if (this.opened || this.semaphore) {
		return;
	}

	this.drawContainer();
	this.loadFeedbackForm();

	this.container.style.display = 'block';

	var height;
	if (this.form_loaded) {
		this.container.style.visibility = 'hidden';
		this.container.style.height = 'auto';
		// get interior dimensions to account for padding and border heights
		var region = YAHOO.util.Dom.getRegion(this.container.firstChild);
		new_height = region.bottom - region.top;
		this.container.style.height = '0';
		this.container.style.visibility = 'visible';
	} else {
		new_height = 324;
	}

	var animation = new YAHOO.util.Anim(
		this.container,
		{ height: { from: 0, to: new_height } },
		0.25,
		YAHOO.util.Easing.easeIn
	);

	animation.onComplete.subscribe(function() {
		if (this.form_loaded) {
			this.container.style.height = 'auto';
		}
		this.semaphore = false;
	}, this, true);

	animation.animate();

	if (this.container.firstChild) {
		var animation = new YAHOO.util.Anim(
			this.container.firstChild,
			{ opacity: { to: 1 } },
			0.25,
			YAHOO.util.Easing.easeIn
		);

		animation.animate();
	}

	this.semaphore = true;
	this.opened = true;
}

StoreFeedbackPanel.prototype.close = function()
{
	if (!this.opened || this.semaphore) {
		return;
	}

	this.drawContainer();

	var animation = new YAHOO.util.Anim(
		this.container,
		{ height: { to: 0 } },
		0.25,
		YAHOO.util.Easing.easeOut
	);

	animation.onComplete.subscribe(function() {
		this.semaphore = false;
		this.container.style.display = 'none';
	}, this, true);

	animation.animate();

	var animation = new YAHOO.util.Anim(
		this.container.firstChild,
		{ opacity: { to: 0 } },
		0.25,
		YAHOO.util.Easing.easeOut
	);

	animation.animate();

	this.semaphore = true;
	this.opened = false;
}

StoreFeedbackPanel.prototype.drawContainer = function()
{
	if (this.container) {
		return;
	}

	this.container = document.createElement('div');
	this.container.className = 'store-feedback-panel-container';

	this.element.appendChild(this.container);

	var panel_region     = YAHOO.util.Dom.getRegion(this.element);
	var container_region = YAHOO.util.Dom.getRegion(this.container);

	var panel_width      = panel_region.right     - panel_region.left;
	var container_width  = container_region.right - container_region.left;
	var panel_height     = panel_region.bottom    - panel_region.top;

	var span = document.createElement('span');
	span.className = 'store-feedback-panel-message';
	span.appendChild(document.createTextNode(StoreFeedbackPanel.loading_text));
	this.container.appendChild(span);

	var xy = [
		panel_region.left + Math.round((panel_width - container_width) / 2),
		panel_region.bottom - 5
	];

	YAHOO.util.Dom.setXY(this.container, xy);
}

StoreFeedbackPanel.prototype.drawCancelLink = function()
{
	this.cancel_link = document.createElement('a');
	this.cancel_link.href = '#';
	this.cancel_link.appendChild(
		document.createTextNode(StoreFeedbackPanel.cancel_text)
	);

	YAHOO.util.Event.on(this.cancel_link, 'click', function(e) {
		YAHOO.util.Event.preventDefault(e);
		this.close();
	}, this, true);

	function getLastDiv(node)
	{
		var last_div = null;
		var child_node;

		for (var i = 0; i < node.childNodes.length; i++) {
			child_node = node.childNodes[i];
			if (   child_node.nodeType == 1
				&& child_node.nodeName == 'DIV'
				&& child_node.className.indexOf('swat-input-hidden') == -1
			) {
				last_div = child_node;
			}
		}

		if (last_div) {
			last_div = getLastDiv(last_div);
		} else {
			last_div = node;
		}

		return last_div;
	}

	var parent_node = getLastDiv(this.form);

	parent_node.appendChild(this.cancel_link);
}

StoreFeedbackPanel.prototype.removeCancelLink = function()
{
	if (!this.cancel_link) {
		return;
	}

	YAHOO.util.Event.purgeElement(this.cancel_link, false, 'click');
	this.cancel_link.parentNode.removeChild(this.cancel_link);
	this.cancel_link = null;
}

StoreFeedbackPanel.prototype.loadFeedbackForm = function()
{
	if (this.form_loaded || !this.container) {
		return;
	}

	var client = new XML_RPC_Client('xml-rpc/feedback-panel');
	var that = this;

	var callback = function(response)
	{
		that.removeCancelLink();
		that.container.innerHTML = response.content;
		that.initForm();
		that.drawCancelLink();
		that.form_loaded = true;
	};

	client.callProcedure(
		'getContent',
		callback,
		['GET',    '',        location.href],
		['string', 'string', 'string']
	);
}

StoreFeedbackPanel.prototype.initForm = function()
{
	this.form = this.element.getElementsByTagName('form')[0];

	var animation = new YAHOO.util.Anim(
		this.form,
		{ opacity: { to: 1 } },
		0.25,
		YAHOO.util.Easing.easeIn
	);

	animation.onComplete.subscribe(function() {
		this.container.style.height = 'auto';
	}, this, true);

	animation.animate();

	YAHOO.util.Event.on(
		this.form,
		'submit',
		this.handleFormSubmit,
		this,
		true
	);
}

StoreFeedbackPanel.prototype.hide = function()
{
	var animation = new YAHOO.util.Anim(
		this.container,
		{ opacity: { to: 0 } },
		0.25,
		YAHOO.util.Easing.easeOut
	);

	animation.onComplete.subscribe(function() {
		this.container.parentNode.removeChild(this.container);
	});

	var span = document.createElement('span');
	span.className = 'store-feedback-panel-title';
	span.appendChild(
		document.createTextNode(StoreFeedbackPanel.thank_you_text)
	);
	this.link.parentNode.replaceChild(span, this.link);

	animation.animate();
}

StoreFeedbackPanel.prototype.handleFormSubmit = function(e)
{
	YAHOO.util.Event.preventDefault(e);

	if (this.form_submitted) {
		return;
	}

	var that = this;

	this.showThrobber();

	var client = new XML_RPC_Client('xml-rpc/feedback-panel');

	var callback = function(response)
	{
		if (response.success) {
			that.hide();
		} else {
			that.removeCancelLink();
			that.container.innerHTML = response.content;
			that.initForm();
			that.drawCancelLink();
		}
	};

	client.callProcedure(
		'getContent',
		callback,
		['POST',   this.getFormData(this.form), location.href],
		['string', 'string',                    'string']
	);

	this.submitted = true;
}

StoreFeedbackPanel.prototype.showThrobber = function()
{
	this.throbber = document.createElement('div');
	this.throbber.className = 'store-feedback-panel-overlay';

	var span = document.createElement('span');
	span.className = 'store-feedback-panel-message';
	span.appendChild(document.createTextNode(StoreFeedbackPanel.sending_text));
	this.throbber.appendChild(span);

	this.form.appendChild(this.throbber);

	var form_region = YAHOO.util.Dom.getRegion(this.form.firstChild);

	var region = YAHOO.util.Dom.getRegion(this.throbber);

	var form_width  = form_region.right  - form_region.left;
	var form_height = form_region.bottom - form_region.top;

	this.throbber.style.width  = form_width  + 'px';
	this.throbber.style.height = form_height + 'px';

	var width  = region.right  - region.left;
	var height = region.bottom - region.top;

	YAHOO.util.Dom.setXY(this.throbber, [form_region.left, form_region.top]);

	var animation = new YAHOO.util.Anim(
		this.throbber,
		{ opacity: { from: 0, to: 0.7 } },
		0.5,
		YAHOO.util.Easing.easeOut
	);

	animation.animate();
}

StoreFeedbackPanel.prototype.getFormData = function(form)
{
	var data = '';

	var inputNodes = form.getElementsByTagName('input');
	for (var i = 0; i < inputNodes.length; i++) {
		if (inputNodes[i].name) {
			data += '&' + encodeURIComponent(inputNodes[i].name) + '=' +
				encodeURIComponent(inputNodes[i].value);
		}
	}

	var textareaNodes = form.getElementsByTagName('textarea');
	for (var i = 0; i < textareaNodes.length; i++) {
		if (textareaNodes[i].name) {
			data += '&' + encodeURIComponent(textareaNodes[i].name) + '=' +
				encodeURIComponent(textareaNodes[i].value);
		}
	}

	var checkboxNodes = form.getElementsByTagName('checkbox');
	for (var i = 0; i < checkboxNodes.length; i++) {
		if (checkboxNodes[i].name) {
			if (checkboxNodes[i].checked) {
				data += '&' + encodeURIComponent(checkboxNodes[i].name) + '=' +
					encodeURIComponent(checkboxNodes[i].value);
			} else {
				data += '&' + encodeURIComponent(checkboxNodes[i].name) + '=';
			}
		}
	}

	var radioNodes = form.getElementsByTagName('radio');
	for (var i = 0; i < radioNodes.length; i++) {
		if (radioNodes[i].checked && radioNodes[i].name) {
			data += '&' + encodeURIComponent(radioNodes[i].name) + '=' +
				encodeURIComponent(radioNodes[i].value);
		}
	}

	var selectNodes = form.getElementsByTagName('select');
	for (var i = 0; i < selectNodes.length; i++) {
		var select = selectNodes[i];
		if (select.name) {
			data += '&' + encodeURIComponent(select.name) + '=' +
				encodeURIComponent(select.options[select.selectedIndex].value);
		}
	}

	data = data.substr(1);

	return data;
}
